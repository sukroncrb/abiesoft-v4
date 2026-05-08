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
        $cipher = "aes-128-cbc";
        $ciphertext_dec = base64_decode($secretcode, true);
        
        if ($ciphertext_dec === false) {
            return null;
        }

        $ivlen = openssl_cipher_iv_length($cipher);
    
        if (strlen($ciphertext_dec) <= $ivlen) {
            return null;
        }

        $iv = substr($ciphertext_dec, 0, $ivlen);
        $ciphertext = substr($ciphertext_dec, $ivlen);
        
        $original_plaintext = openssl_decrypt($ciphertext, $cipher, $secretkey, 0, $iv);
        
        if ($original_plaintext === false) {
            return null; 
        }

        $data = json_decode($original_plaintext, true);
    
        return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
    }
    
}