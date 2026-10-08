<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

use Abiesoft\System\Console\Commands\Utilities\Compile;

class MakeModuleCommand extends BaseCommand
{
    use Compile;

    public function handle(array $args): void
    {
        $isGo = false;
        $moduleInput = null;

        for ($i = 2; $i < count($args); $i++) {
            $arg = trim($args[$i]);
            if (in_array(strtolower($arg), ['--go', '-go', '--golang', '-g'])) {
                $isGo = true;
            } else if (!str_starts_with($arg, '-')) {
                $moduleInput = $arg;
            }
        }

        if (!$moduleInput) {
            $this->tampilkanError("Nama module belum diisi.\nGunakan:\n  • PHP Module    : php abiesoft make:module <nama_module>\n  • Golang Module : php abiesoft make:module <nama_module> --go");
            return;
        }

        $name = strtolower($moduleInput);
        $namaModule = ucfirst($moduleInput);
        $modeLabel = $isGo ? "Golang Native Module (--go)" : "PHP Core Module";

        $this->log("\n🛠️  Memulai Setup Module: {$namaModule} [{$modeLabel}]", self::COLOR_CYAN);
        $this->log("Silakan definisikan field database (atau tekan [ENTER] kosong jika selesai/menggunakan default).\n", self::COLOR_YELLOW);

        $semuaKolom = [];

        // Deteksi apakah stdin interaktif
        $isInteractive = posix_isatty(STDIN);

        while (true) {
            if ($isInteractive) {
                echo self::COLOR_GREEN . "👉 Nama Kolom (misal: nama): " . self::COLOR_RESET;
            }
            $line = fgets(STDIN);
            if ($line === false) {
                break;
            }
            $namaKolom = trim((string)$line);

            if (empty($namaKolom)) {
                break;
            }

            if ($isInteractive) {
                echo "   Tipe Data (contoh: string, text, longtext, datetime, angka, enum) (default: string): ";
            }
            $tipeLine = fgets(STDIN);
            $tipeKolom = ($tipeLine !== false) ? trim((string)$tipeLine) : '';

            if (empty($tipeKolom)) {
                $tipeKolom = 'string';
                $tipedata = 'VARCHAR(255)';
            } else if ($tipeKolom === 'text') {
                $tipeKolom = 'string';
                $tipedata = 'TEXT';
            } else if ($tipeKolom === 'longtext') {
                $tipeKolom = 'string';
                $tipedata = 'LONGTEXT';
            } else if ($tipeKolom === 'datetime') {
                $tipeKolom = 'string';
                $tipedata = 'DATETIME';
            } else if ($tipeKolom === 'angka') {
                $tipeKolom = 'int';
                $tipedata = 'INT(11)';
            } else if ($tipeKolom === 'enum') {
                if ($isInteractive) {
                    echo self::COLOR_CYAN . "💡 Masukkan pilihan ENUM (pisahkan dengan koma, contoh: aktif,nonaktif): " . self::COLOR_RESET;
                }
                $enumLine = fgets(STDIN);
                $enumInput = ($enumLine !== false) ? trim((string)$enumLine) : 'aktif,nonaktif';
                $optionsArray = explode(',', $enumInput);
                $formattedOptions = array_map(function($val) {
                    return "'" . trim($val) . "'";
                }, $optionsArray);
                $enumString = implode(', ', $formattedOptions);
                $tipeKolom = 'string';
                $tipedata = "ENUM($enumString) DEFAULT " . ($formattedOptions[0] ?? "'aktif'");
            } else {
                $tipeKolom = 'string';
                $tipedata = 'VARCHAR(255)';
            }

            $semuaKolom[] = [
                'nama' => $namaKolom,
                'tipe' => $tipeKolom,
                'tipedata' => $tipedata
            ];

            if ($isInteractive) {
                echo self::COLOR_CYAN . "   ✓ Disimpan.\n" . self::COLOR_RESET;
            }
        }

        // Jika tidak ada kolom yang diinput, berikan kolom default 'nama'
        if (empty($semuaKolom)) {
            $semuaKolom[] = [
                'nama' => 'nama',
                'tipe' => 'string',
                'tipedata' => 'VARCHAR(255)'
            ];
        }

        // 1. Buat Schema SQL Database
        $this->generateSchema($namaModule, $semuaKolom);

        if ($isGo) {
            // ==========================================
            // ALUR GENERATE GOLANG MODULE (--go)
            // ==========================================
            $this->log("\n🔄 Men-generate modul Golang di src/GoModules/{$namaModule}...", self::COLOR_BLUE);

            $this->generateGoDto($name, $namaModule, $semuaKolom);
            $this->generateGoService($name, $namaModule, $semuaKolom);
            $this->generateGoAction($name, $namaModule, $semuaKolom);
            $this->generateGoTest($name, $namaModule);

            $this->injectGoRoutes($name, $namaModule);

            $this->log("\n⚙️  Melakukan kompilasi ulang Go Engine otomatis...", self::COLOR_BLUE);
            $this->compileGo();

            $this->log("\n✨ Golang Module '{$namaModule}' selesai dibuat dan aktif!", self::COLOR_GREEN);
            $this->log("📡 Rute Direct Golang yang tersedia:", self::COLOR_CYAN);
            echo "   • GET    http://127.0.0.1:3000/api-go/{$name}\n";
            echo "   • GET    http://127.0.0.1:3000/api-go/{$name}/{id}\n";
            echo "   • GET    http://127.0.0.1:3000/api-go/{$name}/{offset}/{limit}\n";
            echo "   • POST   http://127.0.0.1:3000/api-go/{$name}\n";
            echo "   • PUT    http://127.0.0.1:3000/api-go/{$name}/{id}\n";
            echo "   • DELETE http://127.0.0.1:3000/api-go/{$name}/{id}\n\n";
        } else {
            // ==========================================
            // ALUR GENERATE PHP CORE MODULE (Default)
            // ==========================================
            $this->log("\n🔄 Men-generate modul PHP di src/Modules/{$namaModule}...", self::COLOR_BLUE);

            $this->generatePhpDto($namaModule, $semuaKolom);
            $this->generatePhpService($namaModule, $semuaKolom);
            $this->generatePhpActions($namaModule, $name);
            $this->generatePhpViewTemplate($name, $namaModule);
            $this->generatePhpTest($name, $namaModule);

            $this->injectPhpRoutes($name, $namaModule);

            $this->log("\n✨ PHP Module '{$namaModule}' selesai dibuat dan aktif!", self::COLOR_GREEN);
            $this->log("📡 Rute PHP Core yang tersedia:", self::COLOR_CYAN);
            echo "   • Web UI : http://127.0.0.1:3000/{$name}\n";
            echo "   • GET    : http://127.0.0.1:3000/api/{$name}\n";
            echo "   • POST   : http://127.0.0.1:3000/api/{$name}\n\n";
        }

        $this->log("💡 Jangan lupa jalankan: php abiesoft database:import untuk membuat tabel database jika belum ada.", self::COLOR_YELLOW);
    }

