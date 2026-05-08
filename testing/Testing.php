<?php

declare(strict_types=1);

namespace Abiesoft\Testing;

use Abiesoft\App\Shared\Helpers\PiGoCaller;

readonly class Testing
{

    use PiGoCaller;
    public function __invoke()
    {
        print_r($this->call("wellcome"));
    }
}