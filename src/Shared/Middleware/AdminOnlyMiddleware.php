<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Middleware;

use Abiesoft\App\Shared\Helpers\Konten\Define;
use Abiesoft\System\Http\MiddlewareInterface;

class AdminOnlyMiddleware implements MiddlewareInterface
{
    
    /*


        ---------------------------------------------------------------
        Memastikan bahwa halaman ini diperuntukan
        untuk user yang sudah login
        ---------------------------------------------------------------
    */
    use Define;
    
    public function handle(): bool
    {
        if ($this->defineOpsi('sesi_role') != "admin") {
            header('Location: /'.$_ENV['LOGIN_PAGE']);
            exit; 
            return false;
        }
        return true;
    }
}