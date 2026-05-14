<?php

use Abiesoft\App\Modules\Home\Actions\SampleAllDataHomeAction;
use Abiesoft\App\Modules\Home\Actions\SampleBigDataHomeAction;
use Abiesoft\App\Modules\Home\Actions\SampleOnlyDataHomeAction;
use Abiesoft\App\Modules\Home\Actions\ShowHomeAction;
use Abiesoft\App\Modules\Home\Actions\WellcomeHomeAction;
use Abiesoft\App\Shared\Middleware\ApiMiddleware;

/** @var \Abiesoft\System\Http\Router $router */




$router->get('/', ShowHomeAction::class);
$router->get('/api/wellcome/{info}/{getby}', WellcomeHomeAction::class, [
    ApiMiddleware::class
]);

$router->get('/api/sample', SampleAllDataHomeAction::class, [
    ApiMiddleware::class
]);

$router->get('/api/sample/{offset}/{limit}', SampleBigDataHomeAction::class, [
    ApiMiddleware::class
]);

$router->get('/api/sample/{id}', SampleOnlyDataHomeAction::class, [
    ApiMiddleware::class
]);
