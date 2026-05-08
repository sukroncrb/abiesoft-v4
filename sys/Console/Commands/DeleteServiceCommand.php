<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class DeleteServiceCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $moduleInput = $args[2] ?? null;
        $namaInput   = $args[3] ?? null;

        if (!$moduleInput || !$namaInput) {
            $this->tampilkanError("Parameter kurang.\nGunakan: php abiesoft delete:service <module> <nama>");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        $nama       = ucfirst($namaInput);

        $namaClass  = "{$nama}Repository";

        $path = dirname(__DIR__, 3) . "/src/Modules/$namaModule/Services/$namaClass.php";

        $this->hapusFileDenganConfirm($path);
    }
}