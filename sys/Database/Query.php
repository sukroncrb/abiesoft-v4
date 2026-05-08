<?php

declare(strict_types=1);

namespace Abiesoft\System\Database;

use Abiesoft\App\Shared\Helpers\ApiResult;
use Abiesoft\App\Shared\Helpers\Uuid;
use Abiesoft\System\Utilities\Input;
use PDO;
use PDOException;

trait Query {

    /*
        Contoh penggunaan query
        Format Penulisan
        (new DB)->terhubung()->query("SELECT nama FROM users WHERE id=? ",[$iduser])->teks();
    */

    use Uuid;
    public function query(string $sql, array $params = [])
    {
        try {
            $this->_error = false;
            if ($this->_query = $this->_pdo->prepare($sql)) {
                $x = 1;
                if (count($params)) {
                    foreach ($params as $p) {
                        $this->_query->bindValue($x, $p);
                        $x++;
                    }
                }
                if ($this->_query->execute()) {
                    $this->_hasil        = $this->_query->fetchAll(PDO::FETCH_OBJ);
                    $this->_hitung       = $this->_query->rowCount();
                } else {
                    $this->_error = true;
                }
            }
            return $this;
        } catch (PDOException $error) {
            if ($_ENV['MODE'] == 'develope') {
                die($error);
            }
            exit();
        }
    }


    public function action(string $action, string $tabel, array $where = [])
    {
        if (count($where) === 3) {
            $daftarsimbol = array('=', '>', '<', '<=', '>=');
            $kolom  = $where[0];
            $simbol = $where[1];
            $nilai  = $where[2];
            if (in_array($simbol, $daftarsimbol)) {
                $sql = "{$action} FROM {$tabel} WHERE {$kolom} {$simbol} ?";
                if (!$this->query($sql, array($nilai))->error()) {
                    return $this;
                }
            }
        }
        return false;
    }


    /*
        Contoh input ke tabel users
        Format Penulisan
        (new DB)->terhubung()->input('users', array(['nama' => $nama, 'alamat' => $alamat ]));
    */

    public function input(string $tabel, array $kolom)
    {
        
        if(!isset($kolom['uuid'])){
            $kolom['uuid'] = $this->uidV4();
        }

        if (count($kolom)) {
            $keys = array_keys($kolom);
            $value = null;
            $x = 1;

            foreach ($kolom as $k) {
                $value .= '?';
                if ($x < count($kolom)) {
                    $value .= ', ';
                }
                $x++;
            }

            $sql = "INSERT INTO {$tabel} (`" . implode('`, `', $keys) . "`) VALUES ({$value})";


            if (!$this->query($sql, $kolom)->error()) {
                return true;
            }
        }
        return false;
    }

    public function all($tabel, $select = "*", $query = "", $opsi = [], $output = "array"){

        if(count($opsi) == 0){
            $opsi = "";
        }else{
            $opsi = ", ".$opsi;
        }

        $data = $this->query("SELECT $select FROM $tabel $query ". $opsi);

        return match($output){
            'json' => $data->json(),
            'hitung' => $data->hitung(),
            default => $data->hasil()
        };
        
    }

    public function only($tabel, $select = "*", $output = "array", $id = ""){

        $kolomKunci = is_numeric($id) ? 'id' : 'uuid';
        $where = " $kolomKunci = ? ";
        $data = $this->query("SELECT $select FROM $tabel WHERE $where ", [$id]);

        return match($output){
            'json' => $data->json(),
            'hitung' => $data->hitung(),
            'string' => $data->teks(),
            'angka' => $data->angka(),
            default => $data->hasil()
        };
        
    }

    /*
        Contoh penggunaan join sederhana
        (new DB)->terhubung()->join(['users','profil'], ['id','user_id'])->hasil();
    */
    public function join(array $tabel, array $on, string $select = "*")
    {
        if (count($tabel) === 2 && count($on) === 2) {
            $tabel1 = $tabel[0];
            $tabel2 = $tabel[1];
            $kolom1 = $on[0];
            $kolom2 = $on[1];

            $sql = "SELECT {$select} FROM {$tabel1} JOIN {$tabel2} ON {$tabel1}.{$kolom1} = {$tabel2}.{$kolom2}";
            
            if (!$this->query($sql)->error()) {
                return $this;
            }
        }
        return false;
    }

    /*
        Contoh memperbarui data ke tabel users
        Format Penulisan
        (new DB)->terhubung()->perbarui('users', $id, array(['nama' => $nama, 'alamat' => $alamat ]));
    */

    public function perbarui(string $tabel, int|string $id, array $kolom)
    {
        $set = '';
        $x = 1;
        $params = []; 

        foreach ($kolom as $nama => $value) {
            $set .= "{$nama} = ?";
            $params[] = $value;
            
            if ($x < count($kolom)) {
                $set .= ', ';
            }
            $x++;
        }
        
        $kolomKunci = is_numeric($id) ? 'id' : 'uuid';
        $sql = "UPDATE {$tabel} SET {$set} WHERE {$kolomKunci} = ?";
        $params[] = $id;
        if (!$this->query($sql, $params)->error()) {
            return true;
        }
        
        return false;
    }

    /*
        Contoh menghapus data dari tabel users
        Format Penulisan
        (new DB)->terhubung()->hapus('users', array('id_users', '=', 'id'));
    */
    public function hapus(string $tabel, array $where)
    {
        return  $this->action('DELETE ', $tabel, $where);
    }




    /*
        Contoh menampilkan data awal dari tabel users
        Format Penulisan
        (new DB)->terhubung()->tampilkan('users', array('id_users', '=', 'id'));
    */

    public function tampilkan(string $tabel, array $where)
    {
        return $this->action('SELECT *', $tabel, $where);
    }


}