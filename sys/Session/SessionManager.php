<?php

declare(strict_types=1);

namespace Abiesoft\System\Session;

use Abiesoft\App\Shared\Helpers\Keamanan\SecretCode;
use Abiesoft\App\Shared\Helpers\Konten\Define;
use Abiesoft\App\Shared\Helpers\Konten\Info;
use Abiesoft\App\Shared\Helpers\Utilities\ApiResult;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Http\Cookie;
use Abiesoft\System\Utilities\Generate;
use Abiesoft\System\Utilities\Input;

class SessionManager
{
    
    use ApiResult, Define, Info, SecretCode;
    protected function cariuser(string $email)
    {
        $database = (new DB)->terhubung();
        $cookie = new Cookie();
        if ($email) {
            $data =  $database->tampilkan('users', ['email', '=', $email]);
            if ($data->hitung()) {
                return $data->awal();
            }
            $cookie->delete('abiesoft-SUID-v3');
        }
        return false;
    }

    public function setLogin(){
        $input = new Input();
        $generate = new Generate();
        $cookie = new Cookie();
        $database = (new DB)->terhubung();
        $email = $input->get('email');
        $password = $input->get('password');
        $secretkey = $_ENV['SECRET_KEY'];
        $user = $this->cariuser($email);
        $remember = $input->get('remember');

        if($user){
            if(password_verify($password, $user->password_hash)){


                /*


                    ---------------------------------------------------------------
                    Jika Status Maintenance 1
                    dan Usernya bukan IT maka akan di lock tidak bisa login
                    ---------------------------------------------------------------
                */
                $statusMaintenance = $database->query("SELECT status FROM seting WHERE id = ? ", [2])->teks();
                if($statusMaintenance == "1" && $user->role != "admin"){
                    $this->badrequest("Aplikasi dalam pemeliharaan, silahkan coba lagi nanti.");
                }

                $decodedData = $this->readSecretCode($cookie->get("_cf_v3"),$secretkey);
                $apikey = $decodedData['apikey'];
                $inisial = $decodedData['inisial'];
                $notifikasi = $decodedData['seting']['notifikasi'];
                $suara = $decodedData['seting']['suara'];

                if($remember == "on"){
                    $remember = $email;
                }

                $datacf = [
                    'apikey' => $apikey,
                    'inisial' => $inisial,
                    'remember' => $remember,
                    'timestamp' => time(),
                    'seting' => [
                        'notifikasi' => $notifikasi,
                        'suara' => $suara
                    ]
                ];
                $newcf = $this->makeSecretCode($datacf, $secretkey);
                $cookie->set("_cf_v3", $newcf);

                $logID = $database->query("SELECT id FROM log WHERE inisial = ? AND email = ? ",[$inisial, $email])->teks();
                if($logID != ""){
                    $setLog = $database->perbarui('log', $logID, [
                        'email' => $email,
                        'device' => $_SERVER['HTTP_USER_AGENT'],
                        'aktifitas' => 'Login',
                        'lokasi' => $this->lokasiIp()->kota. ", ". $this->lokasiIp()->wilayah,
                        'ip' => $this->lokasiIp()->ip,
                        'inisial' => $inisial,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }else{
                    $setLog = $database->input('log', [
                        'email' => $email,
                        'device' => $_SERVER['HTTP_USER_AGENT'],
                        'aktifitas' => 'Login',
                        'lokasi' => $this->lokasiIp()->kota. ", ". $this->lokasiIp()->wilayah,
                        'ip' => $this->lokasiIp()->ip,
                        'inisial' => $inisial
                    ]);
                }

                if($setLog){
                    $data = [
                        'email' => $email,
                        'inisial' => $inisial,
                    ];
                    $secretcode = $this->makeSecretCode($data,$secretkey);
                    $cookie->set("abiesoft-SUID-v3", $secretcode);
                    $this->success();
                }else{
                    $this->badrequest();
                }

            }else{
                $this->badrequest("Login Gagal");
            }
        }else{
            $this->badrequest("Login Gagal");
        }
    }

    public function isLogin(){
        $input = new Input();
        $cookie = new Cookie();
        $database = (new DB)->terhubung();
        $email = $input->get('email');
        $secretkey = $_ENV['SECRET_KEY'];

        $result = false;
        if($cookie->has('abiesoft-SUID-v3')) {
            $secretcode = $cookie->get('abiesoft-SUID-v3');
            $inisial = $this->readSecretCode($secretcode, $secretkey)['inisial'];
            $email = $this->readSecretCode($secretcode, $secretkey)['email'];

            if($database->query("SELECT id FROM log WHERE email = ? AND inisial = ? AND aktifitas = ? ", [$email, $inisial, "Login"])->hitung() > 0){
                if($this->cariuser($email)){
                    $result = true;
                }
            }
        }

        return $result;
    }

    public function logout()
    {
        $database = (new DB)->terhubung();
        $cookie = new Cookie();
        $email = $this->defineOpsi('sesi_email');
        $inisial = $this->defineOpsi('inisial');
        $idlog = $database->query("SELECT id FROM log WHERE email = ? AND inisial = ? ",[$email, $inisial])->teks();
        $database->perbarui('log', $idlog, [
            'email' => $email,
            'aktifitas' => 'Logout',
            'inisial' => $inisial,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $cookie->delete('abiesoft-SUID-v3');
        if(!$cookie->has('abiesoft-SUID-v3')){
            $this->success("Logout berhasil","success");
        }else{
            $this->badrequest("Logout Gagal","error");
        }
    }

    protected function userData(){
        $cookie = new Cookie();
        $secretkey = $_ENV['SECRET_KEY'];
        if($cookie->has('abiesoft-SUID-v3')) {
            $secretcode = $cookie->get('abiesoft-SUID-v3');
            $email = $this->readSecretCode($secretcode, $secretkey)['email'];
            return $this->cariuser($email);
        }
    }

    public function getId() {
        return $this->userData()->id;
    }

    public function getUuid() {
        return $this->userData()->uuid;
    }

    public function getPassword() {
        return $this->userData()->password_hash;
    }

    public function getPhoto() {
        $user = $this->userData();
        if (!$user) return "";

        $publicfolder = $_ENV['PUBLIC_FOLDER'];
        $baseurl = $_ENV['BASEURL'];

        $photo = "https://placehold.co/40x40/F0ECE5/161A30?text=".(new Generate)->namaInisial($user->nama);

        if($user->photo != ""){
            $file = __DIR__."/../../".$publicfolder."/".$user->photo;
            if(file_exists($file)){
                $photo = $baseurl . $user->photo;
            }
        }
        return $photo;
    }

    public function getEmail() {
        return $this->userData()->email;
    }

    public function getNama() {
        return $this->userData()->nama;
    }

    public function getRole() {
        return $this->userData()->role;
    }
    
}