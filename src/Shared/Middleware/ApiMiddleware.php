<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Middleware;

use Abiesoft\App\Shared\Helpers\ApiResult;
use Abiesoft\App\Shared\Helpers\Define;
use Abiesoft\System\Database\DB;
use Abiesoft\System\Http\Cookie;
use Abiesoft\System\Http\GoEngine;
use Abiesoft\System\Http\MiddlewareInterface;
use Abiesoft\System\Utilities\Input;
use Abiesoft\System\Utilities\Reader;
use Exception;

class ApiMiddleware implements MiddlewareInterface
{
    
    use ApiResult, Define;
    public function handle(): bool
    {

        try {
            GoEngine::ensureEngineRunning();
        } catch (Exception $e) {
            return false;
        }

        if($this->defineOpsi("mode") != "develope"){

            $cookie = new Cookie();
            $reader = new Reader();
            $input = new Input();
            $database = (new DB)->terhubung();
            $secretkey = $_ENV['SECRET_KEY'];
            $method = $_SERVER['REQUEST_METHOD'];

            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            if (empty($authHeader)) {
                $this->unauthorized();
            }

            $secretcode = str_replace("Bearer ", "", $authHeader);

            $decodedData = $reader->secretCode($secretcode, $secretkey);

            if (isset($decodedData['timestamp']) && (time() - $decodedData['timestamp'] > 600000)) {
                $this->unauthorized("Sesi enkripsi berakhir");
            }

            if (!is_array($decodedData) || !isset($decodedData['apikey'])) {
                $this->forbidden();
            }

            $apikey = $decodedData['apikey'];
            $inisial = $decodedData['inisial'] ?? ''; 

            $inisialCookie = "";
            if ($cookie->has("_cf_v3")) {
                $cookieData = $reader->secretCode($cookie->get("_cf_v3"), $secretkey);
                if (is_array($cookieData)) {
                    $inisialCookie = $cookieData['inisial'] ?? '';
                }
            }

            if ($apikey !== $_ENV['APIKEY'] || $inisialCookie !== $inisial) {
                $this->forbidden();
            }

            if($method == "POST"){
                $fid = $input->get("fid");
                $csrf = $input->get("__csrf");
                $kondisi = [
                    'inisial' => $inisial,
                    'fid' => $fid,
                    'token' => $csrf,
                ];
                $validasi = $database->query("SELECT id FROM token WHERE inisial = ? AND fid = ? AND token = ? ", [$inisial, $fid, $csrf])->hitung();
                if($validasi != 1){
                    $this->forbidden("Token Expire");
                }
                $input->unset("fid");
                $input->unset("__csrf");
            }

            return true;
            
        }else{
            return true;
        }
    }
    
}