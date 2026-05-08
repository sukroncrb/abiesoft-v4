<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeDtoCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $moduleInput = $args[2] ?? null;
        $namaInput   = $args[3] ?? null;

        if (!$moduleInput || !$namaInput) {
            $this->tampilkanError("Parameter kurang.\nGunakan: php abiesoft make:dto <module> <nama>");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        $nama       = ucfirst($namaInput);
        $namaClass  = "{$nama}Data";

        $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Dto';
        $filePath   = $folderPath . '/' . $namaClass . '.php';

        $this->buatFolder($folderPath);

        if (file_exists($filePath)) {
            $this->tampilkanError("File $namaClass.php sudah ada!");
            return;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace Abiesoft\App\Modules\\{$namaModule}\Dto;

        readonly class {$namaClass}
        {
            public function __construct(
                // public string \$nama,
            ) {}

            public static function fromArray(array \$data): self
            {
                return new self(
                    // nama: \$data['nama'] ?? '',
                );
            }
        }
        PHP;

        $this->buatFile($filePath, $content, $namaClass);
    }
}