    private function generateSchema(string $namaModule, array $semuaKolom): void
    {
        $namatabel = strtolower($namaModule);
        $sql = "CREATE TABLE IF NOT EXISTS `{$namatabel}` (\n";
        $sql .= "    `id` INT AUTO_INCREMENT PRIMARY KEY,\n";
        $sql .= "    `uuid` VARCHAR(36) NOT NULL,\n";

        foreach ($semuaKolom as $kolom) {
            $sql .= "    `{$kolom['nama']}` {$kolom['tipedata']},\n";
        }

        $sql .= "    `dibuat` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "    `diedit` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,\n";
        $sql .= "    `dihapus` TIMESTAMP NULL DEFAULT NULL,\n";
        $sql .= "    INDEX (`uuid`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";

        $timestamp = date('Y_m_d_His');
        $namaSchema = "{$timestamp}_create_{$namatabel}_table.sql";

        $folderSchema = dirname(__DIR__, 3) . '/database/schemas';
        if (!is_dir($folderSchema)) {
            mkdir($folderSchema, 0755, true); 
        }

        $schemaPath = $folderSchema . '/' . $namaSchema;
        file_put_contents($schemaPath, $sql);

        $this->log("✅ File Schema dibuat: database/schemas/{$namaSchema}", self::COLOR_GREEN);
    }

