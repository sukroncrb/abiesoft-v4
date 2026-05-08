<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Actions;

use Abiesoft\System\View\ViewRenderer;


readonly class ShowHomeAction
{
    public function __invoke(ViewRenderer $view): void
    {
        $view->render('page/home/index',[
            'title' => 'Selamat Datang Di Abiesoft',
        ]);
    }
}