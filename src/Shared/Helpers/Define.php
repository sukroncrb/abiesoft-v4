<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers;

use Abiesoft\System\Database\DB;
use Abiesoft\System\Http\Cookie;
use Abiesoft\System\Session\SessionManager;
use Abiesoft\System\Utilities\Reader;

trait Define
{
    
    public function defineOpsi($label = "")
    {

        $cookie = new Cookie();
        $sesi = new SessionManager();
        $reader = new Reader;
        $secretkey = $_ENV['SECRET_KEY'];
        $db = (new DB)->terhubung();

        $token = "";
        if($cookie->has("_cf_v3")){
            $token = $cookie->get("_cf_v3");
        }

        $registrasi = false;
        $uuidRegistrasi = "c33f3f11-1232-4a83-9cae-d535e36524cd";
        $statusRegistrasi = $db->query("SELECT status FROM seting WHERE uuid = ? ", [$uuidRegistrasi])->angka();
        if($statusRegistrasi == 1){
            $registrasi = true;
        }

        $cf = $reader->secretCode($cookie->get("_cf_v3"),$secretkey);

        $static = [
            'mode'          => $_ENV['MODE'],
            'output'        => $_ENV['OUTPUT_MODE'],
            'baseurl'       => $_ENV['BASEURL'],
            'token'         => $token,
            'inisial'       => $cf['inisial'],
            'remember'      => $cf['remember'],
            'timestamp'     => $cf['timestamp'],
            'page'          => $_ENV['LOGIN_PAGE'],
            'registrasi'    => $registrasi,
        ];

        $datasesi = [];
        if($sesi->isLogin()){
            $datasesi = [
                'sesi_id' => $sesi->getId(),
                'sesi_uuid' => $sesi->getUuid(),
                'sesi_password' => $sesi->getPassword(),
                'sesi_nama' => $sesi->getNama(),
                'sesi_email' => $sesi->getEmail(),
                'sesi_photo' => $sesi->getPhoto(),
                'sesi_role' => $sesi->getRole(),
                'sesi_hp' => $sesi->getHp(),
                'sesi_alamat' => $sesi->getAlamat(),
                'sesi_unit' => $sesi->getUnit(),
            ];
        }

        $static = array_merge($static, $datasesi);

        if ($label !== "") {
            return $static[$label] ?? null;
        }
        
        return $static;
    }

}