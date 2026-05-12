<?php

declare(strict_types=1);

namespace Abiesoft\System\Auth;

readonly class GetRefreshTokenAction
{
    public function __invoke($fid): void
    {
        $repo = new Token;
        $repo->getToken($fid);
    }
}