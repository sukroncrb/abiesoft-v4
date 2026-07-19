<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

use Abiesoft\System\Database\DB;

class DatabaseImportCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $db = DB::terhubung();

        $db->query("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $schemaFolder = dirname(__DIR__, 3) . '/database/schemas';
        
        if (!is_dir($schemaFolder)) {
            $this->tampilkanError("Folder schema belum ada. Buat module dulu.");
            return;
        }

        $files = glob($schemaFolder . '/*.sql');
        sort($files); 

        if (empty($files)) {
            $this->log("📂 Tidak ada file schema ditemukan di database/schemas.", self::COLOR_YELLOW);
            return;
        }

        $db->query("SELECT migration FROM migrations");
        $importedFiles = [];
        
        if ($db->hitung() > 0) {
            foreach ($db->hasil() as $row) {
                $importedFiles[] = $row->migration;
            }
        }

        $importedCount = 0;

        foreach ($files as $file) {
            $fileName = basename($file);

            if (!in_array($fileName, $importedFiles)) {
                $sql = file_get_contents($file);

                $db->query($sql);

                if (!$db->error()) {
                    
                    $db->query("INSERT INTO migrations (migration) VALUES (?)", [$fileName]);

                    $this->log("✅ Diimport: " . $fileName, self::COLOR_GREEN);
                    $importedCount++;
                    
                } else {
                    $this->tampilkanError("❌ Gagal mengimport {$fileName}. Silakan periksa syntax SQL di file tersebut.");
                    return; 
                }
            }
        }

        if ($importedCount === 0) {
            $this->log("👍 Semua schema database sudah up-to-date. Tidak ada yang baru.", self::COLOR_CYAN);
        } else {
            $this->log("🎉 Selesai! $importedCount schema baru berhasil diimport.", self::COLOR_GREEN);
        }
    }
}