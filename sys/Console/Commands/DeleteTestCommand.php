<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class DeleteTestCommand extends BaseCommand
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
            $this->tampilkanError("Nama module belum diisi.\nGunakan:\n  • Hapus Unit Test PHP    : php abiesoft delete:test <module> [nama]\n  • Hapus Unit Test Golang : php abiesoft delete:test <module> [nama] --go");
            return;
        }

        $namaModule  = ucfirst($moduleInput);
        $moduleLower = strtolower($moduleInput);

        if ($isGo) {
            $testName = $nameInput ? strtolower($nameInput) : $moduleLower;
            $fileName = "{$testName}_test.go";
            $path     = dirname(__DIR__, 3) . "/src/GoModules/{$namaModule}/{$fileName}";
            $this->hapusFileDenganConfirm($path);
        } else {
            $testName = $nameInput ? ucfirst($nameInput) : $namaModule;
            if (!str_ends_with($testName, 'Test')) {
                $testName .= 'Test';
            }
            $fileName = "{$testName}.php";
            $path     = dirname(__DIR__, 3) . "/src/Modules/{$namaModule}/Tests/{$fileName}";
            $this->hapusFileDenganConfirm($path);
        }
    }
}
