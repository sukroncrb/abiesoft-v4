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

    public function getOnly()
    {
        /*


            ---------------------------------------------------------------
            Menampilkan 1 data
            ---------------------------------------------------------------
        */
    }

    public function keep()
    {
        /*


            ---------------------------------------------------------------
            Menambah data
            ---------------------------------------------------------------
        */
    }

    public function replace()
    {
        /*


            ---------------------------------------------------------------
            Memperbarui data
            ---------------------------------------------------------------
        */
    }

    public function drop()
    {
        /*


            ---------------------------------------------------------------
            Menghapus data
            ---------------------------------------------------------------
        */
    }
}