<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

use Abiesoft\System\Testing\TestCase;
use ReflectionClass;
use ReflectionMethod;

class TestCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $isGo = false;
        $moduleInput = null;

        for ($i = 2; $i < count($args); $i++) {
            $arg = trim($args[$i]);
            if (in_array(strtolower($arg), ['--go', '-go', '--golang', '-g'])) {
                $isGo = true;
            } else if (!str_starts_with($arg, '-')) {
                $moduleInput = $arg;
            }
        }

        $baseDir = dirname(__DIR__, 3);

        $this->log("\n=======================================================", self::COLOR_CYAN);
        $this->log("         🧪  AbieSoft Automated Test Runner", self::COLOR_CYAN);
        $this->log("=======================================================", self::COLOR_CYAN);

        $totalTests = 0;
        $totalPassed = 0;
        $totalFailed = 0;

        // 1. Jalankan Unit Test Golang jika diminta --go atau tanpa argumen spesifik PHP
        if ($isGo || (!$isGo && !$moduleInput)) {
            $this->runGoTests($baseDir, $moduleInput, $totalTests, $totalPassed, $totalFailed);
        }

        // 2. Jalankan Unit Test PHP jika BUKAN khusus flag --go murni tanpa modul atau jika modul PHP
        if (!$isGo || (!$moduleInput && !$isGo)) {
            $this->runPhpTests($baseDir, $moduleInput, $totalTests, $totalPassed, $totalFailed);
        }

        // Ringkasan Akhir
        $this->log("\n-------------------------------------------------------", self::COLOR_CYAN);
        $this->log("Ringkasan Hasil Test:", self::COLOR_YELLOW);
        $this->log("  Total Test    : " . $totalTests);
        $this->log("  Passed        : " . $totalPassed, self::COLOR_GREEN);
        if ($totalFailed > 0) {
            $this->log("  Failed        : " . $totalFailed, self::COLOR_RED);
            $this->log("❌ Beberapa unit test mengalami kegagalan.\n", self::COLOR_RED);
        } else if ($totalTests === 0) {
            $this->log("💡 Tidak ada file unit test yang ditemukan.", self::COLOR_YELLOW);
        } else {
            $this->log("🎉 Seluruh Unit Test Berhasil (PASS)!\n", self::COLOR_GREEN);
        }
    }

    private function runGoTests(string $baseDir, ?string $moduleInput, int &$totalTests, int &$totalPassed, int &$totalFailed): void
    {
        $this->log("\n🔹 [GOLANG] Menjalankan Unit Test Native...", self::COLOR_BLUE);

        $targetPath = "./src/GoModules/...";
        if ($moduleInput) {
            $ucModule = ucfirst($moduleInput);
            $targetPath = "./src/GoModules/{$ucModule}/...";
        }

        $command = "cd " . escapeshellarg($baseDir) . " && go test -v " . escapeshellarg($targetPath) . " 2>&1";
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        $hasTest = false;
        foreach ($output as $line) {
            if (str_starts_with($line, "=== RUN")) {
                $hasTest = true;
                $totalTests++;
                echo "   " . self::COLOR_CYAN . $line . self::COLOR_RESET . PHP_EOL;
            } else if (str_starts_with($line, "--- PASS:")) {
                $totalPassed++;
                echo "   " . self::COLOR_GREEN . "✔ " . trim($line) . self::COLOR_RESET . PHP_EOL;
            } else if (str_starts_with($line, "--- FAIL:")) {
                $totalFailed++;
                echo "   " . self::COLOR_RED . "✘ " . trim($line) . self::COLOR_RESET . PHP_EOL;
            } else if (str_contains($line, "[no test files]")) {
                echo "   " . self::COLOR_YELLOW . trim($line) . self::COLOR_RESET . PHP_EOL;
            } else if ($returnVar !== 0 && (str_contains($line, "FAIL") || str_contains($line, "cannot find package"))) {
                echo "   " . self::COLOR_RED . trim($line) . self::COLOR_RESET . PHP_EOL;
            }
        }

        if (!$hasTest && $returnVar === 0) {
            $this->log("   💡 Golang: Tidak ada file test (*_test.go) yang terdaftar di path target.", self::COLOR_YELLOW);
        } else if ($returnVar !== 0 && !$hasTest) {
            $totalFailed++;
            $this->log("   ✘ Gagal menjalankan test Golang: Path modul tidak ditemukan atau compile error.", self::COLOR_RED);
        }
    }

    private function runPhpTests(string $baseDir, ?string $moduleInput, int &$totalTests, int &$totalPassed, int &$totalFailed): void
    {
        $this->log("\n🔹 [PHP] Menjalankan Unit Test...", self::COLOR_BLUE);

        $testFiles = [];
        $modulesDir = $baseDir . '/src/Modules';

        if ($moduleInput) {
            $ucModule = ucfirst($moduleInput);
            $moduleTestDir = $modulesDir . '/' . $ucModule . '/Tests';
            if (is_dir($moduleTestDir)) {
                foreach (scandir($moduleTestDir) as $f) {
                    if (str_ends_with($f, 'Test.php')) {
                        $testFiles[] = $moduleTestDir . '/' . $f;
                    }
                }
            }
        } else if (is_dir($modulesDir)) {
            foreach (scandir($modulesDir) as $moduleName) {
                if ($moduleName === '.' || $moduleName === '..' || !is_dir($modulesDir . '/' . $moduleName)) {
                    continue;
                }
                $moduleTestDir = $modulesDir . '/' . $moduleName . '/Tests';
                if (is_dir($moduleTestDir)) {
                    foreach (scandir($moduleTestDir) as $f) {
                        if (str_ends_with($f, 'Test.php')) {
                            $testFiles[] = $moduleTestDir . '/' . $f;
                        }
                    }
                }
            }
        }

        if (empty($testFiles)) {
            $this->log("   💡 PHP: Tidak ada file test (*Test.php) di src/Modules/*/Tests.", self::COLOR_YELLOW);
            return;
        }

        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile);
            $namespace = '';
            if (preg_match('/namespace\s+([^;]+);/', $content, $m)) {
                $namespace = trim($m[1]);
            }
            if (preg_match('/class\s+([A-Za-z0-9_]+)/', $content, $m)) {
                $className = trim($m[1]);
                $testClass = $namespace ? $namespace . '\\' . $className : $className;
            } else {
                continue;
            }

            require_once $testFile;
            if (!class_exists($testClass)) {
                continue;
            }

            $ref = new ReflectionClass($testClass);
            if ($ref->isAbstract()) {
                continue;
            }

            $this->log("   • Menjalankan suite: " . $ref->getShortName(), self::COLOR_CYAN);

            $instance = $ref->newInstance();
            $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (str_starts_with($method->getName(), 'test')) {
                    $totalTests++;
                    $testName = $method->getName();
                    try {
                        $method->invoke($instance);
                        $totalPassed++;
                        echo "     " . self::COLOR_GREEN . "✔ PASS: {$testName}()" . self::COLOR_RESET . PHP_EOL;
                    } catch (\Throwable $e) {
                        $totalFailed++;
                        echo "     " . self::COLOR_RED . "✘ FAIL: {$testName}() - " . $e->getMessage() . self::COLOR_RESET . PHP_EOL;
                    }
                }
            }
        }
    }
}
