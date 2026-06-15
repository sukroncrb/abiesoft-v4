<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

use Abiesoft\System\Database\DB;

class DeleteModuleCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $moduleInput = $args[2] ?? null;

        if (!$moduleInput) {
            $this->tampilkanError("Nama module belum diisi.\nGunakan: php abiesoft delete:module <nama_module>");
            return;
        }

        $name = strtolower($moduleInput);
        $namaModule = ucfirst($moduleInput);
        
        $phpModulePath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule;
        $goModulePath  = dirname(__DIR__, 3) . '/src/GoModules/' . $namaModule;
        $folderSchema  = dirname(__DIR__, 3) . '/database/schemas';

        
        if (!is_dir($phpModulePath) && !is_dir($goModulePath)) {
            $this->tampilkanError("Module '$namaModule' tidak ditemukan di struktur PHP maupun Go Engine.");
            return;
        }

        echo self::COLOR_RED . "\n⚠️  PERINGATAN BERBAHAYA!" . self::COLOR_RESET . PHP_EOL;
        echo "Anda akan menghapus komponen modul " . self::COLOR_YELLOW . $namaModule . self::COLOR_RESET . ":" . PHP_EOL;
        if (is_dir($phpModulePath)) { echo "   [-] Folder PHP Modul murni\n"; }
        if (is_dir($goModulePath))  { echo "   [-] Folder Golang Core Modul\n"; }
        echo "   [-] Tabel Database MySQL: " . self::COLOR_YELLOW . $name . self::COLOR_RESET . PHP_EOL;
        echo "   [-] Berkas Schema SQL & Riwayat Data di Tabel 'migrations'" . PHP_EOL;
        echo "Tindakan ini menghapus SELURUH file/data terkait dan tidak bisa dibatalkan." . PHP_EOL;
        echo "Apakah Anda yakin? (y/n): ";

        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);

        if (strtolower($confirmation) !== 'y') {
            $this->log("\n❌ Penghapusan dibatalkan.", self::COLOR_YELLOW);
            return;
        }

        echo PHP_EOL;

        
        if (is_dir($phpModulePath)) {
            $this->log("🗑️  Menghapus struktur folder PHP Modul...", self::COLOR_BLUE);
            if ($this->hapusDirektori($phpModulePath)) {
                $this->log("   ✔ Folder PHP '$namaModule' berhasil dibersihkan.", self::COLOR_GREEN);
            } else {
                $this->log("   ✘ Gagal menghapus beberapa berkas PHP. Periksa permission folder.", self::COLOR_RED);
            }
        }

        
        if (is_dir($goModulePath)) {
            $this->log("🗑️  Menghapus struktur folder Golang Modul...", self::COLOR_BLUE);
            if ($this->hapusDirektori($goModulePath)) {
                $this->log("   ✔ Folder Go '$namaModule' berhasil dibersihkan.", self::COLOR_GREEN);
            } else {
                $this->log("   ✘ Gagal menghapus beberapa berkas Go. Periksa permission folder.", self::COLOR_RED);
            }
        }

        
        $this->log("⚡ Menghapus tabel database '{$name}'...", self::COLOR_BLUE);
        try {
            $db = (new DB)->terhubung();
            $db->query("DROP TABLE IF EXISTS {$name}");
            $this->log("   ✔ Tabel '{$name}' berhasil di-drop dari database.", self::COLOR_GREEN);
        } catch (\Exception $e) {
            $this->log("   ✘ Gagal menghapus tabel: " . $e->getMessage(), self::COLOR_RED);
        }

        
        $this->log("📂 Mencari dan menghapus berkas schema SQL...", self::COLOR_BLUE);
        $fileSchemaDitemukan = [];
        if (is_dir($folderSchema)) {
            foreach (scandir($folderSchema) as $file) {
                
                if (str_contains($file, "_create_{$name}_table.sql")) {
                    $fullPath = $folderSchema . '/' . $file;
                    if (unlink($fullPath)) {
                        $this->log("   ✔ Berkas schema 'database/schemas/{$file}' berhasil dihapus.", self::COLOR_GREEN);
                        $fileSchemaDitemukan[] = $file; 
                    }
                }
            }
        }
        if (empty($fileSchemaDitemukan)) {
            $this->log("   💡 Info: Berkas file schema SQL tidak ditemukan atau sudah dihapus.", self::COLOR_YELLOW);
        }

        if (!empty($fileSchemaDitemukan)) {
            $this->log("🗄️  Membersihkan data riwayat di tabel 'migrations'...", self::COLOR_BLUE);
            try {
                $db = (new DB)->terhubung();
                foreach ($fileSchemaDitemukan as $namaFileSql) {
                    $db->query("DELETE FROM migrations WHERE migration = '{$namaFileSql}'");
                }
                $this->log("   ✔ Riwayat migrasi untuk modul '{$name}' berhasil dibersihkan.", self::COLOR_GREEN);
            } catch (\Exception $e) {
                $this->log("   ✘ Gagal membersihkan tabel migrations: " . $e->getMessage(), self::COLOR_RED);
            }
        }

        $this->log("\n✨ Pembersihan total modul $namaModule selesai tanpa sisa!", self::COLOR_GREEN);
        $this->log("💡 Catatan: Jika modul ini terdaftar di src/Modules/handler.go, silakan hapus baris routingnya manual.", self::COLOR_YELLOW);
    }

    private function hapusDirektori(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->hapusDirektori($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}