<?php

declare(strict_types=1);

namespace Abiesoft\System\Database;

use Abiesoft\App\Shared\Helpers\Konten\Define;
use Abiesoft\App\Shared\Helpers\Konten\Info;
use Abiesoft\App\Shared\Helpers\Utilities\Uuid;
use PDO;
use PDOException;

class DB
{
    use Uuid, Info, Define;
    private static $terhubung = null;

    private
        $_pdo,
        $_query,
        $_error = false,
        $_hasil = [],
        $_hitung = 0;

    private string $_qb_tabel = "";
    private string $_qb_select = "*";
    private array $_qb_where = [];
    private array $_qb_join = [];
    private array $_qb_order = [];
    private ?int $_qb_limit = null;
    private int $_qb_offset = 0;
    private array $_qb_params = [];

    private static bool $_is_logging = false;

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

    /*
        ---------------------------------------------------------------
        Mencatat semua proses transaksi ke database
        ---------------------------------------------------------------
    */
    private function catatLog(string $pesan): void
    {

        if (self::$_is_logging) {
            return;
        }

        self::$_is_logging = true;

        try {
            $logPath = __DIR__ . '/../../var/logs/database/' . date('Y-m-d') . '.log';
        
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $isLoggedIn = $this->defineOpsi('sesi_nama') != '' ? 'Ya, Login' : 'Tanpa Login';
            $user = $this->defineOpsi('sesi_nama') != '' ? $this->defineOpsi('sesi_nama') : 'N/A';
            
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $perangkat = $this->deviceModel($userAgent);
            $ip = $this->getIp();
            
            $waktu = date('Y-m-d H:i:s');
            $formatLog = "[{$waktu}] [{$isLoggedIn}] [User: {$user}] [Device: {$perangkat}] [IP: {$ip}] | Pesan: {$pesan}" . PHP_EOL;
            
            file_put_contents($logPath, $formatLog, FILE_APPEND);

        } finally {
            self::$_is_logging = false;
        }
    }

    private function eksekusiQueryBuilder(): void
    {
        if ($this->_qb_tabel !== "") {
            $sql = "SELECT {$this->_qb_select} FROM {$this->_qb_tabel}";

            if (!empty($this->_qb_join)) {
                $sql .= " " . implode(" ", $this->_qb_join);
            }

            if (!empty($this->_qb_where)) {
                $sql .= " WHERE " . implode(" AND ", $this->_qb_where);
            }

            if (!empty($this->_qb_order)) {
                $sql .= " ORDER BY " . implode(", ", $this->_qb_order);
            }

            if ($this->_qb_limit !== null) {
                $sql .= " LIMIT ? OFFSET ?";
                $this->_qb_params[] = $this->_qb_limit;
                $this->_qb_params[] = $this->_qb_offset;
            }

            $this->query($sql, $this->_qb_params);

            $this->resetQueryBuilder();
        }
    }

    private function resetQueryBuilder(): void
    {
        $this->_qb_tabel = "";
        $this->_qb_select = "*";
        $this->_qb_where = [];
        $this->_qb_join = [];
        $this->_qb_order = [];
        $this->_qb_limit = null;
        $this->_qb_offset = 0;
        $this->_qb_params = [];
    }

    public function tabel(string $tabel): self
    {
        $this->resetQueryBuilder();
        $this->_qb_tabel = $tabel;
        return $this;
    }

    public function select(string $select = "*"): self
    {
        $this->_qb_select = $select;
        return $this;
    }

    public function where(string $kolom, string $simbol, $nilai): self
    {
        $this->_qb_where[] = "{$kolom} {$simbol} ?";
        $this->_qb_params[] = $nilai;
        return $this;
    }

    public function join(string $tabelTarget, string $onKolom1, string $simbol, string $onKolom2): self
    {
        $this->_qb_join[] = "JOIN {$tabelTarget} ON {$onKolom1} {$simbol} {$onKolom2}";
        return $this;
    }

    public function order(string $kolom, string $arah = 'ASC'): self
    {
        $arahAman = strtoupper($arah) === 'DESC' ? 'DESC' : 'ASC';
        $this->_qb_order[] = "{$kolom} {$arahAman}";
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->_qb_limit = $limit;
        $this->_qb_offset = $offset;
        return $this;
    }

    public function hasil(): array
    {
        $this->eksekusiQueryBuilder();
        return $this->_hasil;
    }

    public function json()
    {
        $this->eksekusiQueryBuilder();
        return json_encode($this->_hasil);
    }

    public function teks()
    {
        $this->eksekusiQueryBuilder();
        $result = '';
        if(isset($this->_hasil[0])){
            foreach($this->_hasil[0] as $k => $v){
                $result = $this->_hasil[0]->$k;
            }
        }
        return $result;
    }