    // =========================================================================
    // GENERATOR PHP MODULE
    // =========================================================================

    private function generatePhpDto(string $namaModule, array $semuaKolom): void
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
            $properties .= "        public ?{$tipe} \${$nama} = null,\n";

            $cast = match($tipe) {
                'int' => '(int)',
                'float' => '(float)',
                'bool' => '(bool)',
                default => ''
            };
            
            $mapping .= "            {$nama}: \$input->get('{$nama}') ? {$cast}\$input->get('{$nama}') : null,\n";
        }

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Dto;

use Abiesoft\System\Utilities\Input;

readonly class {$className}
{
    public function __construct(
        public ?int \$id = null,
        public ?string \$uuid = null,
{$properties}    ) {}

    public static function fromArray(): self
    {
        \$input = new Input();
        return new self(
            id: \$input->get('id') ? (int)\$input->get('id') : null,
            uuid: \$input->get('uuid') ? (string)\$input->get('uuid') : null,
{$mapping}        );
    }
}
PHP;
        $this->buatFile($filePath, $content, $className);
    }

    private function generatePhpService(string $namaModule, array $semuaKolom): void
    {
        $className = "{$namaModule}Repository";
        $dtoClass  = "{$namaModule}Data";
        $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Services';
        $filePath   = $folderPath . '/' . $className . '.php';
        $tableName  = strtolower($namaModule);

        $this->buatFolder($folderPath);

        $columnArray = "";
        foreach ($semuaKolom as $kolom) {
            $nama = $kolom['nama'];
            $columnArray .= "            '{$nama}' => \$dto->{$nama},\n";
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

    public function getAll(): void
    {
        \$data = \$this->db->tabel('{$tableName}')->order('id', 'DESC')->hasil();
        \$this->success(\$data ?? []);
    }

    public function getOnly(\$id): void
    {
        \$data = \$this->db->tabel('{$tableName}')->where('id', '=', \$id)->hasil();
        \$this->success(\$data ?? []);
    }

    public function post({$dtoClass} \$dto): void
    {
        \$input = new Input();
        \$id = \$input->get('id');
        \$method = strtoupper(\$input->get('__method'));

        if (\$id !== '') {
            if (\$method === 'DELETE') {
                \$hapus = \$this->db->hapus('{$tableName}', ['id', '=', \$id]);
                if (\$hapus) {
                    \$this->success("Data {$tableName} berhasil dihapus");
                } else {
                    \$this->badrequest("Gagal menghapus data {$tableName}");
                }
            } else {
                \$perbarui = \$this->db->perbarui('{$tableName}', \$id, [
{$columnArray}                ]);
                if (\$perbarui) {
                    \$this->success("Data {$tableName} berhasil diperbarui");
                } else {
                    \$this->badrequest("Gagal memperbarui data {$tableName}");
                }
            }
        } else {
            \$insert = \$this->db->input('{$tableName}', [
{$columnArray}            ]);
            if (\$insert) {
                \$this->success("Data {$tableName} berhasil ditambahkan");
            } else {
                \$this->badrequest("Gagal menambahkan data {$tableName}");
            }
        }
    }
}
PHP;
        $this->buatFile($filePath, $content, $className);
    }

    private function generatePhpActions(string $namaModule, string $tableName): void
    {
        $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Actions';
        $this->buatFolder($folderPath);

        $classIndex = "Index{$namaModule}Action";
        $classRepo = "{$namaModule}Repository";
        $dtoClass = "{$namaModule}Data";

        // 1. Index Action (View Renderer)
        $indexContent = <<<PHP
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$namaModule}\Actions;

use Abiesoft\System\View\ViewRenderer;

