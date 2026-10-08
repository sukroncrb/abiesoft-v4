<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Actions;

use Abiesoft\App\Modules\Home\Services\WellcomeRepository;
use Abiesoft\App\Shared\Helpers\Utilities\ApiResult;
use Abiesoft\System\Utilities\Input;

readonly class PostSampleHomeAction
{
    use ApiResult;
    public function __invoke(): void
    {
        $repo = new WellcomeRepository();
        $repo->postSampleDataWithPhp();
    }
}