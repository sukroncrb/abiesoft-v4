<?php

declare(strict_types=1);

namespace Abiesoft\System\Utilities;

class Input
{
    public function metode(string $tipe = 'post'): bool
    {
        return (strtolower($_SERVER['REQUEST_METHOD']) === strtolower($tipe));
    }

    public function get(string $item): string
    {
        $value = '';

        if (isset($_POST[$item])) {
            $value = $_POST[$item];
        } else if (isset($_GET[$item])) {
            $value = $_GET[$item];
        }

        if (is_array($value)) {
            return "";
        }

        $value = trim($value);

        $cleanValue = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        
        return htmlspecialchars($cleanValue, ENT_QUOTES, 'UTF-8');
    }

    public function getInt(string $item): int
    {
        $val = $this->get($item);
        return (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT);
    }

    public function file(string $item, string $tipe): string
    {
        return $_FILES[$item][$tipe] ?? '';
    }

    public function unset(string $item): void
    {
        if (isset($_POST[$item])) {
            unset($_POST[$item]);
        }
    }
}