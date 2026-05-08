<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers;

trait Info
{

    public function deviceModel($ua)
    {
        if (preg_match('/windows|win32/i', $ua)) {
            $os = "Windows PC";
        } else if (preg_match('/macintosh|mac os x/i', $ua)) {
            $os = "MacBook / iMac";
        } else if (preg_match('/CrOS/i', $ua)) {
            $os = "Chromebook";
        } else if (preg_match('/android/i', $ua)) {
            $os = "Android Device";
        } else if (preg_match('/iphone|ipad/i', $ua)) {
            $os = "Apple iOS";
        } else if (preg_match('/linux/i', $ua)) {
            $os = "Linux PC";
        } else {
            $os = "Unknown Device";
        }

        if (preg_match('/chrome|crios/i', $ua) && !preg_match('/opr|edge|edg/i', $ua)) {
            $browser = "Google Chrome";
        } else if (preg_match('/firefox/i', $ua)) {
            $browser = "Mozilla Firefox";
        } else if (preg_match('/safari/i', $ua) && !preg_match('/chrome/i', $ua)) {
            $browser = "Apple Safari";
        } else if (preg_match('/edge|edg/i', $ua)) {
            $browser = "Microsoft Edge";
        } else if (preg_match('/opr/i', $ua)) {
            $browser = "Opera";
        } else {
            $browser = "Unknown Browser";
        }

        return $os . " - " . $browser;
    }

    public function lokasiIp($ip = "") 
    {
        if ($ip == "") {
            $ipList = [
                $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
                $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? ''
            ];

            foreach ($ipList as $currentIp) {
                if (!$currentIp) continue;

                $ips = explode(',', $currentIp);
                $cleanIp = trim($ips[0]);

                if (filter_var($cleanIp, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $ip = $cleanIp;
                    break;
                }
            }
        }

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $ip = ""; 
        }

        try {

            $url = "http://ip-api.com/json/{$ip}?fields=status,regionName,city,query";
            $response = @file_get_contents($url); // Gunakan @ untuk handle silent error

            if ($response === false) {
                return $this->formatError($ip);
            }

            $data = json_decode($response, true);

            if ($data && $data['status'] === 'success') {

                return (object) [
                    'kota' => $data['city'],
                    'wilayah' => $data['regionName'],
                    'ip' => $data['query'],
                    'full' => "{$data['city']}, {$data['regionName']} • {$data['query']}"
                ];
            }
        } catch (\Exception $e) {
            return $this->formatError($ip);
        }

        return $this->formatError($ip);
    }

    private function formatError($ip)
    {
        return (object) [
            'kota' => 'Tidak diketahui',
            'wilayah' => 'Tidak diketahui',
            'ip' => $ip,
            'full' => "Lokasi tidak diketahui • {$ip}"
        ];
    }
}