<?php

namespace Abiesoft\System\Console;

use Abiesoft\System\Console\Commands\DatabaseImportCommand;
use Abiesoft\System\Console\Commands\DeleteActionCommand;
use Abiesoft\System\Console\Commands\DeleteDtoCommand;
use Abiesoft\System\Console\Commands\DeleteModuleCommand;
use Abiesoft\System\Console\Commands\DeleteServiceCommand;
use Abiesoft\System\Console\Commands\MakeActionCommand;
use Abiesoft\System\Console\Commands\MakeDtoCommand;
use Abiesoft\System\Console\Commands\MakeModuleCommand;
use Abiesoft\System\Console\Commands\MakeServiceCommand;
use Abiesoft\System\Console\Commands\Utilities\Compile;
use Abiesoft\System\Console\Commands\Utilities\Help;
use Abiesoft\System\Console\Commands\Utilities\Routes;
use Abiesoft\System\Console\Commands\Utilities\Start;

class Kernel
{ 

    use Compile, Help, Routes, Start;

    public function handle(array $args): void
    {
        $command = $args[1] ?? 'help';

        match ($command) {
            'start'        => $this->startServer(),
            'route'        => $this->showRoutes(),
            'build'        => $this->compileGo(),
            
            'make:action'  => (new MakeActionCommand())->handle($args),
            'make:dto'     => (new MakeDtoCommand())->handle($args),
            'make:service' => (new MakeServiceCommand())->handle($args),
            'make:module'  => (new MakeModuleCommand())->handle($args),
            'delete:module' => (new DeleteModuleCommand())->handle($args),
            'delete:action'  => (new DeleteActionCommand())->handle($args),
            'delete:dto'     => (new DeleteDtoCommand())->handle($args),
            'delete:service' => (new DeleteServiceCommand())->handle($args),

            'database:import' => (new DatabaseImportCommand())->handle($args),
            
            'help'         => $this->showHelp(),
            default        => $this->tampilkanError("Perintah '$command' tidak dikenali."),
        };
    }

    private function tampilkanError(string $message): void
    {
        echo self::COLOR_RED . "Error: " . $message . self::COLOR_RESET . PHP_EOL;
    }


}