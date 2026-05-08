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
        $_hasil,
        $_hitung = 0;

    public function __construct()
    {
        try {
            $this->_pdo = new PDO(
                "mysql:host=" . $_ENV['DB_HOST'] . ";
                dbname=" . $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASS']
            );
        } catch (PDOException $error) {

            if ($_ENV['MODE'] == 'develope') {
                die($error);
            }

            exit();
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
        return $this->_hasil;
    }

    public function json()
    {
        return json_encode($this->_hasil);
    }

    public function teks()
    {
        $result = '';
        foreach($this->_hasil[0] as $k => $v){
            $result = $this->_hasil[0]->$k;
        }
        return $result;
    }

    public function angka()
    {
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

    public function awal(): object
    {
        return $this->hasil()[0];
    }

    public function hitung(): int
    {
        return $this->_hitung;
    }
}