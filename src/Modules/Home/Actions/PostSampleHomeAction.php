<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Actions;

use Abiesoft\App\Modules\Home\Services\WellcomeRepository;
use Abiesoft\App\Shared\Helpers\ApiResult;
use Abiesoft\System\Utilities\Input;
use Abiesoft\System\View\ViewRenderer;

readonly class PostSampleHomeAction
{
    use ApiResult;
    public function __invoke(): void
    {
        $input = new Input();
        $repo = new WellcomeRepository();
        if($input->get("tech") == "on") {
            $_POST['tech'] == "golang";
            $repo->postSampleDataWithGolang();
        }else{
            $_POST['tech'] == "php";
            $repo->postSampleDataWithPhp();
        }
    }
}