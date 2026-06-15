<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands\Utilities;

trait Compile
{
    public function compileGo() 
    {
        $root = dirname(__DIR__, 4);
        $outputPath = "sys/pigo/bin/pigo-engine";
        $fileSock = "sys/pigo/pigo.sock";
        $sourcePath = "./sys/pigo";
        
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            $outputPath .= ".exe";
        }

        $fullOutputPath = $root . "/" . $outputPath;
        
        
        $arch = isset($_ENV['ARCH_OS']) ? trim((string)$_ENV['ARCH_OS']) : 'amd64';
        if (empty($arch)) {
            $arch = 'amd64';
        }

        echo "\n\e[34m--- AbieSoft Framework Build System ---\e[0m\n";

        if (file_exists($fullOutputPath)) {
            @unlink($fullOutputPath);
        }

        if (file_exists($fileSock)) {
            @unlink($fileSock);
        }

        $descriptorspec = [
            1 => ["pipe", "w"],
            2 => ["pipe", "w"] 
        ];

        
        $cmd = "cd " . escapeshellarg($root) . " && go mod tidy && go build -o " . escapeshellarg($outputPath) . " " . escapeshellarg($sourcePath);

        
        $envVars = array_merge($_ENV, getenv(), [
            'GOOS'   => $isWindows ? 'windows' : 'linux',
            'GOARCH' => $arch
        ]);

        
        $process = proc_open($cmd, $descriptorspec, $pipes, $root, $envVars);

        if (is_resource($process)) {
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            $spinner = ['|', '/', '-', '\\']; 
            $i = 0;

            while (proc_get_status($process)['running']) {
                echo "\r\e[32m" . $spinner[$i % count($spinner)] . "\e[0m Mengompilasi engine Go... \r";
                $i++;
                usleep(100000);
            }

            $stderr = stream_get_contents($pipes[2]);
            foreach ($pipes as $pipe) { fclose($pipe); }
            
            proc_close($process);

            if (file_exists($fullOutputPath)) {
                echo "\r\e[32m✔\e[0m Kompilasi Selesai! [Binary: $outputPath]          \n\n";
                
                if (!$isWindows) {
                    chmod($fullOutputPath, 0755);
                }
            } else {
                echo "\r\e[31m✘\e[0m Kompilasi Gagal!                                 \n";
                if (!empty($stderr)) {
                    echo "\e[33mDetail Error dari Go Compiler:\e[0m\n" . trim($stderr) . "\n\n";
                } else {
                    echo "\e[31mError: File binary tidak ditemukan di $outputPath\e[0m\n\n";
                }
            }
        }
    }
}