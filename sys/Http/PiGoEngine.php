<?php

declare(strict_types=1);

namespace Abiesoft\System\Http;

use Exception;

class PiGoEngine
{
    private $socketPath = __DIR__ . "/../pigo/pigo.sock";
    private $goBinaryPath = __DIR__ . "/../pigo/bin/pigo-engine";

    public function pastikanGoEngineRun() {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            
            $connection = @fsockopen("127.0.0.1", 8081, $errno, $errstr, 1);
            if (!$connection) {
                $this->startGoEngine();
            } else {
                fclose($connection);
            }
        } else {
            
            if (!file_exists($this->socketPath)) {
                $this->startGoEngine();
            } else {
                $connection = @stream_socket_client("unix://" . $this->socketPath, $errno, $errstr, 1);
                if (!$connection) {
                    @unlink($this->socketPath);
                    $this->startGoEngine();
                } else {
                    fclose($connection);
                }
            }
        }
    }

    private function startGoEngine() {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        $binary = $this->goBinaryPath;
        if ($isWindows) {
            $binary .= ".exe";
        }

        if (file_exists($binary)) {
            if ($isWindows) {

                $cmd = "start /B " . escapeshellarg($binary) . " > " . __DIR__ . "/../pigo/bin/output.log 2>&1";
                pclose(popen($cmd, "r"));
            } else {

                $cmd = $binary . " > " . __DIR__ . "/../pigo/bin/output.log 2>&1 &";
                shell_exec($cmd);
            }
            usleep(200000);
        } else {
            throw new Exception("Binari pigo tidak ditemukan di " . $binary);
        }
    }
}