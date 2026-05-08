<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class DeleteActionCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $moduleInput = $args[2] ?? null;
        $actionInput = $args[3] ?? null;

        if (!$moduleInput || !$actionInput) {
            $this->tampilkanError("Parameter kurang.\nGunakan: php abiesoft delete:action <module> <action>");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        $namaAction= ucfirst($actionInput);
        
        $namaClass  = "{$namaAction}{$namaModule}Action";

        $path = dirname(__DIR__, 3) . "/src/Modules/$namaModule/Actions/$namaClass.php";

        $this->hapusFileDenganConfirm($path);
    }
}