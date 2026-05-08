<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers;

trait PiGoCaller
{

    public function call($action, $params = []) {
        $socketPath = "./../sys/pigo/pigo.sock";
        
        $payload = json_encode([
            "action" => $action,
            "params" => $params,
            "timestamp" => time()
        ]);

        $fp = stream_socket_client("unix://$socketPath", $errno, $errstr, 5);
        if (!$fp) return ["error" => "Engine mati"];

        fwrite($fp, $payload);
        $response = fread($fp, 4096);
        fclose($fp);

        return json_decode($response, true);
    }

}