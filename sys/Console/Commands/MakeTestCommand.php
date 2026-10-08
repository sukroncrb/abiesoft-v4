<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeTestCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $isGo = false;
        $params = [];

        for ($i = 2; $i < count($args); $i++) {
            $arg = trim($args[$i]);
            if (in_array(strtolower($arg), ['--go', '-go', '--golang', '-g'])) {
                $isGo = true;
            } else if (!str_starts_with($arg, '-')) {
                $params[] = $arg;
            }
        }

        $moduleInput = $params[0] ?? null;
        $nameInput   = $params[1] ?? null;

        if (!$moduleInput) {
            $this->tampilkanError("Nama module belum diisi.\nGunakan:\n  • Buat Unit Test PHP    : php abiesoft make:test <module> [nama]\n  • Buat Unit Test Golang : php abiesoft make:test <module> [nama] --go");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        $moduleLower = strtolower($moduleInput);

        if ($isGo) {
            $testName = $nameInput ? strtolower($nameInput) : $moduleLower;
            $fileName = "{$testName}_test.go";
            $folderPath = dirname(__DIR__, 3) . "/src/GoModules/{$namaModule}";
            $filePath = $folderPath . "/{$fileName}";

            $this->buatFolder($folderPath);

            if (file_exists($filePath)) {
                $this->tampilkanError("File Go Test '{$fileName}' sudah ada di src/GoModules/{$namaModule}/!");
                return;
            }

            $funcPrefix = ucfirst($testName);

            $content = <<<GOCONTENT
package {$moduleLower}_test

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"testing"
)

func Test{$funcPrefix}Example(t *testing.T) {
	expected := "{$namaModule}"
	actual := "{$namaModule}"

	if actual != expected {
		t.Errorf("Expected '%s', but got '%s'", expected, actual)
	}
}

func Test{$funcPrefix}RequestParams(t *testing.T) {
	req := shared.PiGoRequest{
		Action: "{$moduleLower}-test",
		Params: map[string]string{
			"module": "{$namaModule}",
		},
	}

	if req.Params["module"] != "{$namaModule}" {
		t.Errorf("Expected module '{$namaModule}', but got '%s'", req.Params["module"])
	}
}
GOCONTENT;

            $this->buatFile($filePath, $content, "Go Test: {$fileName}");
            $this->log("💡 Jalankan test ini dengan:", self::COLOR_YELLOW);
            echo "   php abiesoft test {$moduleLower} --go\n\n";
        } else {
            $testName = $nameInput ? ucfirst($nameInput) : $namaModule;
            if (!str_ends_with($testName, 'Test')) {
                $testName .= 'Test';
            }

            $folderPath = dirname(__DIR__, 3) . "/src/Modules/{$namaModule}/Tests";
            $filePath = $folderPath . "/{$testName}.php";

            $this->buatFolder($folderPath);

            if (file_exists($filePath)) {
                $this->tampilkanError("File PHP Test '{$testName}.php' sudah ada!");
                return;
            }

            $content = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Tests;

use Abiesoft\System\Testing\TestCase;

class {$testName} extends TestCase
{
    public function testExample(): void
    {
        \$this->assertTrue(true, "Contoh assertion bernilai true");
    }

    public function testDataProcessing(): void
    {
        \$data = [
            'module' => '{$namaModule}',
            'status' => 'active'
        ];

        \$this->assertArrayHasKey('module', \$data);
        \$this->assertEquals('{$namaModule}', \$data['module']);
        \$this->assertNotEquals('inactive', \$data['status']);
    }
}
PHP;

            $this->buatFile($filePath, $content, $testName);
            $this->log("💡 Jalankan test ini dengan:", self::COLOR_YELLOW);
            echo "   php abiesoft test {$moduleLower}\n\n";
        }
    }
}
