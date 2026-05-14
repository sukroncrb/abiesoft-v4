<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands\Utilities;

trait Start
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

    private function startServer(): void
    {
        $host = $_ENV['SERVER_HOST'] ?? '127.0.0.1';
        $port = $_ENV['SERVER_PORT'] ?? '8000';
        $publif_folder  = $_ENV['PUBLIC_FOLDER'] ?? 'public';
        echo self::COLOR_GREEN . "🚀 Menyalakan Abiesoft Server di http://{$host}:{$port}..." . self::COLOR_RESET . PHP_EOL;
        passthru("php -S {$host}:{$port} -t {$publif_folder}");
    }

}