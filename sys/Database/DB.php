<?php

declare(strict_types=1);

namespace Abiesoft\System\Database;

use PDO;
use PDOException;

class DB
{
    use Query;
    private static $terhubung = null;

    private
        $_pdo,
        $_query,
        $_error = false,
        $_hasil = [],
        $_hitung = 0;

    public function __construct()
    {
        
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $dbName = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';
        $mode = $_ENV['MODE'] ?? 'production';

        try {
            
            $dsn = "mysql:host=" . trim($host) . ";dbname=" . trim($dbName) . ";charset=utf8mb4";
            
            $this->_pdo = new PDO($dsn, trim($user), trim($pass), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

        } catch (\PDOException $error) {
            
            if (trim($mode) === 'develope' || trim($mode) === 'development') {
                die("<b>[Database Error]</b> Gagal terhubung ke database: " . $error->getMessage());
            }
            
            
            exit("Koneksi database bermasalah.");
        }
    }

    public static function terhubung()
    {
        if (!isset(self::$terhubung)) {
            return new DB();
        }
        return self::$terhubung;
    }

    public function hasil(): array
    {
        return $this->_hasil ?? [];
    }

    public function json()
    {
        return json_encode($this->hasil());
    }

    public function teks()
    {
        if ($this->_hitung === 0 || empty($this->_hasil)) {
            return '';
        }

        $result = '';
        foreach($this->_hasil[0] as $k => $v){
            $result = $this->_hasil[0]->$k;
        }
        return $result;
    }

    public function angka()
    {
        if ($this->_hitung === 0 || empty($this->_hasil)) {
            return 0;
        }

        $result = '';
        foreach($this->_hasil[0] as $k => $v){
            $result = $this->_hasil[0]->$k;
        }
        return intval($result);
    }

    public function error(): bool
    {
        return $this->_error;
    }

    public function awal(): ?object
    {
        if ($this->_hitung === 0 || empty($this->hasil())) {
            return null; 
        }

        return $this->hasil()[0];
    }

    public function hitung(): int
    {
        return $this->_hitung;
    }
}