<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class DeleteDtoCommand extends BaseCommand
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
            $this->tampilkanError("Parameter kurang.\nGunakan:\n  • Hapus DTO PHP    : php abiesoft delete:dto <module> <nama>\n  • Hapus DTO Golang : php abiesoft delete:dto <module> <nama> --go");
            return;
        }

        $namaModule = ucfirst($moduleInput);

        if ($isGo) {
            $dtoLower = strtolower($namaInput);
            $path     = dirname(__DIR__, 3) . "/src/GoModules/{$namaModule}/Dto/{$dtoLower}_dto.go";
            $this->hapusFileDenganConfirm($path);
        } else {
            $nama      = ucfirst($namaInput);
            $namaClass = "{$nama}Data";
            $path      = dirname(__DIR__, 3) . "/src/Modules/{$namaModule}/Dto/{$namaClass}.php";
            $this->hapusFileDenganConfirm($path);
        }
    }
}