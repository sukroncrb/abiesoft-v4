<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands\Utilities;

trait Routes
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

    private function showRoutes(): void
    {

        $router = new \Abiesoft\System\Http\Router();

        $routesPath = __DIR__ . '/../../../../routes/web.php';
        
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

    private function tampilkanError(string $message): void
    {
        echo self::COLOR_RED . "Error: " . $message . self::COLOR_RESET . PHP_EOL;
    }

}