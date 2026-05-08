<?php

namespace Abiesoft\System\Console;

use Abiesoft\System\Console\Commands\DatabaseImportCommand;
use Abiesoft\System\Console\Commands\DeleteActionCommand;
use Abiesoft\System\Console\Commands\DeleteDtoCommand;
use Abiesoft\System\Console\Commands\DeleteModuleCommand;
use Abiesoft\System\Console\Commands\DeleteServiceCommand;
use Abiesoft\System\Console\Commands\MakeActionCommand;
use Abiesoft\System\Console\Commands\MakeDtoCommand;
use Abiesoft\System\Console\Commands\MakeModuleCommand;
use Abiesoft\System\Console\Commands\MakeServiceCommand;

class Kernel
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

    public function handle(array $args): void
    {
        $command = $args[1] ?? 'help';

        match ($command) {
            'start'        => $this->startServer(),
            'route'        => $this->showRoutes(),
            
            /*


                Memanggil external command.
                Lokasi commandnya ada di folder sys/Console/Commands
            */
            'make:action'  => (new MakeActionCommand())->handle($args),
            'make:dto'     => (new MakeDtoCommand())->handle($args),
            'make:service' => (new MakeServiceCommand())->handle($args),
            'make:module'  => (new MakeModuleCommand())->handle($args),
            'delete:module' => (new DeleteModuleCommand())->handle($args),
            'delete:action'  => (new DeleteActionCommand())->handle($args),
            'delete:dto'     => (new DeleteDtoCommand())->handle($args),
            'delete:service' => (new DeleteServiceCommand())->handle($args),

            'database:import' => (new DatabaseImportCommand())->handle($args),
            
            'help'         => $this->showHelp(),
            default        => $this->tampilkanError("Perintah '$command' tidak dikenali."),
        };
    }

    private function startServer(): void
    {
        $host = $_ENV['SERVER_HOST'] ?? '127.0.0.1';
        $port = $_ENV['SERVER_PORT'] ?? '8000';
        echo self::COLOR_GREEN . "🚀 Menyalakan Abiesoft Server di http://{$host}:{$port}..." . self::COLOR_RESET . PHP_EOL;
        passthru("php -S {$host}:{$port} -t public");
    }

    private function showRoutes(): void
    {

        $router = new \Abiesoft\System\Http\Router();

        $routesPath = __DIR__ . '/../../routes/web.php';
        
        if (file_exists($routesPath)) {
            require_once $routesPath;
        } else {
            $this->tampilkanError("File routes/web.php tidak ditemukan.");
            return;
        }

        $allRoutes = $router->getRoutes();

        echo PHP_EOL;
        echo self::COLOR_BLUE . "Daftar Route Abiesoft" . self::COLOR_RESET . PHP_EOL;
        echo str_repeat("-", 85) . PHP_EOL;
        
        printf(
            "%-10s %-25s %-30s %-20s" . PHP_EOL, 
            "METHOD", "URI", "ACTION", "MIDDLEWARE"
        );
        echo str_repeat("-", 85) . PHP_EOL;

        foreach ($allRoutes as $method => $uris) {
            
            $methodColor = match($method) {
                'GET' => self::COLOR_GREEN,
                'POST' => self::COLOR_YELLOW,
                'PUT' => self::COLOR_BLUE,
                'DELETE' => self::COLOR_RED,
                default => self::COLOR_RESET
            };

            foreach ($uris as $uri => $routeData) {
                
                $action = $routeData['action'];
                
                $middlewareList = $routeData['middleware'];
                $middlewareStr = empty($middlewareList) ? '-' : implode(', ', array_map(function($m) {
                    return (new \ReflectionClass($m))->getShortName(); 
                }, $middlewareList));

                printf(
                    "%s%-10s%s %-25s %-30s %-20s" . PHP_EOL,
                    $methodColor, $method, self::COLOR_RESET, 
                    $uri,
                    strlen($action) > 28 ? '...' . substr($action, -25) : $action,
                    $middlewareStr
                );
            }
        }
        echo str_repeat("-", 85) . PHP_EOL;
        echo PHP_EOL;
    }

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
        echo "   " . self::COLOR_BLUE . "Contoh:" . self::COLOR_RESET . " php abiesoft delete:action alumni index" . PHP_EOL . PHP_EOL;
    }

    private function tampilkanError(string $message): void
    {
        echo self::COLOR_RED . "Error: " . $message . self::COLOR_RESET . PHP_EOL;
    }
}