<?php

declare(strict_types=1);

namespace Abiesoft\System\Http;

use Abiesoft\App\Shared\Helpers\ApiResult;
use Abiesoft\App\Shared\Middleware\ApiMiddleware;
use Abiesoft\System\Auth\CsrfTokenAction;
use Abiesoft\System\Auth\GetRefreshBearerTokenAction;
use Abiesoft\System\Auth\GetRefreshTokenAction;
use Abiesoft\System\Auth\LogoutAuthAction;
use Abiesoft\System\Utilities\Generate;
use Abiesoft\System\Utilities\Reader;
use Abiesoft\System\View\ViewRenderer;
use Abiesoft\Testing\Testing;

class Router
{

    use ApiResult;
    
    private array $routes = [];
    private string $groupPrefix = '';

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }
    
    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }
    
    public function group(string $prefix, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix .= '/' . trim($prefix, '/');
        $callback($this);
        $this->groupPrefix = $previousPrefix;
    }

    /*


        Menyimpan Route kedalam array $routes
    */
    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {

        $cleanPath = trim($path, '/');
    
        $finalPath = $this->groupPrefix;
        
        if ($cleanPath !== '') {
            $finalPath .= '/' . $cleanPath;
        }

        if ($finalPath === '') {
            $finalPath = '/';
        }

        $this->routes[$method][$finalPath] = [
            'action' => $handler,
            'middleware' => $middleware
        ];

    }

    /*


        Menampilkan route berdasarkan url browser
    */

    protected function routeSys() {
        $this->get("/testing",Testing::class);
        $this->get("/api/logout", LogoutAuthAction::class, [ ApiMiddleware::class ]);
        $this->get("/api/token/{fid}", GetRefreshTokenAction::class, [ ApiMiddleware::class ]);
        $this->get("/api/auth/refresh-token", GetRefreshBearerTokenAction::class, [ ApiMiddleware::class ]);
    }
        
    public function resolve(string $uri, string $method): void
    {
        $this->routeSys();
        $this->initApp();

        $path = parse_url($uri, PHP_URL_PATH);
        $routes = $this->routes[$method] ?? [];

        $matchedRoute = null;
        $params = [];

        // 1. Cari route yang cocok
        foreach ($routes as $routePath => $routeData) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $routePath);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $path, $matches)) {
                $matchedRoute = $routeData;
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                break;
            }
        }

        // PERBAIKAN 1: Jika route tidak ditemukan, throw Exception!
        if (!$matchedRoute) {
            throw new \Abiesoft\System\Exception\HttpException("Halaman atau Endpoint '[$method] $path' Tidak Ditemukan", 404);
        }

        /*
            Menambahkan middleware
        */
        foreach ($matchedRoute['middleware'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            if (!$middleware->handle()) {
                return;
            }
        }
        
        $actionClass = $matchedRoute['action'];
        
        // PERBAIKAN 2: Jika Action Class tidak ditemukan di folder App/System
        if (!class_exists($actionClass)) {
            throw new \Abiesoft\System\Exception\HttpException("Controller atau Action Class '$actionClass' tidak ditemukan di sistem AbieSoft.", 500);
        }

        $action = new $actionClass();
        
        // PERBAIKAN 3: Jika Action Class ada tapi lupa membuat method __invoke
        if (!method_exists($action, '__invoke')) {
            throw new \Abiesoft\System\Exception\HttpException("Class '$actionClass' wajib memiliki method __invoke() sebagai entry point router.", 500);
        }
            
        $reflector = new \ReflectionMethod($actionClass, '__invoke');
        $parameters = $reflector->getParameters();
        
        $args = [];
        
        foreach ($parameters as $param) {
            $paramName = $param->getName();
            $type = $param->getType();
            
            if ($type && !$type->isBuiltin() && $type->getName() === ViewRenderer::class) {
                $args[] = new ViewRenderer();
            } 
            elseif (array_key_exists($paramName, $params)) {
                $args[] = $params[$paramName];
            } 
            else {
                $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }
        }

        $action(...$args);
    }

    protected function initApp() {

        $this->routes['GET']['/api/csrf/{token}'] = [
            'action' => CsrfTokenAction::class,
            'middleware' => [],
        ];

        $cookie = new Cookie();
        $reader = new Reader();
        $generate = new Generate();

        if($cookie->has("_cf_v3")){
            $cf = $cookie->get("_cf_v3"); 
            $secretKey = $_ENV['SECRET_KEY'];

            $decodedData = $reader->secretCode($cf, $secretKey);
            
            if (!is_array($decodedData)) {
                $decodedData = [];
            }
            
            $inisial = $decodedData['inisial'] ?? '';
            $remember = $decodedData['remember'] ?? '';
            
            if($inisial == null || $inisial == ''){
                $inisial = $generate->angka('6');
            }

            $notifikasi = $decodedData['seting']['notifikasi'] ?? true;
            $suara = $decodedData['seting']['suara'] ?? 'popcorn';
            
            $data = [
                'apikey' => $_ENV['APIKEY'],
                'inisial' => $inisial,
                'remember' => $remember,
                'timestamp' => time(),
                'seting' => [
                    'notifikasi' => $notifikasi,
                    'suara' => $suara
                ]
            ];
            
            $kode = $generate->secretCode($data, $secretKey);
            $cookie->set("_cf_v3", $kode);

            return;
            
        } else {
            
            $data = [
                'apikey' => $_ENV['APIKEY'], 
                'inisial' => $generate->angka('6'),
                'timestamp' => time(),
                'seting' => [
                    'notifikasi' => true,
                    'suara' => 'popcorn'
                ]
            ];
            
            $kode = $generate->secretCode($data, $_ENV['SECRET_KEY']); 
            $cookie->set("_cf_v3", $kode);

            return;
        }

    }

    /*


        Fungsi ini digunakan untuk kebutuhan CLI (php abiesoft route)
    */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}