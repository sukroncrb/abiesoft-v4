<?php

namespace Abiesoft\System\View;

use Abiesoft\App\Shared\Helpers\Define;
use Latte\Engine;

class ViewRenderer
{
    private Engine $latte;

    use Define;

    public function __construct()
    {
        $this->latte = new Engine();
        $baseDir = dirname(__DIR__, 2); 
        $this->latte->setTempDirectory($baseDir . '/var/cache');
        $this->latte->setAutoRefresh(true);
    }

    public function render(string $template, array $params = []): void
    {
        /*


            Path: templates/pages/home/index.latte
        */
        $combinedParams = array_merge($params, $this->defineOpsi());
        $file = dirname(__DIR__, 2) . '/templates/' . $template . '.latte';
        $this->latte->render($file, $combinedParams);
    }
}