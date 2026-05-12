<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands\Utilities;

trait Compile
{

    public function compileGo() {
        $root = dirname(__DIR__, 4);
        $outputPath = "sys/pigo/bin/pigo-engine";
        $sourcePath = "./sys/pigo";
        $os = $_ENV['OS'];
        echo "--- Compiling PiGo Engine ---\n";
        $cmd = "cd $root && GOOS=linux GOARCH=$os go build -o $outputPath $sourcePath";
        $output = [];
        $resultCode = 0;
        exec($cmd . " 2>&1", $output, $resultCode);

        if ($resultCode === 0) {
            echo "Success: Binary created at $outputPath\n";
        } else {
            echo "Error during build:\n";
            echo implode("\n", $output);
        }
    }

}