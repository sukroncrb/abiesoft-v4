<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class DeleteDtoCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $moduleInput = $args[2] ?? null;
        $namaInput   = $args[3] ?? null;

        if (!$moduleInput || !$namaInput) {
            $this->tampilkanError("Parameter kurang.\nGunakan: php abiesoft delete:dto <module> <nama>");
            return;
        }

        $namaModul = ucfirst($moduleInput);
        $nama       = ucfirst($namaInput);
        
        $namaClass  = "{$nama}Data";

        $path = dirname(__DIR__, 3) . "/src/Modules/$namaModul/Dto/$namaClass.php";

        $this->hapusFileDenganConfirm($path);
    }
}