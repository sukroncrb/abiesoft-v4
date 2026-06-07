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
        $tech = $input->get("tech", "PHP");
        if ($tech === "on" || strtolower($tech) === "golang") {
            $_POST['tech'] = "Golang"; 
            $repo->postSampleDataWithGolang();
        } else {
            $_POST['tech'] = "PHP"; 
            $repo->postSampleDataWithPhp();
        }
    }
}