readonly class {$classIndex}
{
    public function __invoke(ViewRenderer \$view): void
    {
        \$view->render('pages/{$tableName}/index', [
            'title' => 'Modul {$namaModule}'
        ]);
    }
}
PHP;
        $this->buatFile($folderPath . '/' . $classIndex . '.php', $indexContent, $classIndex);

        // 2. Get Action
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
    }
}
PHP;
        $this->buatFile($folderPath . '/' . $classGet . '.php', $getContent, $classGet);

        // 3. Post Action
        $storeClass = "Post{$namaModule}Action";
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
        \$dto = {$dtoClass}::fromArray();
        \$repo->post(\$dto);
    }
}
PHP;
        $this->buatFile($folderPath . '/' . $storeClass . '.php', $storeContent, $storeClass);
    }

    private function generatePhpViewTemplate(string $name, string $namaModule): void
    {
        $templateDir = dirname(__DIR__, 3) . "/templates/pages/{$name}";
        $this->buatFolder($templateDir);
        $templateFile = $templateDir . "/index.latte";

        if (!file_exists($templateFile)) {
            $latteContent = <<<LATTE
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{\$title}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-8 font-sans">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow-sm border border-slate-200">
        <h1 class="text-2xl font-bold text-slate-800">Modul {$namaModule}</h1>
        <p class="text-slate-500 mt-1">Halaman modul PHP dibuat secara otomatis oleh AbieSoft Framework.</p>
        
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm">
            Endpoint API PHP: <code class="font-mono font-semibold">/api/{$name}</code>
        </div>
    </div>
</body>
</html>
LATTE;
            file_put_contents($templateFile, $latteContent);
            $this->log("✅ Template Latte dibuat: templates/pages/{$name}/index.latte", self::COLOR_GREEN);
        }
    }

    private function injectPhpRoutes(string $name, string $ucName): void
    {
        $routesFile = dirname(__DIR__, 3) . "/routes/web.php";
        if (!file_exists($routesFile)) return;

        $routesContent = file_get_contents($routesFile);

        if (!str_contains($routesContent, "/api/{$name}")) {
            $routeSnippet = <<<PHP


// Rute Modul {$ucName} (PHP Core)
\$router->get('/{$name}', \Abiesoft\App\Modules\\{$ucName}\Actions\Index{$ucName}Action::class);
\$router->get('/api/{$name}', \Abiesoft\App\Modules\\{$ucName}\Actions\Get{$ucName}Action::class, [ \Abiesoft\App\Shared\Middleware\ApiMiddleware::class ]);
\$router->post('/api/{$name}', \Abiesoft\App\Modules\\{$ucName}\Actions\Post{$ucName}Action::class, [ \Abiesoft\App\Shared\Middleware\ApiMiddleware::class ]);

PHP;
            file_put_contents($routesFile, $routesContent . $routeSnippet);
            $this->log("✔ Rute didaftarkan di routes/web.php", self::COLOR_GREEN);
        }
    }

    // =========================================================================
    // GENERATOR GOLANG MODULE (--go)
    // =========================================================================

    private function generateGoDto(string $name, string $ucName, array $semuaKolom): void
    {
        $baseGoDir = dirname(__DIR__, 3) . "/src/GoModules/{$ucName}/Dto";
        $this->buatFolder($baseGoDir);

        $dtoFile = $baseGoDir . "/{$name}_dto.go";
        $properties = "";

        foreach ($semuaKolom as $kolom) {
            $namaCamel = ucfirst($kolom['nama']);
            $tipe = ($kolom['tipe'] === 'int') ? 'int' : 'string';
            $properties .= "\t{$namaCamel} {$tipe} `json:\"{$kolom['nama']}\"`\n";
        }

        $dtoTemplate = <<<GODTO
package dto

type {$ucName}Dto struct {
	ID   int    `json:"id"`
	Uuid string `json:"uuid"`
{$properties}}
GODTO;
        file_put_contents($dtoFile, $dtoTemplate);
        $this->log("✅ Berkas Go DTO dibuat: src/GoModules/{$ucName}/Dto/{$name}_dto.go", self::COLOR_GREEN);
    }

    private function generateGoService(string $name, string $ucName, array $semuaKolom): void
    {
        $baseGoDir = dirname(__DIR__, 3) . "/src/GoModules/{$ucName}/Services";
        $this->buatFolder($baseGoDir);

        $serviceFile = $baseGoDir . "/{$name}_service.go";

        $selectCols = "id, uuid";
        $scanFields = "&d.ID, &d.Uuid";
        $insertCols = "uuid";
        $insertPlaceholders = "?";
        $insertArgs = "uuid";
        $updateSet = "";
        $updateArgs = "";

        $extractLines = "";
        foreach ($semuaKolom as $kolom) {
            $kNama = $kolom['nama'];
            $kCamel = ucfirst($kNama);
            $selectCols .= ", `{$kNama}`";
            $scanFields .= ", &d.{$kCamel}";
            $insertCols .= ", `{$kNama}`";
            $insertPlaceholders .= ", ?";
            $insertArgs .= ", {$kNama}";
            $updateSet .= "`{$kNama}` = ?, ";
            $updateArgs .= "{$kNama}, ";

            $extractLines .= "\t{$kNama} := req.Params[\"{$kNama}\"]\n";
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
	rows, err := db.Query("SELECT {$selectCols} FROM `{$name}` ORDER BY id DESC")
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal Query: " + err.Error()
		return res
	}
	defer rows.Close()

	list := make([]dto.{$ucName}Dto, 0)
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

func GetOnly{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	id := req.Params["id"]
	rows, err := db.Query("SELECT {$selectCols} FROM `{$name}` WHERE id = ?", id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal mengambil data: " + err.Error()
		return res
	}
	defer rows.Close()

	list := make([]dto.{$ucName}Dto, 0)
	for rows.Next() {
		var d dto.{$ucName}Dto
		if err := rows.Scan({$scanFields}); err != nil {
			continue
		}
		list = append(list, d)
	}

	res.Status = "success"
	res.Msg = "Single data retrieved successfully"
	res.Data = list
	return res
}

func GetBigData{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	limitStr := req.Params["limit"]
	offsetStr := req.Params["offset"]
	if limitStr == "" { limitStr = "100" }
	if offsetStr == "" { offsetStr = "0" }

	query := "SELECT {$selectCols} FROM `{$name}` LIMIT ? OFFSET ?"
	rows, err := db.Query(query, limitStr, offsetStr)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal Query Big Data: " + err.Error()
		return res
	}
	defer rows.Close()

	list := make([]dto.{$ucName}Dto, 0)
	for rows.Next() {
		var d dto.{$ucName}Dto
		if err := rows.Scan({$scanFields}); err != nil {
			continue
		}
		list = append(list, d)
	}

	res.Status = "success"
	res.Msg = "Big data retrieved successfully"
	res.Data = list
	return res
}

