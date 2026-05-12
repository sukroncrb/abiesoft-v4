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
        $fullOutputPath = $root . "/" . $outputPath;

        echo "\n\e[34m--- AbieSoft Framework Build System ---\e[0m\n";

        // 1. Hapus file lama untuk memastikan build yang sekarang benar-benar baru
        if (file_exists($fullOutputPath)) {
            @unlink($fullOutputPath);
        }

        if (file_exists($fileSock)) {
            @unlink($fileSock);
        }

        $descriptorspec = [
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"]  // stderr
        ];

        // Gunakan 'amd64' secara eksplisit
        $cmd = "cd $root && go mod tidy && GOOS=linux GOARCH=amd64 go build -o $outputPath $sourcePath";
        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (is_resource($process)) {
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            $spinner = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
            $i = 0;

            while (proc_get_status($process)['running']) {
                echo "\r\e[32m" . $spinner[$i % count($spinner)] . "\e[0m Mengompilasi engine Go... \r";
                $i++;
                usleep(100000);
            }

            $stderr = stream_get_contents($pipes[2]);
            foreach ($pipes as $pipe) { fclose($pipe); }
            
            proc_close($process);

            // 2. LOGIKA VALIDASI: Jangan cuma percaya Exit Code. 
            // Cek apakah file output benar-benar tercipta/ada.
            if (file_exists($fullOutputPath)) {
                echo "\r\e[32m✔\e[0m Kompilasi Selesai! [Binary: $outputPath]          \n\n";
            } else {
                echo "\r\e[31m✘\e[0m Kompilasi Gagal!                                 \n";
                if (!empty($stderr)) {
                    echo "\e[33mDetail Error:\e[0m\n" . trim($stderr) . "\n\n";
                } else {
                    echo "\e[31mError: File binary tidak ditemukan di $outputPath\e[0m\n\n";
                }
            }
        }
    }
}