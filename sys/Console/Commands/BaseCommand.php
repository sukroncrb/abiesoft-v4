<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

abstract class BaseCommand
{

    protected const COLOR_RESET = "\033[0m";
    protected const COLOR_GREEN = "\033[32m";
    protected const COLOR_YELLOW = "\033[33m";
    protected const COLOR_RED = "\033[31m";
    protected const COLOR_CYAN = "\033[36m";
    protected const COLOR_BLUE = "\033[34m";


    abstract public function handle(array $args): void;


    protected function tampilkanError(string $pesan): void
    {
        echo self::COLOR_RED . "Error: " . $pesan . self::COLOR_RESET . PHP_EOL;
    }

    protected function log(string $pesan, string $warna = self::COLOR_RESET): void
    {
        echo $warna . $pesan . self::COLOR_RESET . PHP_EOL;
    }

    protected function buatFolder(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            // Menampilkan path relatif agar output terminal tidak kepanjangan
            $baseDir = dirname(__DIR__, 4); // Menyesuaikan kedalaman folder
            $relativePath = str_replace($baseDir, '', $path);
            $this->log("📂 Membuat folder: $relativePath", self::COLOR_YELLOW);
        }
    }

    protected function buatFile(string $path, string $content, string $namaClass): void
    {
        if (file_put_contents($path, $content)) {
            $baseDir = dirname(__DIR__, 4);
            $relativePath = str_replace($baseDir, '', $path);
            $this->log("✅ Berhasil membuat: $namaClass", self::COLOR_GREEN);
            $this->log("👉 Lokasi: $relativePath");
        } else {
            $this->tampilkanError("Gagal menulis file.");
        }
    }

    protected function hapusFileDenganConfirm(string $filePath): void
    {
        if (!file_exists($filePath)) {
            $this->tampilkanError("File tidak ditemukan: " . basename($filePath));
            return;
        }

        echo self::COLOR_RED . "⚠️  Yakin hapus file? " . self::COLOR_YELLOW . basename($filePath) . self::COLOR_RESET . " (y/n): ";
        
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));

        if (strtolower($input) !== 'y') {
            $this->log("❌ Dibatalkan.", self::COLOR_YELLOW);
            return;
        }

        if (unlink($filePath)) {
            $this->log("✅ File berhasil dihapus.", self::COLOR_GREEN);
        } else {
            $this->tampilkanError("Gagal menghapus file (Permission denied?).");
        }
    }
}