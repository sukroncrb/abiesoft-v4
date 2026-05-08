<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Middleware;

use Abiesoft\System\Http\MiddlewareInterface;
use Abiesoft\System\Session\SessionManager;

class AuthMiddleware implements MiddlewareInterface
{
    
    /*


        ---------------------------------------------------------------
        Memastikan bahwa halaman ini diperuntukan
        untuk user yang sudah login
        ---------------------------------------------------------------
    */
    public function handle(): bool
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        $sesi = new SessionManager();
        if (!$sesi->isLogin()) {
            header('Location: /'.$_ENV['LOGIN_PAGE']);
            exit; 
            return false;
        }
        return true;
    }
}