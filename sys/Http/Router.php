<?php

declare(strict_types=1);

namespace Abiesoft\System\Http;

use Abiesoft\App\Modules\Token\Actions\CsrfTokenAction;
use Abiesoft\App\Shared\Middleware\ApiMiddleware;
use Abiesoft\System\Auth\GetRefreshBearerTokenAction;
use Abiesoft\System\Auth\GetRefreshTokenAction;
use Abiesoft\System\Auth\LogoutAuthAction;
use Abiesoft\System\Utilities\Generate;
use Abiesoft\System\Utilities\Reader;
use Abiesoft\System\View\ViewRenderer;
use Abiesoft\Testing\Testing;

class Router
{
    
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
        $this->get("/api/auth/refresh-bearer", GetRefreshBearerTokenAction::class, [ ApiMiddleware::class ]);
    }
        
    public function resolve(string $uri, string $method): void
    {
        $this->routeSys();
        $this->initApp();

        $path = parse_url($uri, PHP_URL_PATH);
        $routes = $this->routes[$method] ?? [];

        $matchedRoute = null;
        $params = [];

        // 1. Cari route yang cocok (Dinamis / Statis)
        foreach ($routes as $routePath => $routeData) {
            
            // Ubah format {parameter} jadi Regex. Contoh: /api/csrf/{token}
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[^/]+)', $routePath);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $path, $matches)) {
                $matchedRoute = $routeData;
                
                // Ambil parameter yang ditangkap regex (buang index angka)
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                break;
            }
        }

        if (!$matchedRoute) {

            if ($_ENV['MODE'] == "develope") {
                http_response_code(404);
                echo "
                    <div style='position: absolute; top: 0; right: 0; bottom: 0; left: 0; align-items: center; justify-content: center; display: flex; color: #bdbdbd; font-family: Arial;'>
                        <div>
                            <div style='text-align: center; font-size: 60pt;'>404</div>
                            <div style='text-align: center; margin-top: 5px;'>Halaman Tidak Ditemukan</div>
                        </div>
                    </div>
                ";
                return;
            }else{
                header('Location: /'.$_ENV['LOGIN_PAGE']);
            }

        }

        /*
            Menambahkan middleware agar halaman bisa diatur hak akses usernya
        */
        foreach ($matchedRoute['middleware'] as $middlewareClass) {
            $middleware = new $middlewareClass();
            if (!$middleware->handle()) {
                return;
            }
        }
        
        $actionClass = $matchedRoute['action'];
        
        if (class_exists($actionClass)) {
            $action = new $actionClass();
            
            if (method_exists($action, '__invoke')) {
                
                $reflector = new \ReflectionMethod($actionClass, '__invoke');
                $parameters = $reflector->getParameters();
                
                $args = [];
                
                // 2. Injeksi ViewRenderer ATAU Parameter dari URL
                foreach ($parameters as $param) {
                    $paramName = $param->getName();
                    $type = $param->getType();
                    
                    // Jika butuh ViewRenderer, berikan ViewRenderer
                    if ($type && !$type->isBuiltin() && $type->getName() === ViewRenderer::class) {
                        $args[] = new ViewRenderer();
                    } 
                    // Jika nama variabel cocok dengan parameter dinamis URL (contoh: $token)
                    elseif (array_key_exists($paramName, $params)) {
                        $args[] = $params[$paramName];
                    } 
                    // Jika tidak ada keduanya, set default value atau null
                    else {
                        $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                    }
                }

                $action(...$args);

            } else {
                echo "Error: Class $actionClass harus punya method __invoke()";
            }
            
        } else {
            echo "Error: Class $actionClass tidak ditemukan.";
        }
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