<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers\Konten;

use Abiesoft\System\Database\DB;
use Abiesoft\System\Utilities\Input;
use Abiesoft\System\Utilities\Metafile;

trait Upload
{

    protected function saveFileToAssetStorage($tabel) {
        $db = (new DB)->terhubung();

        $input = new Input();
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

    protected function saveImageFromTextToStorage() {
        $publicfolder = $_ENV['PUBLIC_FOLDER'];
        $dir = __DIR__."/../../../".$publicfolder."/";
        $kolomfile = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = $_POST;

            foreach ($postData as $nama => $textData) {
                
                if (is_string($textData) && strpos($textData, 'data:image/') === 0) {
                    if (substr($nama, -7) !== '_base64') {
                        $this->badrequest(
                            "Gagal memproses berkas teks. Nama elemen input '" . $nama . "' tidak valid. " .
                            "Keterangan: Nama elemen input text untuk gambar Base64 harus berakhiran '_base64' " .
                            "(Contoh penamaan yang benar: foto_base64 atau photo_base64)."
                        );
                    }

                    $parts = explode(',', $textData);
                    if (count($parts) < 2) {
                        continue;
                    }
                    
                    $metaHeader = $parts[0]; 
                    $fileDataMurni = base64_decode($parts[1]);

                    $ekstensi = '.jpg';
                    if (strpos($metaHeader, 'image/png') !== false) {
                        $ekstensi = '.png';
                    } elseif (strpos($metaHeader, 'image/webp') !== false) {
                        $ekstensi = '.webp';
                    }

                    $subDir = "assets/storage/image/".date('Y-m-d')."/";
                    
                    $namaFileBaru = "abiesoft_" . uniqid() . "_" . time() . $ekstensi;
                    $fullPathSimpan = $dir . $subDir . $namaFileBaru;

                    if (!is_dir($dir . $subDir)) {
                        mkdir($dir . $subDir, 0755, true);
                    }

                    if (file_put_contents($fullPathSimpan, $fileDataMurni) !== false) {
                        $namaKolomDb = str_replace('_base64', '', $nama);
                        $kolomfile[$namaKolomDb] = $subDir . $namaFileBaru;
                    } else {
                        $this->badrequest("Gagal menulis file gambar dari teks input '" . $nama . "' ke folder asset storage.");
                    }
                }else{
                    $namaKolomDb = str_replace('_base64', '', $nama);
                    $namaFileBaru = $textData;
                    $kolomfile[$namaKolomDb] = $namaFileBaru;
                }
            }        
        } else {
            $this->badrequest("Metode request tidak sah untuk proses konversi berkas teks.");
        }

        return $kolomfile;
    }

}