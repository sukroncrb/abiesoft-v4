<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeServiceCommand extends BaseCommand
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
            $this->tampilkanError("Parameter kurang.\nGunakan:\n  • PHP Service    : php abiesoft make:service <module> <nama>\n  • Golang Service : php abiesoft make:service <module> <nama> --go");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        $nama       = ucfirst($namaInput);

        if ($isGo) {
            $serviceLower = strtolower($namaInput);
            $folderPath   = dirname(__DIR__, 3) . '/src/GoModules/' . $namaModule . '/Services';
            $filePath     = $folderPath . '/' . $serviceLower . '_service.go';

            $this->buatFolder($folderPath);

            if (file_exists($filePath)) {
                $this->tampilkanError("File Go Service '{$serviceLower}_service.go' sudah ada!");
                return;
            }

            $content = <<<GOCONTENT
package services

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

// {$nama}Service menangani business logic service untuk modul {$namaModule}
func {$nama}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	// TODO: Implementasi logika service di Go Engine
	res.Status = "success"
	res.Msg = "Service {$nama} pada modul {$namaModule} berhasil dieksekusi"
	res.Data = req.Params
	return res
}
GOCONTENT;

            $this->buatFile($filePath, $content, "Go Service: {$serviceLower}_service.go");
        } else {
            $namaClass  = "{$nama}Repository";
            $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Services';
            $filePath   = $folderPath . '/' . $namaClass . '.php';

            $this->buatFolder($folderPath);

            if (file_exists($filePath)) {
                $this->tampilkanError("File $namaClass.php sudah ada!");
                return;
            }

            $content = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Services;

use Abiesoft\App\Shared\Helpers\Service;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Utilities\Input;

class {$namaClass} extends Service
{
    private \$db;

    public function __construct()
    {
        \$this->db = (new DB)->terhubung();
    }

    public function getAll(): void
    {
        // Menampilkan semua data
    }

    public function getOnly(): void
    {
        // Menampilkan 1 data
    }

    public function post(): void
    {
        \$input = new Input();
        if (\$input->get("__method") == "DELETE") {
            \$this->drop();
        } else {
            if (\$input->get("id") != "" || \$input->get("uuid") != "") {
                \$this->replace();
            } else {
                \$this->keep();
            }
        }
    }

    protected function keep(): void
    {
        // Menambah data
    }

    protected function replace(): void
    {
        // Memperbarui data
    }

    protected function drop(): void
    {
        // Menghapus data
    }
}
PHP;

            $this->buatFile($filePath, $content, $namaClass);
        }
    }
}