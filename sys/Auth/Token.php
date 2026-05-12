<?php

namespace Abiesoft\System\Auth;

use Abiesoft\App\Shared\Helpers\Define;
use Abiesoft\App\Shared\Helpers\Service;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Http\Cookie;
use Abiesoft\System\Utilities\Generate;
use Abiesoft\System\Utilities\Reader;

class Token extends Service
{ 

    private $db;
    use Define;

    public function __construct()
    {
        $this->db = (new DB)->terhubung();
    }

    public function getToken($fid)
    {
        $inisial = $this->defineOpsi('inisial');
        $db = (new DB)->terhubung();
        $token = $db->query("SELECT token FROM token WHERE fid = ? AND inisial = ? ", [$fid, $inisial])->teks();
        $this->success($token);
    }

    public function getBearer()
    {
        $cookie = new Cookie;
        $reader = new Reader;
        $secretkey = $_ENV['SECRET_KEY'];
        $generate = new Generate;
        $dataReader = $reader->secretCode($cookie->get('_cf_v3'),$secretkey);
        $datacf = [
            'apikey' => $dataReader->apikey,
            'inisial' => $dataReader->inisial,
            'remember' => $dataReader->remember,
            'timestamp' => time(),
            'seting' => [
                'notifikasi' => $dataReader->notifikasi,
                'suara' => $dataReader->suara
            ]
        ];
        $newcf = $generate->secretCode($datacf, $secretkey);
        $cookie->set("_cf_v3", $newcf);
        $this->success($newcf);
    }

}