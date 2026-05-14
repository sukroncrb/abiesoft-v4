<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Services;

use Abiesoft\App\Shared\Helpers\Service;
use Abiesoft\System\Database\DB;

class WellcomeRepository extends Service
{
    private $db;

    public function __construct()
    {
        $this->db = (new DB)->terhubung();
    }

    public function getAllWithGo($info)
    {
        $result = (object)$this->call("wellcome", [
            'info' => $info,
        ]);

        // $this->success($result);

        if($result->status == "success") {
            $this->success($result->data);
        }else{
            $this->badrequest("Gagal mengambil data");
        }
    }

    public function getAllWithPhp($info)
    {
        $this->success("[PHP Api Say] ".$info);
    }

    public function getAllSampleData()
    {
        $result = (object)$this->call("sample-all-data");
        $this->success($result->data);
    }

    public function getOnlySampleData($id)
    {
        $result = (object)$this->call("sample-only-data",[
            'id' => $id
        ]);
        $this->success($result->data);
    }

}