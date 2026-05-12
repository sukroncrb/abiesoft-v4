<?php

declare(strict_types=1);

namespace Abiesoft\System\Auth;

use Abiesoft\System\Session\SessionManager;
use Abiesoft\System\View\ViewRenderer;

readonly class LogoutAuthAction
{
    public function __invoke(ViewRenderer $view): void
    {   
        $sesi = new SessionManager();
        $sesi->logout();
    }
}