func Create{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	uuid := req.Params["uuid"]
{$extractLines}
	query := "INSERT INTO `{$name}` ({$insertCols}) VALUES ({$insertPlaceholders})"
	_, err := db.Exec(query, {$insertArgs})
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal menyimpan ke database: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil disimpan oleh Go Engine"
	res.Data = map[string]interface{}{
		"uuid": uuid,
	}
	return res
}

func Update{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	id := req.Params["id"]
{$extractLines}
	query := "UPDATE `{$name}` SET {$updateSet} WHERE id = ?"
	_, err := db.Exec(query, {$updateArgs}id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal memperbarui database: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil diperbarui oleh Go Engine"
	return res
}

func Delete{$ucName}Service(res shared.PiGoResponse, db *sql.DB, req shared.PiGoRequest) shared.PiGoResponse {
	id := req.Params["id"]
	query := "DELETE FROM `{$name}` WHERE id = ?"
	_, err := db.Exec(query, id)
	if err != nil {
		res.Status = "error"
		res.Msg = "Gagal menghapus data dari database: " + err.Error()
		return res
	}

	res.Status = "success"
	res.Msg = "Data berhasil dihapus oleh Go Engine"
	return res
}
GOCONTENT;

        file_put_contents($serviceFile, $serviceTemplate);
        $this->log("✅ Berkas Go Service dibuat: src/GoModules/{$ucName}/Services/{$name}_service.go", self::COLOR_GREEN);
    }

    private function generateGoAction(string $name, string $ucName, array $semuaKolom): void
    {
        $baseGoDir = dirname(__DIR__, 3) . "/src/GoModules/{$ucName}/Actions";
        $this->buatFolder($baseGoDir);

        $actionFile = $baseGoDir . "/{$name}_action.go";

        $actionTemplate = <<<GOACTION
package actions

import (
	services "abiesoft/src/GoModules/{$ucName}/Services"
	shared "abiesoft/src/Shared/Helpers/Golang"
	"crypto/rand"
	"database/sql"
	"fmt"
	"net/http"
	"strings"
	"time"
)

func generateSimpleUUID() string {
	b := make([]byte, 16)
	_, err := rand.Read(b)
	if err != nil {
		return fmt.Sprintf("%d", time.Now().UnixNano())
	}
	return fmt.Sprintf("%x-%x-%x-%x-%x", b[0:4], b[4:6], b[6:8], b[8:10], b[10:])
}

func GetAll{$ucName}HTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		req := shared.PiGoRequest{
			Action: "{$name}-all-data",
			Params: shared.ExtractParams(r),
		}
		result := services.GetAll{$ucName}Service(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Data)
	}
}

