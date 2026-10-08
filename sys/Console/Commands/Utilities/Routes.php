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
    private const COLOR_MAGENTA = "\033[35m";

    private function showRoutes(): void
    {
        $router = new \Abiesoft\System\Http\Router();
        $routesPath = dirname(__DIR__, 4) . '/routes/web.php';
        
        if (file_exists($routesPath)) {
            require_once $routesPath;
        } else {
            $this->tampilkanError("File routes/web.php tidak ditemukan.");
            return;
        }

        $phpRoutes = $router->getRoutes();
        $goRoutes  = $this->getGoRoutes();

        $totalPhp = 0;
        foreach ($phpRoutes as $method => $uris) {
            $totalPhp += count($uris);
        }
        $totalGo = count($goRoutes);

        echo PHP_EOL;
        echo self::COLOR_CYAN . "==========================================================================================" . self::COLOR_RESET . PHP_EOL;
        echo self::COLOR_CYAN . "                           Daftar Route AbieSoft Framework                                " . self::COLOR_RESET . PHP_EOL;
        echo self::COLOR_CYAN . "==========================================================================================" . self::COLOR_RESET . PHP_EOL;

        // -----------------------------------------------------------------
        // 1. RUTE PHP CORE
        // -----------------------------------------------------------------
        echo PHP_EOL . self::COLOR_BLUE . "🐘 [PHP Core Routes] (routes/web.php)" . self::COLOR_RESET . PHP_EOL;
        echo str_repeat("-", 90) . PHP_EOL;
        printf(
            "%-8s %-32s %-30s %-16s" . PHP_EOL, 
            "METHOD", "URI", "ACTION", "MIDDLEWARE"
        );
        echo str_repeat("-", 90) . PHP_EOL;

        foreach ($phpRoutes as $method => $uris) {
            $methodColor = match($method) {
                'GET'    => self::COLOR_GREEN,
                'POST'   => self::COLOR_YELLOW,
                'PUT'    => self::COLOR_BLUE,
                'DELETE' => self::COLOR_RED,
                default  => self::COLOR_RESET
            };

            foreach ($uris as $uri => $routeData) {
                $action = $routeData['action'];
                $shortAction = class_exists($action) ? (new \ReflectionClass($action))->getShortName() : $action;
                
                $middlewareList = $routeData['middleware'];
                $middlewareStr = empty($middlewareList) ? '-' : implode(', ', array_map(function($m) {
                    return (new \ReflectionClass($m))->getShortName(); 
                }, $middlewareList));

                printf(
                    "%s%-8s%s %-32s %-30s %-16s" . PHP_EOL,
                    $methodColor, $method, self::COLOR_RESET, 
                    $uri,
                    strlen($shortAction) > 28 ? substr($shortAction, 0, 25) . '...' : $shortAction,
                    $middlewareStr
                );
            }
        }

        // -----------------------------------------------------------------
        // 2. RUTE GOLANG NATIVE DIRECT
        // -----------------------------------------------------------------
        echo PHP_EOL . self::COLOR_CYAN . "⚡ [Golang Native Direct Routes] (src/Modules/handler.go -> High Performance Bypass)" . self::COLOR_RESET . PHP_EOL;
        echo str_repeat("-", 90) . PHP_EOL;
        printf(
            "%-8s %-32s %-36s %-12s" . PHP_EOL, 
            "METHOD", "URI", "HANDLER ACTION", "ENGINE"
        );
        echo str_repeat("-", 90) . PHP_EOL;

        if (empty($goRoutes)) {
            echo self::COLOR_YELLOW . "   Belum ada rute native Golang yang didaftarkan." . self::COLOR_RESET . PHP_EOL;
        } else {
            foreach ($goRoutes as $goRoute) {
                $method = $goRoute['method'];
                $methodColor = match($method) {
                    'GET'    => self::COLOR_GREEN,
                    'POST'   => self::COLOR_YELLOW,
                    'PUT'    => self::COLOR_BLUE,
                    'DELETE' => self::COLOR_RED,
                    default  => self::COLOR_MAGENTA
                };

                $action = $goRoute['action'];

                printf(
                    "%s%-8s%s %-32s %-36s %-12s" . PHP_EOL,
                    $methodColor, $method, self::COLOR_RESET,
                    $goRoute['uri'],
                    strlen($action) > 34 ? substr($action, 0, 31) . '...' : $action,
                    self::COLOR_CYAN . "Direct Go" . self::COLOR_RESET
                );
            }
        }

        echo str_repeat("-", 90) . PHP_EOL;
        echo self::COLOR_YELLOW . "Total: {$totalPhp} Rute PHP Core | {$totalGo} Rute Golang Native Direct" . self::COLOR_RESET . PHP_EOL . PHP_EOL;
    }

    private function getGoRoutes(): array
    {
        $handlerFile = dirname(__DIR__, 4) . '/src/Modules/handler.go';
        if (!file_exists($handlerFile)) {
            return [];
        }

        $content = file_get_contents($handlerFile);
        $routes = [];

        // Mencocokkan: mux.HandleFunc("METHOD /URI", ACTION) atau mux.HandleFunc("/URI", ACTION)
        $pattern = '/mux\.HandleFunc\(\s*"([A-Z]*)\s*([^"]+)"\s*,\s*([^)]+\))/';
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $method = !empty(trim($m[1])) ? trim($m[1]) : 'ANY';
                $uri = trim($m[2]);
                $action = trim($m[3]);
                $routes[] = [
                    'method' => $method,
                    'uri' => $uri,
                    'action' => $action,
                ];
            }
        }

        return $routes;
    }

    private function tampilkanError(string $message): void
    {
        echo self::COLOR_RED . "Error: " . $message . self::COLOR_RESET . PHP_EOL;
    }

}