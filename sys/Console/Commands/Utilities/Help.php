<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands\Utilities;

trait Help
{

    private const COLOR_RESET = "\033[0m";
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_RED = "\033[31m";
    private const COLOR_CYAN = "\033[36m";
    private const COLOR_BLUE = "\033[34m";
    const BG_LIGHT_GREEN = "\e[102m";
    const TEXT_BLACK = "\e[30m";
    const TEXT_CYAN = "\e[36m";

    private function showHelp(): void
    {
        echo PHP_EOL;
        echo self::COLOR_CYAN . "   Abiesoft Framework CLI" . self::COLOR_RESET . " version 1.1.0" . PHP_EOL;
        echo self::COLOR_RESET . "   Usage: php abiesoft " . self::COLOR_YELLOW . "[command]" . self::COLOR_RESET . " [options]" . PHP_EOL . PHP_EOL;

        // Saya perlebar kolomnya jadi 32 karakter agar teks tidak berantakan
        $mask = "   " . self::COLOR_GREEN . "%-32s" . self::COLOR_RESET . " %s" . PHP_EOL;

        // --- SECTION: MAIN ---
        echo self::COLOR_YELLOW . "   Available Commands:" . self::COLOR_RESET . PHP_EOL;
        printf($mask, "start", "Menjalankan server development lokal");
        printf($mask, "route", "Menampilkan daftar route yang terdaftar");
        printf($mask, "build", "Membuat file compile golang");
        printf($mask, "help", "Menampilkan menu bantuan ini");

        // --- SECTION: MAKE (GENERATORS) ---
        echo PHP_EOL . self::COLOR_YELLOW . "   Generators:" . self::COLOR_RESET . PHP_EOL;
        printf($mask, "make:module <nama>", "Wizard interaktif membuat module lengkap");
        printf($mask, "make:action <module> <nama>", "Membuat file Action baru");
        printf($mask, "make:service <module> <nama>", "Membuat file Service/Repository baru");
        printf($mask, "make:dto <module> <nama>", "Membuat file Data Transfer Object baru");

        // --- SECTION: MAINTENANCE ---
        echo PHP_EOL . self::COLOR_YELLOW . "   Maintenance:" . self::COLOR_RESET . PHP_EOL;
        printf($mask, "delete:module <nama>", "Menghapus module beserta seluruh isinya");
        printf($mask, "delete:action <module> <nama>", "Menghapus file Action tertentu");
        printf($mask, "delete:service <module> <nama>", "Menghapus file Service/Repository tertentu");
        printf($mask, "delete:dto <module> <nama>", "Menghapus file DTO tertentu");

        echo PHP_EOL;
    }

}