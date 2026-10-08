<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class DeleteActionCommand extends BaseCommand
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
        $actionInput = $params[1] ?? null;

        if (!$moduleInput || !$actionInput) {
            $this->tampilkanError("Parameter kurang.\nGunakan:\n  • Hapus Action PHP    : php abiesoft delete:action <module> <action>\n  • Hapus Action Golang : php abiesoft delete:action <module> <action> --go");
            return;
        }

        $namaModule = ucfirst($moduleInput);

        if ($isGo) {
            $actionLower = strtolower($actionInput);
            $path = dirname(__DIR__, 3) . "/src/GoModules/{$namaModule}/Actions/{$actionLower}_action.go";
            if (!file_exists($path)) {
                $pathAlt = dirname(__DIR__, 3) . "/src/GoModules/{$namaModule}/Actions/{$actionLower}.go";
                if (file_exists($pathAlt)) {
                    $path = $pathAlt;
                }
            }
            $this->hapusFileDenganConfirm($path);
        } else {
            $namaAction = ucfirst($actionInput);
            $namaClass  = "{$namaAction}{$namaModule}Action";
            $path       = dirname(__DIR__, 3) . "/src/Modules/{$namaModule}/Actions/{$namaClass}.php";
            $this->hapusFileDenganConfirm($path);
        }
    }
}