<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

use Abiesoft\System\Database\DB;

class DatabaseImportCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        // Gunakan koneksi DB Anda yang baru
        $db = DB::terhubung();

        // 1. Buat Tabel Tracking Migration (Jika belum ada)
        $db->query("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // 2. Ambil Daftar File Schema
        $schemaFolder = dirname(__DIR__, 3) . '/database/schemas';
        
        if (!is_dir($schemaFolder)) {
            $this->tampilkanError("Folder schema belum ada. Buat module dulu.");
            return;
        }

        // Ambil semua file berakhiran .sql dan urutkan sesuai abjad/waktu
        $files = glob($schemaFolder . '/*.sql');
        sort($files); 

        if (empty($files)) {
            $this->log("📂 Tidak ada file schema ditemukan di database/schemas.", self::COLOR_YELLOW);
            return;
        }

        // 3. Ambil Daftar Schema yang SUDAH diimport dari Database
        $db->query("SELECT migration FROM migrations");
        $importedFiles = [];
        
        // Loop hasil query untuk mendapatkan array nama file saja
        if ($db->hitung() > 0) {
            foreach ($db->hasil() as $row) {
                $importedFiles[] = $row->migration;
            }
        }

        // 4. Eksekusi Schema yang BELUM diimport
        $importedCount = 0;

        foreach ($files as $file) {
            $fileName = basename($file);

            // Jika file belum ada di array $importedFiles, eksekusi!
            if (!in_array($fileName, $importedFiles)) {
                $sql = file_get_contents($file);

                // Eksekusi schema
                $db->query($sql);

                // Cek apakah ada error dari eksekusi di atas
                if (!$db->error()) {
                    
                    // Catat ke tabel migrations agar tidak dieksekusi lagi besok-besok
                    // Kita pakai query() manual karena input() memaksa tambah UUID
                    $db->query("INSERT INTO migrations (migration) VALUES (?)", [$fileName]);

                    $this->log("✅ Diimport: " . $fileName, self::COLOR_GREEN);
                    $importedCount++;
                    
                } else {
                    // Berhenti jika file schema error
                    $this->tampilkanError("❌ Gagal mengimport {$fileName}. Silakan periksa syntax SQL di file tersebut.");
                    return; 
                }
            }
        }

        // 5. Kesimpulan
        if ($importedCount === 0) {
            $this->log("👍 Semua schema database sudah up-to-date. Tidak ada yang baru.", self::COLOR_CYAN);
        } else {
            $this->log("🎉 Selesai! $importedCount schema baru berhasil diimport.", self::COLOR_GREEN);
        }
    }
}