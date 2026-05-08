<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class DeleteModuleCommand extends BaseCommand
{
    public function handle(array $args): void
    {

        $moduleInput = $args[2] ?? null;

        if (!$moduleInput) {
            $this->tampilkanError("Nama module belum diisi.\nGunakan: php abiesoft delete:module <nama_module>");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        
        $modulePath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule;

        if (!is_dir($modulePath)) {
            $this->tampilkanError("Module '$namaModule' tidak ditemukan di: $modulePath");
            return;
        }

        echo self::COLOR_RED . "\n⚠️  PERINGATAN BERBAHAYA!" . self::COLOR_RESET . PHP_EOL;
        echo "Anda akan menghapus module " . self::COLOR_YELLOW . $namaModule . self::COLOR_RESET . " beserta SELURUH isinya (Action, DTO, Service, dll)." . PHP_EOL;
        echo "Tindakan ini tidak bisa dibatalkan." . PHP_EOL;
        echo "Apakah Anda yakin? (y/n): ";

        $handle = fopen("php://stdin", "r");
        $confirmation = trim(fgets($handle));
        fclose($handle);

        if (strtolower($confirmation) !== 'y') {
            $this->log("\n❌ Dibatalkan.", self::COLOR_YELLOW);
            return;
        }

        $this->log("\n🗑️  Sedang menghapus module $namaModule...", self::COLOR_BLUE);

        if ($this->hapusDirektori($modulePath)) {
            $this->log("✅ Module '$namaModule' berhasil dihapus selamanya.", self::COLOR_GREEN);
        } else {
            $this->tampilkanError("Gagal menghapus beberapa file. Silakan cek permission folder.");
        }
    }

    private function hapusDirektori(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir); // Hapus file
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->hapusDirektori($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir); // Hapus folder kosong
    }
}