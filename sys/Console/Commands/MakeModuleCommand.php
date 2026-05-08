<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeModuleCommand extends BaseCommand
{
    public function handle(array $args): void
    {

        $moduleInput = $args[2] ?? null;

        if (!$moduleInput) {
            $this->tampilkanError("Nama module belum diisi.\nGunakan: php abiesoft make:module <nama_module>");
            return;
        }

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
            }else if ($tipeKolom == 'text') {
                $tipeKolom = 'string';
                $tipedata = 'TEXT';
            }else if ($tipeKolom == 'longtext') {
                $tipeKolom = 'string';
                $tipedata = 'LONGTEXT';
            }else if ($tipeKolom == 'datetime') {
                $tipeKolom = 'string';
                $tipedata = 'DATETIME';
            }else if ($tipeKolom == 'angka') {
                $tipeKolom = 'int';
                $tipedata = 'INT(11)';
            }else if ($tipeKolom === 'enum') {
                echo self::COLOR_CYAN . "💡 Masukkan pilihan ENUM (pisahkan dengan koma, contoh: aktif,nonaktif): " . self::COLOR_RESET;
                $enumInput = trim(fgets(STDIN));
                $optionsArray = explode(',', $enumInput);
                $formattedOptions = array_map(function($val) {
                    return "'" . trim($val) . "'";
                }, $optionsArray);
                $enumString = implode(', ', $formattedOptions);
                $tipeKolom = 'string';
                $tipedata = "ENUM($enumString) DEFAULT " . $formattedOptions[0];
            }else{
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

        /*


            Membuat tabel di database
        */

        $namatabel = strtolower($namaModule);
        $sql = "CREATE TABLE IF NOT EXISTS {$namatabel} (\n";
        $sql .= "    id INT AUTO_INCREMENT PRIMARY KEY,\n";
        $sql .= "    uuid VARCHAR(36) NOT NULL,\n";

        foreach ($semuaKolom as $kolom) {
            $sql .= "    {$kolom['nama']} {$kolom['tipedata']},\n";
        }

        $sql .= "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
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


        /*


            Melanjutkan proses sebelumnya
            Membuat Dto
        */

        $this->log("\n🔄 Sedang men-generate file...", self::COLOR_BLUE);

        $this->generateDto($namaModule, $semuaKolom);
        $this->generateService($namaModule, $semuaKolom);
        $this->generateActions($namaModule, $moduleInput);

        $this->log("\n✨ Module $namaModule selesai dibuat!", self::COLOR_GREEN);
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
            
            $mapping .= "            {$nama}: {$cast}(\$data['{$nama}'] ?? null),\n";
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace Abiesoft\App\Modules\\{$namaModule}\Dto;

        readonly class {$className}
        {
            public function __construct(
                public ?int \$id, // ID selalu default
                public ?string \$uuid, // UUID selalu default
        {$properties}
            ) {}

            public static function fromArray(array \$data, ?string \$uuid = null): self
            {
                return new self(
                    id: isset(\$data['id']) ? (int)\$data['id'] : null,
                    uuid: \$uuid ?? (\$data['uuid'] ?? null),
        {$mapping}
                );
            }
        }
        PHP;
        $this->buatFile($filePath, $content, $className);
    }

    private function generateService(string $namaModule, array $semuaKolom): void
    {
        $className = "{$namaModule}Repository";
        $dtoClass  = "{$namaModule}Data";
        $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Services';
        $filePath   = $folderPath . '/' . $className . '.php';
        $tableName  = strtolower($namaModule); // asumsi nama tabel lowercase

        $this->buatFolder($folderPath);

        $insertData = "";
        foreach ($semuaKolom as $kolom) {
            $nama = $kolom['nama'];
            $insertData .= "            '{$nama}' => \$dto->{$nama},\n";
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace Abiesoft\App\Modules\\{$namaModule}\Services;

        use Abiesoft\App\Modules\Proyek\Dto\ProyekData;
        use Abiesoft\App\Modules\\{$namaModule}\Dto\\{$dtoClass};
        use Abiesoft\System\Database\DB;

        class {$className}
        {
            private \$db;

            public function __construct()
            {
                \$this->db = (new DB)->terhubung();
            }

            public function getAll()
            {
                /*


                    ---------------------------------------------------------------
                    Menampilkan semua data
                    ---------------------------------------------------------------
                */
            }

            public function getOnly()
            {
                /*


                    ---------------------------------------------------------------
                    Menampilkan 1 data
                    ---------------------------------------------------------------
                */
            }

            public function keep({$dtoClass} \$dto)
            {
                return \$this->db->input('{$tableName}', [
                    'uuid' => \$dto->uuid,
        {$insertData}
                ]);
            }

            public function replace()
            {
                /*


                    ---------------------------------------------------------------
                    Memperbarui data
                    ---------------------------------------------------------------
                */
            }

            public function drop()
            {
                /*


                    ---------------------------------------------------------------
                    Menghapus data
                    ---------------------------------------------------------------
                */
            }
        }
        PHP;
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
        $this->buatFIle($folderPath . '/' . $classIndex . '.php', $indexContent, $classIndex);

        // 2. Store Action (Tetap sama, karena tidak pakai View)
        $storeClass = "Keep{$namaModule}Action";
        $dtoClass = "{$namaModule}Data";
        
        $storeContent = <<<PHP
        <?php

        declare(strict_types=1);

        namespace Abiesoft\App\Modules\\{$namaModule}\Actions;

        use Abiesoft\App\Modules\\{$namaModule}\Services\\{$classRepo};
        use Abiesoft\App\Modules\\{$namaModule}\Dto\\{$dtoClass};
        use Abiesoft\App\Shared\Helpers\Uuid;

        readonly class {$storeClass}
        {
            public function __invoke(): void
            {
                \$repo = new {$classRepo}();
                // Generate UUID & Mapping DTO
                \$uuid = Uuid::v4();
                \$dto = {$dtoClass}::fromArray(\$_POST, \$uuid);
                \$repo->keep(\$dto);
                exit;
            }
        }
        PHP;
        $this->buatFIle($folderPath . '/' . $storeClass . '.php', $storeContent, $storeClass);
    }
}