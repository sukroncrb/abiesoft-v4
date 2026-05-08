<?php

declare(strict_types=1);

namespace Abiesoft\System\Utilities;

class Metafile
{

    public function approver(array $file_data, bool $pstorage = false): string
    {
        $filename = str_replace(" ", "", str_replace("-", "", str_replace("~", "", $file_data['name'])));
        $filetipe = pathinfo($filename, PATHINFO_EXTENSION);
        $filetmpname = $file_data['tmp_name'];
        $filesize = $file_data['size'];

        $fileCategory = $this->tipe($filetipe);

        if ($fileCategory === 'ditolak' || $fileCategory === null) {
            return "File tipe " . $filetipe . " tidak diijinkan";
        }

        $maxSize = $_ENV["FILE_SIZE_" . strtoupper($fileCategory)];
        if ($filesize > (int)$maxSize) {
            $formattedSize = number_format($maxSize / 1024 / 1024, 2);
            return "Ukuran file tidak boleh melebihi " . $formattedSize . " MB";
        }

        $filepath = "";
        $fdr = $file_data['nama_element'];

        if($pstorage){
            $fpstorage = $_ENV["PRIVATE_STORAGE"];
            $folder = __DIR__."/../../pstorage/".$fpstorage."/".$fdr."/";
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $kode = substr(sha1(date('Y-m-d H:i:s')), 0, 10);
            $namabaru = $kode . "_" . $filename;
            $destinationPath = $folder . "/" . $namabaru;

            $filepath = "file?fdr=".$fdr."&nm=".$namabaru;

        }else{
            
            $publicFolder = $_ENV["PUBLIC_FOLDER"];
            $folder = __DIR__ . "/../../" . $publicFolder . "/assets/storage/" . $fileCategory . "/" . date("d_m_y");

            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $kode = substr(sha1(date('Y-m-d H:i:s')), 0, 10);
            $namabaru = $kode . "_" . $filename;
            $destinationPath = $folder . "/" . $namabaru;

            $filepath = "assets/storage/" . $fileCategory . "/" . date("d_m_y") . "/" . $namabaru;
        }


        if (move_uploaded_file($filetmpname, $destinationPath)) {
            return $filepath;
        } else {
            return "Gagal mengunggah file.";
        }
        
    }

    protected function tipe(string $filetipe): ?string
    {
        $fileTipes = [
            'image'   => explode(",", $_ENV["FILE_TIPE_IMAGE"]),
            'media'   => explode(",", $_ENV["FILE_TIPE_MEDIA"]),
            'dokumen' => explode(",", $_ENV["FILE_TIPE_DOKUMEN"]),
        ];
        foreach ($fileTipes as $category => $types) {
            if (in_array($filetipe, $types)) {
                return $category;
            }
        }
        return "ditolak";
    }

    public function getTipeFile($namafile) {
        
        $ekstensi = pathinfo($namafile, PATHINFO_EXTENSION);
        
        $ekstensi = strtolower($ekstensi);
        
        $ekstensi_gambar = explode(",", $_ENV["FILE_TIPE_IMAGE"]);
        $ekstensi_dokumen = explode(",", $_ENV["FILE_TIPE_DOKUMEN"]);
        $ekstensi_media = explode(",", $_ENV["FILE_TIPE_MEDIA"]);
        
        if (in_array($ekstensi, $ekstensi_gambar)) {
            return "Gambar";
        } elseif (in_array($ekstensi, $ekstensi_dokumen)) {
            return "Dokumen";
        } elseif (in_array($ekstensi, $ekstensi_media)) {
            return "Media";
        } else {
            return "Tipe File Lain";
        }

    }
    
}