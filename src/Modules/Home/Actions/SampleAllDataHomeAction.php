<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Actions;

use Abiesoft\App\Modules\Home\Services\WellcomeRepository;
use Abiesoft\System\Database\DB;

readonly class SampleAllDataHomeAction
{
    public function __invoke(): void
    {
        $repo = new WellcomeRepository();
        $repo->getAllSampleData();
    }
}