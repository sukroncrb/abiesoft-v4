<?php

declare(strict_types=1);

namespace Abiesoft\System\Http;

use Exception;

class PiGoEngine
{

    private $socketPath = __DIR__. "/../pigo/pigo.sock";
    private $goBinaryPath = __DIR__. "/../pigo/bin/pigo-engine";

    public function pastikanGoEngineRun() {

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

    private function startGoEngine() {
        if (file_exists($this->goBinaryPath)) {
            $cmd = $this->goBinaryPath . " > " . __DIR__ . "/../pigo/bin/output.log 2>&1 &";
            shell_exec($cmd);
            usleep(100000); 
        } else {
            throw new Exception("Binari pigo tidak ditemukan di " . $this->goBinaryPath);
        }
    }

}