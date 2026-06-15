<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Services;

use Abiesoft\App\Shared\Helpers\Service;
use Abiesoft\App\Shared\Helpers\Uuid;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Utilities\Input;

class WellcomeRepository extends Service
{
    private $db;
    use Uuid;

    public function __construct()
    {
        $this->db = (new DB)->terhubung();
    }

    public function getAllWithGo($info)
    {
        $result = (object)$this->call("wellcome", [
            'info' => $info,
        ]);

        // Pastikan memeriksa apakah property status ada di dalam object result
        if(isset($result->status) && $result->status == "success") {
            $this->success($result->data ?? "Sukses");
        } else {
            $this->badrequest($result->message ?? "Gagal mengambil data");
        }
    }

    public function getAllWithPhp($info)
    {
        $this->success("[PHP Api Say] ".$info);
    }

    public function getAllSampleData()
    {
        // Berikan parameter asosiatif buatan agar dipastikan menjadi Objek {} saat di-encode
        $result = $this->call("sample-all-data", ['init' => '1']);
        
        if (isset($result['status']) && $result['status'] === "error") {
            $this->badrequest($result['msg'] ?? "Gagal memproses data");
            return;
        }

        $this->success($result['data'] ?? []);
    }

    public function getOnlySampleData($id)
    {
        $result = (object)$this->call("sample-only-data",[
            'id' => $id
        ]);
        if (isset($result->data)) {
            $this->success($result->data);
        } else {
            $this->success($result);
        }
    }

    public function getSampleBigData($offset, $limit)
    {
        $result = (object)$this->call("sample-big-data",[
            'offset' => $offset,
            'limit' => $limit
        ]);
        if (isset($result->data)) {
            $this->success($result->data);
        } else {
            $this->success($result);
        }
    }

    public function postSampleDataWithGolang()
    {
        $input = new Input();
        $tech = "Golang";
        $nama = $input->get('nama');
        $id = $input->get('id');
        $method = $input->get('__method');
        $uuid = $this->uidV4();
        
        if($id != ""){
            if($method == "DELETE"){
                $result = (object)$this->call("delete-sample",[
                    'id' => $id,
                ]);
            }else{
                $result = (object)$this->call("update-sample",[
                    'nama' => $nama,
                    'tech' => $tech,
                    'id' => $id
                ]);
            }
        }else{
            $result = (object)$this->call("post-sample",[
                'uuid' => $uuid,
                'nama' => $nama,
                'tech' => $tech,
            ]);
        }

        // PERBAIKAN DI SINI:
        if (isset($result->status) && $result->status === "error") {
            $this->badrequest($result->message ?? "Gagal memproses data via Go");
            return;
        }

        if (isset($result->data)) {
            // Jika fungsi success() framework Anda hanya mendukung string, gunakan json_encode:
            // $this->success(json_encode($result->data));
            
            // Jika fungsi success() mendukung array/object, kirim langsung secara aman:
            $this->success($result->data);
        } else {
            $this->success("Proses Go Engine Berhasil");
        }
    }

    public function postSampleDataWithPhp()
    {
        $input = new Input();
        $db = (new DB)->terhubung();
        $nama = $input->get('nama');
        $tech = "PHP";
        $id = $input->get('id');
        $method = $input->get('__method');
        if($id != ""){
            if($method == "DELETE"){
                $hapus = $db->hapus("sample", ['id','=',$id]);
                if($hapus){
                    $this->success("Berhasil dihapus");
                }else{
                    $this->badrequest("Gagal menghapus data");
                }
            }else{
                $perbarui = $db->perbarui("sample", $id, [
                    'nama' => $nama,
                    'tech' => $tech
                ]);
                if($perbarui){
                    $this->success("Berhasil diperbarui");
                }else{
                    $this->badrequest("Gagal memperbarui data");
                }
            }
        }else{
            $insert = $db->input("sample", [
                'nama' => $nama,
                'tech' => $tech
            ]);
            if($insert){
                $this->success("Berhasil ditambahkan");
            }else{
                $this->badrequest("Gagal menambahkan data");
            }
        }
    }

}