func GetOnly{$ucName}HTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)
		if id := r.PathValue("id"); id != "" {
			params["id"] = id
		}
		req := shared.PiGoRequest{
			Action: "get-only-{$name}",
			Params: params,
		}
		result := services.GetOnly{$ucName}Service(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Data)
	}
}

func GetBigData{$ucName}HTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)
		if offset := r.PathValue("offset"); offset != "" {
			params["offset"] = offset
		}
		if limit := r.PathValue("limit"); limit != "" {
			params["limit"] = limit
		}
		req := shared.PiGoRequest{
			Action: "get-big-data-{$name}",
			Params: params,
		}
		result := services.GetBigData{$ucName}Service(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Data)
	}
}

func Save{$ucName}HTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)

		methodOverride := strings.ToUpper(params["__method"])
		id := params["id"]

		if methodOverride == "DELETE" && id != "" {
			req := shared.PiGoRequest{
				Action: "delete-{$name}",
				Params: params,
			}
			result := services.Delete{$ucName}Service(res, db, req)
			if result.Status == "error" {
				shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
				return
			}
			shared.SuccessResponse(w, result.Msg)
			return
		}

		if id != "" {
			req := shared.PiGoRequest{
				Action: "update-{$name}",
				Params: params,
			}
			result := services.Update{$ucName}Service(res, db, req)
			if result.Status == "error" {
				shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
				return
			}
			shared.SuccessResponse(w, result.Msg)
			return
		}

		// Create
		if params["uuid"] == "" {
			params["uuid"] = generateSimpleUUID()
		}

		req := shared.PiGoRequest{
			Action: "post-{$name}",
			Params: params,
		}
		result := services.Create{$ucName}Service(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Data)
	}
}

func Update{$ucName}HTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)
		if id := r.PathValue("id"); id != "" {
			params["id"] = id
		}
		req := shared.PiGoRequest{
			Action: "update-{$name}",
			Params: params,
		}
		result := services.Update{$ucName}Service(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Msg)
	}
}

