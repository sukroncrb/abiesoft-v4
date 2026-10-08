<?php

declare(strict_types=1);

namespace Abiesoft\App\Modules\Home\Services;

use Abiesoft\App\Shared\Helpers\Service;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Utilities\Input;

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
        $data = $this->db->tabel("sample")->order("id", "DESC")->hasil();
        $this->success($data ?? []);
    }

    public function getOnlySampleData($id)
    {
        $data = $this->db->tabel("sample")->where("id", "=", $id)->hasil();
        $this->success($data ?? []);
    }

    public function getSampleBigData($offset, $limit)
    {
        $data = $this->db->tabel("sample")->limit((int)$limit, (int)$offset)->hasil();
        $this->success($data ?? []);
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

        if (isset($result->status) && $result->status === "error") {
            $this->badrequest($result->message ?? "Gagal memproses data via Go");
            return;
        }

        if (isset($result->data)) {
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
        $rawTech = $input->get('tech');
        $tech = ($rawTech === 'on' || strtolower($rawTech) === 'golang') ? 'Golang' : 'PHP';
        $id = $input->get('id');
        $method = $input->get('__method');

        if (trim($nama) === "" && $method !== "DELETE") {
            $this->badrequest("Field nama tidak boleh kosong");
            return;
        }

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