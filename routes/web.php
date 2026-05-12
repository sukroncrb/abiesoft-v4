<?php

use Abiesoft\App\Modules\Home\Actions\ShowHomeAction;
use Abiesoft\App\Modules\Home\Actions\WellcomeHomeAction;
use Abiesoft\App\Shared\Middleware\ApiMiddleware;

/** @var \Abiesoft\System\Http\Router $router */




$router->get('/', ShowHomeAction::class);
$router->get('/api/wellcome/{info}/{getby}', WellcomeHomeAction::class, [
    ApiMiddleware::class
]);
