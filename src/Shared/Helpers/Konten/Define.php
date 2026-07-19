<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers\Konten;

use Abiesoft\App\Shared\Helpers\Keamanan\SecretCode;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Http\Cookie;
use Abiesoft\System\Session\SessionManager;

trait Define
{

    use SecretCode;
    public function defineOpsi(string $label = "")
    {
        $cookie = new Cookie();
        $sesi = new SessionManager();
        
        $secretkey = $_ENV['SECRET_KEY'] ?? '';
        $db = DB::terhubung();

        $token = "";
        if ($cookie->has("_cf_v3")) {
            $token = $cookie->get("_cf_v3") ?? "";
        }

        $cf = [
            'inisial' => '',
            'remember' => '',
            'timestamp' => ''
        ];

        if (!empty($token)) {
            $decrypted = $this->readSecretCode($token, $secretkey);
            if (is_array($decrypted)) {
                $cf['inisial'] = $decrypted['inisial'] ?? '';
                $cf['remember'] = $decrypted['remember'] ?? '';
                $cf['timestamp'] = $decrypted['timestamp'] ?? '';
            }
        }

        $static = [
            'mode'          => $_ENV['MODE'] ?? 'production',
            'output'        => $_ENV['OUTPUT_MODE'] ?? 'json',
            'baseurl'       => $_ENV['BASEURL'] ?? '/',
            'page'          => $_ENV['LOGIN_PAGE'] ?? 'login',
            'token'         => $token,
            'inisial'       => $cf['inisial'],
            'remember'      => $cf['remember'],
            'timestamp'     => $cf['timestamp']
        ];

        $datasesi = [];
        if ($sesi->isLogin()) {
            $datasesi = [
                'sesi_id'       => $sesi->getId() ?? '',
                'sesi_uuid'     => $sesi->getUuid() ?? '',
                'sesi_password' => $sesi->getPassword() ?? '',
                'sesi_nama'     => $sesi->getNama() ?? '',
                'sesi_email'    => $sesi->getEmail() ?? '',
                'sesi_photo'    => $sesi->getPhoto() ?? '',
                'sesi_role'     => $sesi->getRole() ?? '',
            ];
        }

        $static = array_merge($static, $datasesi);

        if ($label !== "") {
            return $static[$label] ?? null;
        }
        
        return $static;
    }
}