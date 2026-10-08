<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeDtoCommand extends BaseCommand
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
            $this->tampilkanError("Parameter kurang.\nGunakan:\n  • PHP DTO    : php abiesoft make:dto <module> <nama>\n  • Golang DTO : php abiesoft make:dto <module> <nama> --go");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        $nama       = ucfirst($namaInput);

        if ($isGo) {
            $dtoLower   = strtolower($namaInput);
            $folderPath = dirname(__DIR__, 3) . '/src/GoModules/' . $namaModule . '/Dto';
            $filePath   = $folderPath . '/' . $dtoLower . '_dto.go';

            $this->buatFolder($folderPath);

            if (file_exists($filePath)) {
                $this->tampilkanError("File Go DTO '{$dtoLower}_dto.go' sudah ada!");
                return;
            }

            $content = <<<GOCONTENT
package dto

// {$nama}Dto merepresentasikan Data Transfer Object untuk modul {$namaModule}
type {$nama}Dto struct {
	ID   int    `json:"id"`
	Uuid string `json:"uuid"`
	// Tambahkan field lain sesuai kebutuhan di sini
}
GOCONTENT;

            $this->buatFile($filePath, $content, "Go DTO: {$dtoLower}_dto.go");
        } else {
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
}