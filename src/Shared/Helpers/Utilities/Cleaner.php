<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers\Utilities;

trait Cleaner
{

    protected function bersihkanDataURL($data, $tipe = 'string') {
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->bersihkanDataURL($value, $tipe);
            }
            return $data;
        }

        $data = trim($data);
        $data = stripslashes($data);

        switch (strtolower($tipe)) {
            case 'integer':
            case 'int':
                $data = filter_var($data, FILTER_SANITIZE_NUMBER_INT);
                break;

            case 'slug':
                $data = preg_replace('/[^a-zA-Z0-9\-]/', '', $data);
                break;

            case 'url':
                $data = filter_var($data, FILTER_SANITIZE_URL);
                break;

            case 'string':
            default:
                $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
                break;
        }

        return $data;
    }

    protected function textOnly($string) {
        $kupas1 = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        $kupas2 = html_entity_decode($kupas1, ENT_QUOTES, 'UTF-8');
        $teksBersih = strip_tags($kupas2);
        $teksBersih = preg_replace('/\s+/', ' ', $teksBersih);
        
        return trim($teksBersih);
    }

}