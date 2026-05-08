<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers;

use Abiesoft\System\Database\DB;
use Abiesoft\System\Utilities\Input;
use Abiesoft\System\Utilities\Metafile;

class Service
{

    use ApiResult, Define, PiGoCaller;

    protected function saveFileToAssetStorage($tabel) {
        $input = new Input();
        $db = (new DB)->terhubung();
        $publicfolder = $_ENV['PUBLIC_FOLDER'];
        $dir = __DIR__."/../../../".$publicfolder."/";
        $kolomfile = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES)) {
            foreach ($_FILES as $nama => $fileData) {
                if ($fileData['size'] == 0 || $fileData['error'] == UPLOAD_ERR_NO_FILE || $fileData['name'] == "") {
                    $id = $input->get('id');
                    $uuid = $input->get('uuid'); 
                    if($id != ''){
                        $file = $db->query("SELECT $nama FROM $tabel WHERE id = ? ", [$id])->teks();
                        if(!file_exists($dir.$file)){
                            $db->perbarui($tabel, $id, [
                                $nama => NULL
                            ]);
                            $file = "";
                        }
                        $kolomfile[$nama] = $file;
                    }else if($uuid != ''){
                        $file = $db->query("SELECT $nama FROM $tabel WHERE uuid = ? ", [$uuid])->teks();
                        if(!file_exists($dir.$file)){
                            $db->perbarui($tabel, $uuid, [
                                $nama => NULL
                            ]);
                            $file = "";
                        }
                        $kolomfile[$nama] = $file;
                    }else{
                        $kolomfile[$nama] = "";
                    }
                    continue; 
                }
                $fileData['nama_element'] = $nama;
                $metafileHandler = new Metafile();
                $result = $metafileHandler->approver($fileData);
                if (strpos($result, "assets/storage/") === 0) {
                    $kolomfile[$nama] = $result;
                } else {
                    $this->badrequest("Gagal mengunggah file untuk input '" . $nama . "'. Pesan: " . $result);
                }
            }        
        }else{
            $this->badrequest("Post Max Error, max post file ".ini_get('post_max_size'));
        }
        return $kolomfile;
    }

}