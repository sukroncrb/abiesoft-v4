<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

use Abiesoft\System\Console\Commands\Utilities\Compile;
use Abiesoft\System\Database\DB;

class DeleteModuleCommand extends BaseCommand
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
            $this->tampilkanError("Nama module belum diisi.\nGunakan:\n  • Hapus Modul PHP    : php abiesoft delete:module <nama_module>\n  • Hapus Modul Golang : php abiesoft delete:module <nama_module> --go");
            return;
        }

        $name = strtolower($moduleInput);
        $namaModule = ucfirst($moduleInput);
        
        $phpModulePath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule;
        $goModulePath  = dirname(__DIR__, 3) . '/src/GoModules/' . $namaModule;
        $templatePath  = dirname(__DIR__, 3) . '/templates/pages/' . $name;
        $folderSchema  = dirname(__DIR__, 3) . '/database/schemas';

        if ($isGo) {
            if (!is_dir($goModulePath)) {
                $this->tampilkanError("Modul Golang '$namaModule' tidak ditemukan di src/GoModules/{$namaModule}.");
                return;
            }
        } else {
            if (!is_dir($phpModulePath)) {
                // Cek apakah mungkin ini modul Go jika modul PHP tidak ada
                if (is_dir($goModulePath)) {
                    $this->tampilkanError("Modul PHP '$namaModule' tidak ditemukan, tetapi ditemukan modul Golang.\nGunakan flag --go: php abiesoft delete:module {$name} --go");
                    return;
                }
                $this->tampilkanError("Modul PHP '$namaModule' tidak ditemukan di src/Modules/{$namaModule}.");
                return;
            }
        }

        $modeLabel = $isGo ? "Golang Native Module (--go)" : "PHP Core Module";

        echo self::COLOR_RED . "\n⚠️  PERINGATAN BERBAHAYA!" . self::COLOR_RESET . PHP_EOL;
        echo "Anda akan menghapus modul " . self::COLOR_YELLOW . "{$namaModule} [{$modeLabel}]" . self::COLOR_RESET . ":" . PHP_EOL;
        if ($isGo) {
            echo "   [-] Folder Golang Modul: src/GoModules/{$namaModule}\n";
            echo "   [-] Pendaftaran Rute di src/Modules/handler.go\n";
        } else {
            echo "   [-] Folder PHP Modul: src/Modules/{$namaModule}\n";
            if (is_dir($templatePath)) {
                echo "   [-] Folder View Templates: templates/pages/{$name}\n";
            }
            echo "   [-] Pendaftaran Rute di routes/web.php\n";
        }
        echo "   [-] Tabel Database MySQL: " . self::COLOR_YELLOW . $name . self::COLOR_RESET . PHP_EOL;
        echo "   [-] Berkas Schema SQL & Riwayat Data di Tabel 'migrations'" . PHP_EOL;
        echo "Tindakan ini menghapus file/data terkait dan tidak bisa dibatalkan." . PHP_EOL;
        echo "Apakah Anda yakin? (y/n): ";

        $handle = fopen("php://stdin", "r");
        $confirmation = trim((string)fgets($handle));
        fclose($handle);

        if (strtolower($confirmation) !== 'y') {
            $this->log("\n❌ Penghapusan dibatalkan.", self::COLOR_YELLOW);
            return;
        }

        echo PHP_EOL;

        if ($isGo) {
            // 1. Hapus direktori GoModule
            $this->log("🗑️  Menghapus struktur folder Golang Modul...", self::COLOR_BLUE);
            if ($this->hapusDirektori($goModulePath)) {
                $this->log("   ✔ Folder Go '$namaModule' berhasil dihapus.", self::COLOR_GREEN);
            } else {
                $this->log("   ✘ Gagal menghapus beberapa berkas Go.", self::COLOR_RED);
            }

            // 2. Bersihkan rute dan import di handler.go
            $this->bersihkanGoHandler($name, $namaModule);

            // 3. Recompile Go engine
            $this->log("\n⚙️  Melakukan kompilasi ulang Go Engine...", self::COLOR_BLUE);
            $this->compileGo();
        } else {
            // 1. Hapus direktori PHP Module
            $this->log("🗑️  Menghapus struktur folder PHP Modul...", self::COLOR_BLUE);
            if ($this->hapusDirektori($phpModulePath)) {
                $this->log("   ✔ Folder PHP '$namaModule' berhasil dihapus.", self::COLOR_GREEN);
            } else {
                $this->log("   ✘ Gagal menghapus beberapa berkas PHP.", self::COLOR_RED);
            }

            // 2. Hapus Template View Latte
            if (is_dir($templatePath)) {
                $this->hapusDirektori($templatePath);
                $this->log("   ✔ Folder Template 'templates/pages/{$name}' berhasil dihapus.", self::COLOR_GREEN);
            }

            // 3. Bersihkan rute dari routes/web.php
            $this->bersihkanPhpRoutes($name, $namaModule);
        }

        // Hapus Tabel MySQL
        $this->log("\n⚡ Menghapus tabel database '{$name}'...", self::COLOR_BLUE);
        try {
            $db = (new DB)->terhubung();
            $db->query("DROP TABLE IF EXISTS {$name}");
            $this->log("   ✔ Tabel '{$name}' berhasil di-drop dari database.", self::COLOR_GREEN);
        } catch (\Throwable $e) {
            $this->log("   ✘ Gagal menghapus tabel: " . $e->getMessage(), self::COLOR_RED);
        }

        // Hapus Berkas Schema SQL
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
            $this->log("   💡 Info: Berkas schema SQL tidak ditemukan atau sudah bersih.", self::COLOR_YELLOW);
        }

        if (!empty($fileSchemaDitemukan)) {
            $this->log("🗄️  Membersihkan data riwayat di tabel 'migrations'...", self::COLOR_BLUE);
            try {
                $db = (new DB)->terhubung();
                foreach ($fileSchemaDitemukan as $namaFileSql) {
                    $db->query("DELETE FROM migrations WHERE migration = '{$namaFileSql}'");
                }
                $this->log("   ✔ Riwayat migrasi untuk modul '{$name}' berhasil dibersihkan.", self::COLOR_GREEN);
            } catch (\Throwable $e) {
                $this->log("   ✘ Gagal membersihkan tabel migrations: " . $e->getMessage(), self::COLOR_RED);
            }
        }

        $this->log("\n✨ Pembersihan modul {$namaModule} [{$modeLabel}] selesai total!", self::COLOR_GREEN);
    }

    private function bersihkanGoHandler(string $name, string $ucName): void
    {
        $handlerFile = dirname(__DIR__, 3) . "/src/Modules/handler.go";
        if (!file_exists($handlerFile)) return;

        $lines = file($handlerFile);
        $newLines = [];
        $skipModuleComment = false;

        foreach ($lines as $line) {
            // Hapus import alias
            if (str_contains($line, "src/GoModules/{$ucName}/Actions")) {
                continue;
            }
            // Hapus rute mux.HandleFunc yang mengandung nama modul ini
            if (str_contains($line, "/api-go/{$name}") || str_contains($line, "{$name}Actions.")) {
                continue;
            }
            // Hapus baris komentar modul jika ada
            if (str_contains($line, "// {$ucName} module")) {
                continue;
            }
            $newLines[] = $line;
        }

        file_put_contents($handlerFile, implode('', $newLines));
        $this->log("   ✔ Rute dan import di src/Modules/handler.go berhasil dibersihkan.", self::COLOR_GREEN);
    }

    private function bersihkanPhpRoutes(string $name, string $ucName): void
    {
        $routesFile = dirname(__DIR__, 3) . "/routes/web.php";
        if (!file_exists($routesFile)) return;

        $lines = file($routesFile);
        $newLines = [];

        foreach ($lines as $line) {
            if (str_contains($line, "/{$name}'") || str_contains($line, "/api/{$name}'") || str_contains($line, "\\{$ucName}\\Actions\\")) {
                continue;
            }
            if (str_contains($line, "// Rute Modul {$ucName}")) {
                continue;
            }
            $newLines[] = $line;
        }

        file_put_contents($routesFile, implode('', $newLines));
        $this->log("   ✔ Rute di routes/web.php berhasil dibersihkan.", self::COLOR_GREEN);
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