<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class DeleteServiceCommand extends BaseCommand
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
        $namaInput   = $params[1] ?? null;

        if (!$moduleInput || !$namaInput) {
            $this->tampilkanError("Parameter kurang.\nGunakan:\n  • Hapus Service PHP    : php abiesoft delete:service <module> <nama>\n  • Hapus Service Golang : php abiesoft delete:service <module> <nama> --go");
            return;
        }

        $namaModule = ucfirst($moduleInput);

        if ($isGo) {
            $serviceLower = strtolower($namaInput);
            $path = dirname(__DIR__, 3) . "/src/GoModules/{$namaModule}/Services/{$serviceLower}_service.go";
            $this->hapusFileDenganConfirm($path);
        } else {
            $nama      = ucfirst($namaInput);
            $namaClass = "{$nama}Repository";
            $path      = dirname(__DIR__, 3) . "/src/Modules/{$namaModule}/Services/{$namaClass}.php";
            $this->hapusFileDenganConfirm($path);
        }
    }
}