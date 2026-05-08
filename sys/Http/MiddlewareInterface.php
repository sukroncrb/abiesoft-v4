<?php

declare(strict_types=1);

namespace Abiesoft\System\Http;

interface MiddlewareInterface
{
    /*


        Jika outputnya true, maka requestnya akan dilanjutkan.
        Jika outputnya false, maka requestnya berhenti.
    */
    public function handle(): bool;

}