    public function angka()
    {
        $this->eksekusiQueryBuilder();
        $result = '';
        if(isset($this->_hasil[0])){
            foreach($this->_hasil[0] as $k => $v){
                $result = $this->_hasil[0]->$k;
            }
        }
        return intval($result);
    }

    public function awal(): object
    {
        $this->eksekusiQueryBuilder();
        return $this->_hasil[0];
    }

    public function hitung(): int
    {
        $this->eksekusiQueryBuilder();
        return $this->_hitung;
    }

    public function error(): bool
    {
        return $this->_error;
    }

    public function query(string $sql, array $params = [])
    {

        $sqlUtama = strtoupper(ltrim($sql));

        if (
            str_starts_with($sqlUtama, 'INSERT') || 
            str_starts_with($sqlUtama, 'UPDATE') || 
            str_starts_with($sqlUtama, 'DELETE')
        ) {
            $this->catatLog("Eksekusi Query: SQL -> [{$sql}] | Params -> " . json_encode($params));
        }

        try {
            $this->_error = false;
            if ($this->_query = $this->_pdo->prepare($sql)) {
                $x = 1;
                if (count($params)) {
                    foreach ($params as $p) {
                        if (is_int($p)) {
                            $this->_query->bindValue($x, $p, PDO::PARAM_INT);
                        } else {
                            $this->_query->bindValue($x, $p, PDO::PARAM_STR);
                        }
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

    public function input(string $tabel, array $kolom, string|array $exist = "")
    {
        $this->catatLog("Fungsi input() dipanggil untuk tabel '{$tabel}'");

        if ($exist !== "") {
            $checkSql = "SELECT id FROM {$tabel} WHERE ";
            $checkParams = [];

            if (is_array($exist)) {
                $conditions = [];
                foreach ($exist as $field) {
                    if (array_key_exists($field, $kolom)) {
                        $conditions[] = "`{$field}` = ?";
                        $checkParams[] = $kolom[$field];
                    }
                }
                $checkSql .= implode(' AND ', $conditions);
            } else {
                if (array_key_exists($exist, $kolom)) {
                    $checkSql .= "`{$exist}` = ?";
                    $checkParams[] = $kolom[$exist];
                }
            }

            if (!empty($checkParams)) {
                $jumlahData = $this->query($checkSql, $checkParams)->hitung();
                if ($jumlahData > 0) {
                    return false; 
                }
            }
        }

        if (!isset($kolom['uuid'])) {
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

            if (!$this->query($sql, array_values($kolom))->error()) {
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

    public function legacy_join(array $tabel, array $on, string $select = "*")
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
        ---------------------------------------------------------------
        contoh penggunaan :
        ->perbarui('users', '1', [
            'nama' => 'User Baru',
            'email' => 'userbaru@email.com'
        ])
        ---------------------------------------------------------------
    */
    public function perbarui(string $tabel, int|string $id, array $kolom)
    {
        $kolom['diedit'] = date('Y-m-d H:i:s');
        
        $this->catatLog("Fungsi perbarui() dipanggil untuk tabel '{$tabel}' pada ID/UUID '{$id}'");

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
        ---------------------------------------------------------------
        contoh penggunaan (Hapus Total / Permanen):
        ->hapus('users', ['id', '=', '1'])
        ---------------------------------------------------------------
    */
    public function hapus(string $tabel, array $where)
    {
        $this->catatLog("Fungsi hapus() [PERMANEN] dipanggil untuk tabel '{$tabel}' dengan kondisi " . json_encode($where));
        return  $this->action('DELETE ', $tabel, $where);
    }

    /*
        ---------------------------------------------------------------
        contoh penggunaan
        ->hapusSementara('users', ['id', '=', '1'])
        ---------------------------------------------------------------
    */
    public function hapusSementara(string $tabel, array $where)
    {
        $this->catatLog("Fungsi hapusSementara() [SOFT DELETE] dipanggil untuk tabel '{$tabel}' dengan kondisi " . json_encode($where));
        
        if (count($where) === 3) {
            $daftarsimbol = array('=', '>', '<', '<=', '>=');
            $kolom  = $where[0];
            $simbol = $where[1];
            $nilai  = $where[2];
            
            if (in_array($simbol, $daftarsimbol)) {
                $waktuSekarang = date('Y-m-d H:i:s');
                $sql = "UPDATE {$tabel} SET `dihapus` = ? WHERE {$kolom} {$simbol} ?";
                
                if (!$this->query($sql, [$waktuSekarang, $nilai])->error()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function tampilkan(string $tabel, array $where)
    {
        return $this->action('SELECT *', $tabel, $where);
    }
}