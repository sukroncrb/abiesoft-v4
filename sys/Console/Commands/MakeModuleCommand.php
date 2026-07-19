<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeModuleCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $moduleInput = $args[2] ?? null;

        if (!$moduleInput) {
            $this->tampilkanError("Nama module belum diisi.\nGunakan: php abiesoft make:module <nama_module> [--with-go]");
            return;
        }

        $name = strtolower($moduleInput);
        $namaModule = ucfirst($moduleInput);
        
        $this->log("\n🛠️  Memulai Setup Module: $namaModule", self::COLOR_CYAN);
        $this->log("Silakan definisikan field database untuk DTO & Service.", self::COLOR_YELLOW);
        $this->log("Tekan [ENTER] kosong untuk selesai dan men-generate file.\n", self::COLOR_YELLOW);

        $semuaKolom = [];
        
        while (true) {
            echo self::COLOR_GREEN . "👉 Nama Kolom (misal: nama): " . self::COLOR_RESET;
            $namaKolom = trim((string)fgets(STDIN));

            if (empty($namaKolom)) {
                break;
            }

            echo "   Tipe Data (contoh : string, text, longtext, datetime, angka, enum) (default: string): ";
            $tipeKolom = trim((string)fgets(STDIN));

            if (empty($tipeKolom)) {
                $tipeKolom = 'string';
                $tipedata = 'VARCHAR(255)';
            } else if ($tipeKolom == 'text') {
                $tipeKolom = 'string';
                $tipedata = 'TEXT';
            } else if ($tipeKolom == 'longtext') {
                $tipeKolom = 'string';
                $tipedata = 'LONGTEXT';
            } else if ($tipeKolom == 'datetime') {
                $tipeKolom = 'string';
                $tipedata = 'DATETIME';
            } else if ($tipeKolom == 'angka') {
                $tipeKolom = 'int';
                $tipedata = 'INT(11)';
            } else if ($tipeKolom === 'enum') {
                echo self::COLOR_CYAN . "💡 Masukkan pilihan ENUM (pisahkan dengan koma, contoh: aktif,nonaktif): " . self::COLOR_RESET;
                $enumInput = trim(fgets(STDIN));
                $optionsArray = explode(',', $enumInput);
                $formattedOptions = array_map(function($val) {
                    return "'" . trim($val) . "'";
                }, $optionsArray);
                $enumString = implode(', ', $formattedOptions);
                $tipeKolom = 'string';
                $tipedata = "ENUM($enumString) DEFAULT " . $formattedOptions[0];
            } else {
                $tipeKolom = 'string';
                $tipedata = 'VARCHAR(255)';
            }

            $semuaKolom[] = [
                'nama' => $namaKolom,
                'tipe' => $tipeKolom,
                'tipedata' => $tipedata
            ];
            
            echo self::COLOR_CYAN . "   ✓ Disimpan.\n" . self::COLOR_RESET;
        }

        $namatabel = strtolower($namaModule);
        $sql = "CREATE TABLE IF NOT EXISTS {$namatabel} (\n";
        $sql .= "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
        $sql .= "    uuid VARCHAR(36) NOT NULL,\n";

        foreach ($semuaKolom as $kolom) {
            $sql .= "    {$kolom['nama']} {$kolom['tipedata']},\n";
        }

        $sql .= "    dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "    diedit TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        $sql .= "    dihapus TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        $sql .= "    INDEX (uuid)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";

        $timestamp = date('Y_m_d_His');
        $namaSchema = "{$timestamp}_create_{$namatabel}_table.sql";

        $folderSchema = dirname(__DIR__, 3) . '/database/schemas';
        if (!is_dir($folderSchema)) {
            mkdir($folderSchema, 0755, true); 
        }

        $schemaPath = $folderSchema . '/' . $namaSchema;
        file_put_contents($schemaPath, $sql);

        $this->log("✅ File Schema berhasil dibuat: database/schemas/{$namaSchema}", self::COLOR_GREEN);
        $this->log("💡 Jangan lupa jalankan: php abiesoft database:import", self::COLOR_YELLOW);

        
        $withGo = false;
        if (isset($args[3]) && strtolower($args[3]) === '--with-go') {
            $withGo = true;
        }

        $this->log("\n🔄 Sedang men-generate file PHP...", self::COLOR_BLUE);

        $this->generateDto($namaModule, $semuaKolom);
        
        
        $this->generateService($namaModule, $semuaKolom, $withGo);
        
        $this->generateActions($namaModule, $moduleInput);

        if ($withGo) {
            $this->log("\n🔄 Sedang men-generate file Go Engine ke dalam modul {$namaModule}...", self::COLOR_BLUE);
            $this->createGoModuleStructure($name, $namaModule, $semuaKolom);
            
            $this->log("\n💡 [PETUNJUK INTEGRASI HANDLER.GO]", self::COLOR_YELLOW);
            $this->log("Buka berkas: src/Modules/handler.go", self::COLOR_CYAN);
            $this->log("1. Tambahkan alias import di bagian atas:", self::COLOR_RESET);
            echo self::COLOR_GREEN . "   " . $name . "Actions \"abiesoft/src/GoModules/" . $namaModule . "/Actions\"\n" . self::COLOR_RESET;
            
            $this->log("2. Tambahkan kondisi routing ini di dalam func HandleRequest:", self::COLOR_RESET);
            echo self::COLOR_GREEN . "   if strings.HasPrefix(req.Action, \"" . $name . "-\") || strings.HasSuffix(req.Action, \"-" . $name . "\") {\n";
            echo "       return " . $name . "Actions.Handle" . $namaModule . "Action(req, db)\n";
            echo "   }\n\n" . self::COLOR_RESET;
        } else {
            $this->log("\n💡 Info: Pembuatan modul Go dilewati (Flag --with-go tidak digunakan).", self::COLOR_YELLOW);
        }

        $this->log("✨ Module $namaModule selesai dibuat!", self::COLOR_GREEN);
    }

    private function generateDto(string $namaModule, array $semuaKolom): void
    {
        $className = "{$namaModule}Data";
        $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Dto';
        $filePath   = $folderPath . '/' . $className . '.php';

        $this->buatFolder($folderPath);

        $properties = "";
        $mapping = "";

        foreach ($semuaKolom as $kolom) {
            $nama = $kolom['nama'];
            $tipe = $kolom['tipe'];
            
            $properties .= "        public {$tipe} \${$nama},\n";

            $cast = match($tipe) {
                'int' => '(int)',
                'float' => '(float)',
                'bool' => '(bool)',
                default => ''
            };
            
            $mapping .= "            {$nama}: {$cast}\$input->get('{$nama}') ?? null,\n";
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Dto;

use Abiesoft\System\Utilities\Input;

readonly class {$className}
{
    public function __construct(
        public ?int \$id,
{$properties}
    ) {}

    public static function fromArray(): self
    {
        \$input = new Input();
        return new self(
            id: \$input->get('id') ? (int)\$input->get('id') : null,
{$mapping}
        );
    }
}
PHP;
        $this->buatFile($filePath, $content, $className);
    }

    private function generateService(string $namaModule, array $semuaKolom, bool $withGo): void
    {
        $className = "{$namaModule}Repository";
        $dtoClass  = "{$namaModule}Data";
        $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Services';
        $filePath   = $folderPath . '/' . $className . '.php';
        $tableName  = strtolower($namaModule);

        $this->buatFolder($folderPath);

        if ($withGo) {
            
            
            $paramBinding = "";
            foreach ($semuaKolom as $kolom) {
                $nama = $kolom['nama'];
                
                $paramBinding .= "                    '{$nama}' => \${$nama},\n";
            }

            $content = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Services;

use Abiesoft\App\Modules\\{$namaModule}\Dto\\{$dtoClass};
use Abiesoft\App\Shared\Helpers\Service;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Utilities\Input;

class {$className} extends Service
{
    private \$db;

    public function __construct()
    {
        \$this->db = (new DB)->terhubung();
    }

    public function getAll()
    {
        \$result = \$this->call("{$tableName}-all-data", ['init' => '1']);
        
        if (isset(\$result['status']) && \$result['status'] === "error") {
            \$this->badrequest(\$result['msg'] ?? "Gagal memproses data via Go Engine");
            return;
        }

        \$this->success(\$result['data'] ?? []);
    }

    public function post({$dtoClass} \$dto)
    {
        \$input = new Input();
        \$id = \$input->get('id');
        \$method = \$input->get('__method');

        // Tangkap variabel dinamis dari objek Dto input
PHP;
            foreach ($semuaKolom as $kolom) {
                $content .= "\n        \${$kolom['nama']} = \$dto->{$kolom['nama']};";
            }

            $content .= <<<PHP


        if (\$id != "") {
            if (\$method == "DELETE") {
                \$result = \$this->call("delete-{$tableName}", [
                    'id' => \$id,
                ]);
            } else {
                \$result = \$this->call("update-{$tableName}", [
                    'id' => \$id,
{$paramBinding}                ]);
            }
        } else {
            \$result = \$this->call("post-{$tableName}", [
{$paramBinding}            ]);
        }

        if (isset(\$result['status']) && \$result['status'] === "error") {
            \$this->badrequest(\$result['msg'] ?? "Gagal menyimpan data via Go Engine");
            return;
        }

        \$this->success(\$result['data'] ?? "Proses Go Engine Berhasil");
    }
}
PHP;

        } else {
            
            
            
            $insertData = "";
            foreach ($semuaKolom as $kolom) {
                $nama = $kolom['nama'];
                $insertData .= "            '{$nama}' => \$dto->{$nama},\n";
            }

            $content = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Services;

use Abiesoft\App\Modules\\{$namaModule}\Dto\\{$dtoClass};
use Abiesoft\App\Shared\Helpers\Service;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Utilities\Input;

class {$className} extends Service
{
    private \$db;

    public function __construct()
    {
        \$this->db = (new DB)->terhubung();
    }

    public function getAll()
    {
        // Menampilkan semua data PHP lokal murni MySQL
    }

    public function getOnly()
    {
        // Menampilkan 1 data PHP lokal murni MySQL
    }

    public function post({$dtoClass} \$dto)
    {
        \$input = new Input();
        if(\$input->get("__method") == "DELETE"){
            \$this->drop();
        }else{
            if(\$input->get("id") != "" || \$input->get("uuid") != ""){
                \$this->replace();
            }else{
                \$this->keep(\$dto);
            }
        }
    }

    protected function keep({$dtoClass} \$dto)
    {
        return \$this->db->input('{$tableName}', [
{$insertData}
        ]);
    }

    protected function replace()
    {
        // Memperbarui data PHP lokal
    }

    protected function drop()
    {
        // Menghapus data PHP lokal
    }
}
PHP;
        }

        $this->buatFile($filePath, $content, $className);
    }

    private function generateActions(string $namaModule, string $moduleInput): void
    {
        $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Actions';
        $this->buatFolder($folderPath);

        $classIndex = "Index{$namaModule}Action";
        $classRepo = "{$namaModule}Repository";
        
        
        $indexContent = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Actions;

use Abiesoft\System\View\ViewRenderer;

readonly class {$classIndex}
{
    public function __invoke(ViewRenderer \$view): void
    {
        \$view->render('pages/{$moduleInput}/index', [
            'title' => 'List {$namaModule}'
        ]);
    }
}
PHP;
        $this->buatFile($folderPath . '/' . $classIndex . '.php', $indexContent, $classIndex);

        
        $storeClass = "Post{$namaModule}Action";
        $dtoClass = "{$namaModule}Data";
        
        $storeContent = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Actions;

use Abiesoft\App\Modules\\{$namaModule}\Services\\{$classRepo};
use Abiesoft\App\Modules\\{$namaModule}\Dto\\{$dtoClass};

readonly class {$storeClass}
{
    public function __invoke(): void
    {
        \$repo = new {$classRepo}();
        \$dto = {$dtoClass}::fromArray(); // Berhasil Diperbaiki: Kurung kurawal pembuka ditambahkan
        \$repo->post(\$dto);
        exit;
    }
}
PHP;
        $this->buatFile($folderPath . '/' . $storeClass . '.php', $storeContent, $storeClass);

        
        $classGet = "Get{$namaModule}Action";
        $getContent = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Actions;

use Abiesoft\App\Modules\\{$namaModule}\Services\\{$classRepo};

readonly class {$classGet}
{
    public function __invoke(): void
    {
        \$repo = new {$classRepo}();
        \$repo->getAll();
        exit;
    }
}
PHP;
        $this->buatFile($folderPath . '/' . $classGet . '.php', $getContent, $classGet);
        $this->log("   ✔ Berkas PHP Action: src/Modules/{$namaModule}/Actions/{$classGet}.php dibuat.", self::COLOR_GREEN);
    }

    
    private function createGoModuleStructure(string $name, string $ucName, array $semuaKolom): void
    {
        $baseGoDir = dirname(__DIR__, 3) . "/src/GoModules/{$ucName}/";
        $subDirs = ['Actions', 'Dto', 'Services'];

        foreach ($subDirs as $dir) {
            $path = $baseGoDir . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
                $this->log("   ✔ Folder Go: src/GoModules/{$ucName}/{$dir} dibuat.", self::COLOR_GREEN);
            }
        }

        $dtoFile = $baseGoDir . "Dto/{$name}_dto.go";
        if (!file_exists($dtoFile)) {
            $properties = "";
            foreach ($semuaKolom as $kolom) {
                $namaCamel = ucfirst($kolom['nama']);
                $properties .= "\t{$namaCamel} {$kolom['tipe']} `json:\"{$kolom['nama']}\"`\n";
            }

            $dtoTemplate = <<<GODTO
package dto

type {$ucName}Dto struct {
	ID   interface{} `json:"id"`
	Uuid string      `json:"uuid"`
{$properties}}
GODTO;
            file_put_contents($dtoFile, $dtoTemplate);
            $this->log("   ✔ Berkas Go DTO: src/GoModules/{$ucName}/Dto/{$name}_dto.go selesai.", self::COLOR_GREEN);
        }

        
        $serviceFile = $baseGoDir . "Services/{$name}_service.go";
        if (!file_exists($serviceFile)) {
            $scanFields = "&d.ID, &d.Uuid";
            $selectFields = "id, uuid";
            $insertColumns = "uuid";
            $insertPlaceholders = "?";
            $insertArgs = "uuid";
            $updateSet = "";
            $updateArgs = "";

            foreach ($semuaKolom as $kolom) {
                $namaCamel = ucfirst($kolom['nama']);
                $selectFields .= ", " . $kolom['nama'];
                $scanFields .= ", &d." . $namaCamel;
                
                $insertColumns .= ", " . $kolom['nama'];
                $insertPlaceholders .= ", ?";
                $insertArgs .= ", " . $kolom['nama'];

                $updateSet .= $kolom['nama'] . " = ?, ";
                $updateArgs .= $kolom['nama'] . ", ";
            }
            $updateSet = rtrim($updateSet, ", ");

            $serviceTemplate = <<<GOCONTENT
package services

import (
	dto "abiesoft/src/GoModules/{$ucName}/Dto"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

func GetAll{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	rows, err := db.Query("SELECT {$selectFields} FROM {$name} ORDER BY id DESC")
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal Query: " + err.Error()
		return res
	}
	defer rows.Close()

	list := []dto.{$ucName}Dto{}
	for rows.Next() {
		var d dto.{$ucName}Dto
		if err := rows.Scan({$scanFields}); err != nil {
			res.Status = "error"
			res.Msg = "Scan error: " + err.Error()
			return res
		}
		list = append(list, d)
	}

	res.Status = "success"
	res.Msg = "Data retrieved successfully"
	res.Data = list
	return res
}

func Create{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	uuid := req.Params["uuid"]
GOCONTENT;

            foreach ($semuaKolom as $kolom) {
                $serviceTemplate .= "\n\t{$kolom['nama']} := req.Params[\"{$kolom['nama']}\"]";
            }

            $serviceTemplate .= <<<GOCONTENT


	query := "INSERT INTO {$name} ({$insertColumns}) VALUES ({$insertPlaceholders})"
	_, err := db.Exec(query, {$insertArgs})
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal menyimpan data: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil disimpan oleh Go Engine"
	return res
}

func Update{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	id := req.Params["id"]
GOCONTENT;

            foreach ($semuaKolom as $kolom) {
                $serviceTemplate .= "\n\t{$kolom['nama']} := req.Params[\"{$kolom['nama']}\"]";
            }

            $serviceTemplate .= <<<GOCONTENT


	query := "UPDATE {$name} SET {$updateSet} WHERE id = ?"
	_, err := db.Exec(query, {$updateArgs}id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal memperbarui data: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil diperbarui"
	return res
}

func Delete{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	id := req.Params["id"]

	query := "DELETE FROM {$name} WHERE id = ?"
	_, err := db.Exec(query, id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal menghapus data: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil dihapus"
	return res
}
GOCONTENT;

            file_put_contents($serviceFile, $serviceTemplate);
            $this->log("   ✔ Berkas Go Service: src/GoModules/{$ucName}/Services/{$name}_service.go selesai.", self::COLOR_GREEN);
        }

        
        $actionFile = $baseGoDir . "Actions/{$name}_action.go";
        if (!file_exists($actionFile)) {
            $actionTemplate = <<<GOACTION
package actions

import (
	services "abiesoft/src/GoModules/{$ucName}/Services"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"database/sql"
)

func Handle{$ucName}Action(req shared.PiGoRequest, db *sql.DB) shared.PiGoResponse {
	var res shared.PiGoResponse

	switch req.Action {
	case "{$name}-all-data":
		return services.GetAll{$ucName}Service(res, db, req)
	case "post-{$name}":
		return services.Create{$ucName}Service(res, db, req)
	case "update-{$name}":
		return services.Update{$ucName}Service(res, db, req)
	case "delete-{$name}":
		return services.Delete{$ucName}Service(res, db, req)
	default:
		res.Status = "error"
		res.Msg = "Action di dalam modul {$ucName} tidak ditemukan"
	}

	return res
}
GOACTION;
            file_put_contents($actionFile, $actionTemplate);
            $this->log("   ✔ Berkas Go Action: src/GoModules/{$ucName}/Actions/{$name}_action.go selesai.", self::COLOR_GREEN);
        }
    }
}