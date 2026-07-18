<?php

declare(strict_types=1);

namespace Abiesoft\System\Auth;

use Abiesoft\System\Session\SessionManager;

readonly class LogoutAuthAction
{
    public function __invoke(): void
    {   
        $sesi = new SessionManager();
        $sesi->logout();
    }
}