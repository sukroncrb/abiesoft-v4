<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers;

trait PiGoCaller
{
    public function call($action, $params = []) {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            $address = "tcp://127.0.0.1:8081";
        } else {
            $socketPath = __DIR__ . "/../../../sys/pigo/pigo.sock";
            $address = "unix://$socketPath";
        }
        
        $safeParams = empty($params) ? (object)[] : $params;

        $jsonString = json_encode([
            "action" => $action,
            "params" => $safeParams, 
            "timestamp" => time()
        ]);

        $payload = trim($jsonString) . "\n"; 

        $fp = @stream_socket_client($address, $errno, $errstr, 2);
        if (!$fp) {
            return ["status" => "error", "msg" => "Engine Go mati pada $address: $errstr"];
        }

        fwrite($fp, $payload);

        $response = "";
        while (!feof($fp)) {
            $chunk = fread($fp, 8192);
            if ($chunk === false) {
                break;
            }
            $response .= $chunk;
        }
        fclose($fp);

        $result = json_decode(trim($response), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ["status" => "error", "msg" => "Gagal parse JSON dari Go Engine", "raw" => $response];
        }

        return $result;
    }
}