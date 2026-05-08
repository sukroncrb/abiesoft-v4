<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands;

class MakeActionCommand extends BaseCommand
{
    public function handle(array $args): void
    {
        $moduleInput = $args[2] ?? null;
        $actionInput = $args[3] ?? null;

        if (!$moduleInput || !$actionInput) {
            $this->tampilkanError("Parameter kurang.\nGunakan: php abiesoft make:action <module> <action>");
            return;
        }

        $namaModule = ucfirst($moduleInput);
        $namaAction = ucfirst($actionInput);
        $namaClass  = "{$namaAction}{$namaModule}Action";

        $folderPath = dirname(__DIR__, 3) . '/src/Modules/' . $namaModule . '/Actions';
        $filePath   = $folderPath . '/' . $namaClass . '.php';

        $this->buatFolder($folderPath);

        if (file_exists($filePath)) {
            $this->tampilkanError("File $namaClass.php sudah ada!");
            return;
        }

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        namespace Abiesoft\App\Modules\\{$namaModule}\Actions;

        use Abiesoft\System\View\ViewRenderer;

        readonly class {$namaClass}
        {
            public function __invoke(ViewRenderer \$view): void
            {
                // \$view->render('pages/{$moduleInput}/{$actionInput}',['title' => 'Halaman {$moduleInput} {$actionInput}']);
            }
        }
        PHP;

        $this->buatFIle($filePath, $content, $namaClass);
    }
}