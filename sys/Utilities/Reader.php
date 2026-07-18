<?php

declare(strict_types=1);

namespace Abiesoft\System\Utilities;

class Reader
{

    public function ip() {
        $ip = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'Ip Tidak Dikenali';
        }
        return $ip;
    }
    
    public function secretCode($secretcode, $secretkey) {
        if (empty($secretcode)) {
            return null;
        }
        
        $cipher = "aes-256-gcm";
        $decoded = base64_decode($secretcode, true);
        
        if ($decoded === false) {
            return null;
        }

        $ivlen = openssl_cipher_iv_length($cipher);
        $taglen = 16;
        
        if (strlen($decoded) <= ($ivlen + $taglen)) {
            return null;
        }

        $iv = substr($decoded, 0, $ivlen);
        $tag = substr($decoded, $ivlen, $taglen);
        $ciphertext = substr($decoded, $ivlen + $taglen);
        
        $original_plaintext = openssl_decrypt($ciphertext, $cipher, $secretkey, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($original_plaintext === false) {
            return null;
        }

        $data = json_decode($original_plaintext, true);
        
        return (json_last_error() === JSON_ERROR_NONE) ? $data : $original_plaintext;
    }
    
}