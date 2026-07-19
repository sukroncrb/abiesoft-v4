<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Actions;

use Abiesoft\App\Modules\Home\Services\WellcomeRepository;

readonly class SampleBigDataHomeAction
{
    public function __invoke($offset, $limit): void
    {
        $repo = new WellcomeRepository();
        $repo->getSampleBigData($offset, $limit);
    }
}