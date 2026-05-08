<?php

use Abiesoft\App\Modules\Home\Actions\ShowHomeAction;
use Abiesoft\App\Modules\Home\Actions\WellcomeHomeAction;
use Abiesoft\Testing\Testing;

/** @var \Abiesoft\System\Http\Router $router */

/*


    ---------------------------------------------------------------
    Authentication
    ---------------------------------------------------------------
*/
$router->get('/', ShowHomeAction::class);
$router->get('/testing', Testing::class);



$router->get('/api/wellcome/{info}/{getby}', WellcomeHomeAction::class);
