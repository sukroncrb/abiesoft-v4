<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Actions;

use Abiesoft\App\Modules\Home\Services\WellcomeRepository;
use Abiesoft\System\View\ViewRenderer;

readonly class WellcomeHomeAction
{
    public function __invoke($info, $getby): void
    {

        if($info == "start"){
            $info = "Halo, Programmer!";
        }

        if($info == "pengenalan"){
            $info = "Ini adalah framework hybrid";
        }

        if($info == "fitur1"){
            $info = "Anda bisa gunakan golang untuk api";
        }

        if($info == "fitur2"){
            $info = "Anda juga bisa gunakan php untuk api";
        }

        if($info == "ending"){
            $info = "Selamat mencoba.";
        }

        $repo = new WellcomeRepository();

        if($getby == "go"){
            $repo->getAllWithGo($info);
        }else{
            $repo->getAllWithPhp($info);
        }
    }
}