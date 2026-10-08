<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Tests;

use Abiesoft\System\Testing\TestCase;
use Abiesoft\System\Http\Router;

class HomeTest extends TestCase
{
    public function testRouterInitialization(): void
    {
        $router = new Router();
        $this->assertNotNull($router, "Router instance harus berhasil diinisialisasi");
    }

    public function testRouteRegistration(): void
    {
        $router = new Router();
        $router->get('/test-route', 'TestController::class');
        $routes = $router->getRoutes();

        $this->assertArrayHasKey('GET', $routes, "Route GET harus terdaftar");
        $this->assertArrayHasKey('/test-route', $routes['GET'], "URI /test-route harus terdaftar");
    }

    public function testBasicAssertions(): void
    {
        $this->assertTrue(true);
        $this->assertEquals("AbieSoft", "AbieSoft");
        $this->assertNotEquals("PHP", "Go");
    }
}
