<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Middleware;

use Abiesoft\System\Http\MiddlewareInterface;

class NoCacheMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        return true;
    }
    
}