<?php

declare(strict_types=1);

namespace Abiesoft\System\Auth;

readonly class GetRefreshBearerTokenAction
{
    public function __invoke(): void
    {
        $repo = new Token;
        $repo->getBearer();
    }
}