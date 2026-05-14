<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Actions;

use Abiesoft\App\Modules\Home\Services\WellcomeRepository;
use Abiesoft\System\View\ViewRenderer;

readonly class SampleOnlyDataHomeAction
{
    public function __invoke($id): void
    {
        $repo = new WellcomeRepository();
        $repo->getOnlySampleData($id);
    }
}