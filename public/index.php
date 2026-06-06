<?php


date_default_timezone_set("Asia/Bangkok");

use Abiesoft\System\Exception\ErrorHandler;
use Abiesoft\System\Http\PiGoEngine;
use Abiesoft\System\Http\Router;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

ErrorHandler::register();

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$pigo = new PiGoEngine();
try {
    $pigo->pastikanGoEngineRun();
} catch (Exception $e) {
    die("Framework Error: " . $e->getMessage());
}

$router = new Router();

/*


    Atur Routenya di Folder routes/web.php
*/
require_once __DIR__ . '/../routes/web.php';

$router->resolve($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);