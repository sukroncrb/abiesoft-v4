<?php

declare(strict_types=1);

namespace Abiesoft\System\Utilities;

use Abiesoft\App\Shared\Helpers\Keamanan\SecretCode;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Http\Cookie;

class Generate
{

    use SecretCode;
    protected function acak()
    {
        $karakter = 'AaBbCcDdEeFfGgHhIiJjKkLMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789';
        $batas = strlen($karakter);
        $result = '';
        for ($i = 0; $i < 12; $i++) {
            $result .= $karakter[rand(0, $batas - 1)];
        }
        return $result;
    }

    public function angka($jumlah = 4): int
    {
        $karakter = '0123456789';
        $batas = strlen($karakter);
        $result = 0;
        for ($i = 0; $i < $jumlah; $i++) {
            $result .= $karakter[rand(0, $batas - 1)];
        }
        return (int)$result;
    }

    public function csrf($formid): string
    {
        $db = (new DB)->terhubung();
        $cookie = new Cookie();
        $secretkey = $_ENV['SECRET_KEY'];
        $result = "";
        $inisial = $this->readSecretCode($cookie->get("_cf_v3"), $secretkey)['inisial'];
        
        if($inisial){
            $token = $this->acak();
            $idtoken = $db->query("SELECT id FROM token WHERE fid = ?", [$formid]);
            if($idtoken->hitung() > 0){
                $input = $db->perbarui('token', $db->query("SELECT id FROM token WHERE fid = ?", [$formid])->teks(), [
                    'inisial' => $inisial,
                    'fid' => $formid,
                    'token' => $token
                ]);
            }else{
                $input = $db->input('token', [
                    'inisial' => $inisial,
                    'fid' => $formid,
                    'token' => $token
                ]);
            }
            if($input){
                $result = $token;
            }
        }
        return $result;
    }

    public function formID($method): string
    {
        $cookie = new Cookie();
        $secretkey = $_ENV['SECRET_KEY'];
        $cf = $cookie->get("_cf_v3");
        $kode = $this->readSecretCode($cf, $secretkey)['inisial'];
        $result = "form-".sha1($method.$kode);
        return $result;
    }

    public function namaInisial($namaLengkap){
        $pecahNama = explode(" ", $namaLengkap);
        if(isset($pecahNama[1][0])){
            $inisial = ucfirst($pecahNama[0][0]) . ucfirst($pecahNama[1][0]);
        }else{
            $inisial = ucfirst($pecahNama[0][0]);
        }
        return $inisial;
    }
    
}