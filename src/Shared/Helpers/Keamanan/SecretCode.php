<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers\Keamanan;

trait SecretCode
{

    protected function makeSecretCode($data, $secretkey) {
        $cipher = "aes-256-gcm";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $tag = "";
        $ciphertext = openssl_encrypt($data, $cipher, $secretkey, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $ciphertext);
    }

    protected function readSecretCode($secretcode, $secretkey) {
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