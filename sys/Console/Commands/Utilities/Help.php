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
        echo self::COLOR_CYAN . "   Abiesoft Framework CLI" . self::COLOR_RESET . " version 1.2.0 (Hybrid PHP + Go)" . PHP_EOL;
        echo self::COLOR_RESET . "   Usage: php abiesoft " . self::COLOR_YELLOW . "[command]" . self::COLOR_RESET . " [options]" . PHP_EOL . PHP_EOL;

        $mask = "   " . self::COLOR_GREEN . "%-38s" . self::COLOR_RESET . " %s" . PHP_EOL;

        echo self::COLOR_YELLOW . "   Available Commands:" . self::COLOR_RESET . PHP_EOL;
        printf($mask, "start", "Menjalankan server hybrid development lokal");
        printf($mask, "route", "Menampilkan daftar route terdaftar (PHP & Go)");
        printf($mask, "build", "Mengompilasi binary engine Golang");
        printf($mask, "test [module] [--go]", "Menjalankan unit test untuk PHP dan Golang");
        printf($mask, "database:import", "Mengimpor skema file SQL ke database");
        printf($mask, "help", "Menampilkan menu bantuan ini");

        echo PHP_EOL . self::COLOR_YELLOW . "   Generators:" . self::COLOR_RESET . PHP_EOL;
        printf($mask, "make:module <nama> [--go]", "Membuat modul lengkap PHP atau Golang (--go)");
        printf($mask, "make:action <module> <nama> [--go]", "Membuat file Action baru (PHP atau Golang)");
        printf($mask, "make:service <module> <nama> [--go]", "Membuat file Service/Repository (PHP atau Golang)");
        printf($mask, "make:dto <module> <nama> [--go]", "Membuat file Data Transfer Object (PHP atau Golang)");
        printf($mask, "make:test <module> [nama] [--go]", "Membuat file Unit Test baru (PHP atau Golang)");

        echo PHP_EOL . self::COLOR_YELLOW . "   Maintenance:" . self::COLOR_RESET . PHP_EOL;
        printf($mask, "delete:module <nama> [--go]", "Menghapus module, rute, tabel & skema");
        printf($mask, "delete:action <module> <nama> [--go]", "Menghapus file Action tertentu");
        printf($mask, "delete:service <module> <nama> [--go]", "Menghapus file Service/Repository tertentu");
        printf($mask, "delete:dto <module> <nama> [--go]", "Menghapus file DTO tertentu");
        printf($mask, "delete:test <module> [nama] [--go]", "Menghapus file Unit Test tertentu");

        echo PHP_EOL;
    }

}