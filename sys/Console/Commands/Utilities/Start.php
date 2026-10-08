<?php

declare(strict_types=1);

namespace Abiesoft\System\Console\Commands\Utilities;

trait Start
{
    private const COLOR_RESET = "\033[0m";
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_RED = "\033[31m";
    private const COLOR_CYAN = "\033[36m";
    private const COLOR_BLUE = "\033[34m";

    private function freePort(string $port): void
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($isWindows) {
            $output = @shell_exec("netstat -ano | findstr :{$port}");
            if ($output && preg_match('/LISTENING\s+(\d+)/', $output, $m)) {
                @shell_exec("taskkill /F /PID {$m[1]} >nul 2>&1");
            }
        } else {
            @shell_exec("fuser -k {$port}/tcp >/dev/null 2>&1");
        }
    }

    private function startServer(): void
    {
        $root = dirname(__DIR__, 4);
        $host = $_ENV['SERVER_HOST'] ?? '127.0.0.1';
        $port = $_ENV['SERVER_PORT'] ?? '3000';
        $phpPort = $_ENV['PHP_PORT'] ?? '8002';
        $publicFolder = $_ENV['PUBLIC_FOLDER'] ?? 'public';

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $binary = $root . "/sys/pigo/bin/pigo-engine" . ($isWindows ? ".exe" : "");

        if (!file_exists($binary)) {
            echo self::COLOR_YELLOW . "Binary pigo-engine belum ditemukan. Melakukan kompilasi..." . self::COLOR_RESET . PHP_EOL;
            $this->compileGo();
        }

        // Bersihkan port jika ada proses zombie sebelumnya yang belum terhenti
        $this->freePort($port);
        $this->freePort($phpPort);
        usleep(150000); // 150ms

        echo PHP_EOL;
        echo self::COLOR_BLUE . "==========================================================" . self::COLOR_RESET . PHP_EOL;
        echo self::COLOR_GREEN . "  🚀 AbieSoft Hybrid Engine Server Started" . self::COLOR_RESET . PHP_EOL;
        echo self::COLOR_BLUE . "==========================================================" . self::COLOR_RESET . PHP_EOL;
        echo "  • Gateway URL        : " . self::COLOR_GREEN . "http://{$host}:{$port}" . self::COLOR_RESET . PHP_EOL;
        echo "  • Golang API Route   : " . self::COLOR_CYAN . "http://{$host}:{$port}/api-go/*" . self::COLOR_RESET . " (Direct Native Go)" . PHP_EOL;
        echo "  • PHP API & Web      : " . self::COLOR_YELLOW . "http://{$host}:{$port}/api/*" . self::COLOR_RESET . " (PHP Core Port :{$phpPort})" . PHP_EOL;
        echo self::COLOR_BLUE . "----------------------------------------------------------" . self::COLOR_RESET . PHP_EOL;
        echo "  Tekan " . self::COLOR_RED . "Ctrl+C" . self::COLOR_RESET . " untuk menghentikan server." . PHP_EOL . PHP_EOL;

        // 1. Jalankan PHP Built-in Server di port internal
        $phpCmd = sprintf("php -S %s:%s -t %s", escapeshellarg($host), escapeshellarg($phpPort), escapeshellarg($publicFolder));
        $phpProcess = proc_open($phpCmd, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $phpPipes, $root);

        if (is_resource($phpProcess) && isset($phpPipes[2])) {
            stream_set_blocking($phpPipes[2], false);
        }

        // Beri jeda 150ms agar PHP server mulai listening
        usleep(150000);

        // 2. Jalankan Go Engine di port gateway
        $goEnv = array_merge($_ENV, getenv(), [
            'SERVER_HOST' => $host,
            'SERVER_PORT' => $port,
            'PHP_PORT'    => $phpPort
        ]);

        $goCmd = escapeshellarg($binary);
        $goProcess = proc_open($goCmd, [
            0 => ['pipe', 'r'],
            1 => STDOUT,
            2 => STDERR
        ], $goPipes, $root, $goEnv);

        // Helper cleanup
        $cleaned = false;
        $cleanup = function () use (&$cleaned, &$phpProcess, &$goProcess, &$phpPipes, &$goPipes, $port, $phpPort) {
            if ($cleaned) return;
            $cleaned = true;

            echo PHP_EOL . self::COLOR_YELLOW . "Menghentikan server AbieSoft..." . self::COLOR_RESET . PHP_EOL;
            if (is_resource($phpProcess)) {
                if (isset($phpPipes[0])) @fclose($phpPipes[0]);
                if (isset($phpPipes[1])) @fclose($phpPipes[1]);
                if (isset($phpPipes[2])) @fclose($phpPipes[2]);
                @proc_terminate($phpProcess);
                @proc_close($phpProcess);
            }
            if (is_resource($goProcess)) {
                if (isset($goPipes[0])) @fclose($goPipes[0]);
                @proc_terminate($goProcess);
                @proc_close($goProcess);
            }

            // Pastikan port bersih
            $this->freePort($port);
            $this->freePort($phpPort);

            echo self::COLOR_GREEN . "Server berhasil dihentikan." . self::COLOR_RESET . PHP_EOL;
        };

        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () use ($cleanup) {
                $cleanup();
                exit(0);
            });
            pcntl_signal(SIGTERM, function () use ($cleanup) {
                $cleanup();
                exit(0);
            });
        }

        register_shutdown_function($cleanup);

        // Monitor kedua proses
        while (true) {
            $phpStatus = is_resource($phpProcess) ? proc_get_status($phpProcess) : ['running' => false];
            $goStatus = is_resource($goProcess) ? proc_get_status($goProcess) : ['running' => false];

            if (!$phpStatus['running']) {
                $err = (is_resource($phpProcess) && isset($phpPipes[2])) ? stream_get_contents($phpPipes[2]) : '';
                if (!empty($err)) {
                    echo self::COLOR_RED . "PHP Server berhenti dengan error: " . trim($err) . self::COLOR_RESET . PHP_EOL;
                }
                break;
            }

            if (!$goStatus['running']) {
                break;
            }

            usleep(250000); // 250ms
        }

        $cleanup();
    }
}