func Delete{$ucName}HTTPHandler(db *sql.DB) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		var res shared.PiGoResponse
		params := shared.ExtractParams(r)
		if id := r.PathValue("id"); id != "" {
			params["id"] = id
		}
		req := shared.PiGoRequest{
			Action: "delete-{$name}",
			Params: params,
		}
		result := services.Delete{$ucName}Service(res, db, req)
		if result.Status == "error" {
			shared.ErrorResponse(w, http.StatusBadRequest, result.Msg)
			return
		}
		shared.SuccessResponse(w, result.Msg)
	}
}
GOACTION;

        file_put_contents($actionFile, $actionTemplate);
        $this->log("✅ Berkas Go Action dibuat: src/GoModules/{$ucName}/Actions/{$name}_action.go", self::COLOR_GREEN);
    }

    private function injectGoRoutes(string $name, string $ucName): void
    {
        $handlerFile = dirname(__DIR__, 3) . "/src/Modules/handler.go";
        if (!file_exists($handlerFile)) return;

        $content = file_get_contents($handlerFile);

        // 1. Sisipkan import jika belum ada
        $importLine = "\t{$name}Actions \"abiesoft/src/GoModules/{$ucName}/Actions\"\n";
        if (!str_contains($content, "src/GoModules/{$ucName}/Actions")) {
            $content = preg_replace('/import \(/', "import (\n{$importLine}", $content, 1);
        }

        // 2. Sisipkan rute di RegisterRoutes jika belum ada
        $routeSnippet = "\n\t// {$ucName} module (Direct Go)\n" .
            "\tmux.HandleFunc(\"GET /api-go/{$name}\", {$name}Actions.GetAll{$ucName}HTTPHandler(db))\n" .
            "\tmux.HandleFunc(\"GET /api-go/{$name}/{id}\", {$name}Actions.GetOnly{$ucName}HTTPHandler(db))\n" .
            "\tmux.HandleFunc(\"GET /api-go/{$name}/{offset}/{limit}\", {$name}Actions.GetBigData{$ucName}HTTPHandler(db))\n" .
            "\tmux.HandleFunc(\"POST /api-go/{$name}\", {$name}Actions.Save{$ucName}HTTPHandler(db))\n" .
            "\tmux.HandleFunc(\"PUT /api-go/{$name}/{id}\", {$name}Actions.Update{$ucName}HTTPHandler(db))\n" .
            "\tmux.HandleFunc(\"DELETE /api-go/{$name}/{id}\", {$name}Actions.Delete{$ucName}HTTPHandler(db))\n";

        if (!str_contains($content, "/api-go/{$name}")) {
            // Cari penutup kurung kurawal fungsi RegisterRoutes menggunakan bracket balancer
            $pos = strpos($content, "func RegisterRoutes(");
            if ($pos !== false) {
                $braceStart = strpos($content, "{", $pos);
                if ($braceStart !== false) {
                    $depth = 1;
                    $inString = false;
                    $len = strlen($content);
                    $closingBracePos = -1;
                    for ($i = $braceStart + 1; $i < $len; $i++) {
                        $char = $content[$i];
                        if ($char === '"' && ($i === 0 || $content[$i - 1] !== '\\')) {
                            $inString = !$inString;
                        } else if (!$inString) {
                            if ($char === '{') {
                                $depth++;
                            } else if ($char === '}') {
                                $depth--;
                                if ($depth === 0) {
                                    $closingBracePos = $i;
                                    break;
                                }
                            }
                        }
                    }
                    if ($closingBracePos !== -1) {
                        $content = substr_replace($content, $routeSnippet . "}", $closingBracePos, 1);
                    }
                }
            }
        }

        file_put_contents($handlerFile, $content);
        $this->log("✔ Rute otomatis diinjeksi ke src/Modules/handler.go", self::COLOR_GREEN);
    }

    private function generateGoTest(string $name, string $ucName): void
    {
        $testFile = dirname(__DIR__, 3) . "/src/GoModules/{$ucName}/{$name}_test.go";
        $template = <<<GOTEST
package {$name}_test

import (
	shared "abiesoft/src/Shared/Helpers/Golang"
	"testing"
)

func Test{$ucName}ModuleSmoke(t *testing.T) {
	req := shared.PiGoRequest{
		Action: "{$name}-all-data",
		Params: map[string]string{},
	}
	if req.Action != "{$name}-all-data" {
		t.Errorf("Expected action '{$name}-all-data', got '%s'", req.Action)
	}
}
GOTEST;
        file_put_contents($testFile, $template);
        $this->log("✅ Berkas Unit Test Go dibuat: src/GoModules/{$ucName}/{$name}_test.go", self::COLOR_GREEN);
    }

    private function generatePhpTest(string $name, string $ucName): void
    {
        $testDir = dirname(__DIR__, 3) . "/src/Modules/{$ucName}/Tests";
        $this->buatFolder($testDir);
        $testFile = $testDir . "/{$ucName}Test.php";
        $template = <<<PHPTEST
<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\\{$ucName}\Tests;

use Abiesoft\System\Testing\TestCase;
use Abiesoft\App\Modules\\{$ucName}\Dto\\{$ucName}Data;

class {$ucName}Test extends TestCase
{
    public function testDtoCreation(): void
    {
        \$dto = new {$ucName}Data();
        \$this->assertNotNull(\$dto);
    }
}
PHPTEST;
        file_put_contents($testFile, $template);
        $this->log("✅ Berkas Unit Test PHP dibuat: src/Modules/{$ucName}/Tests/{$ucName}Test.php", self::COLOR_GREEN);
    }
}