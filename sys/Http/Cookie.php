<?php

declare(strict_types=1);

namespace Abiesoft\System\Http;

class Cookie
{
    /**
     * Membuat Cookie Baru
     * @param string 
     * @param string 
     * @param int 
     */
    public static function set(string $name, string $value, int $minutes = 60): void
    {
        $expiry = time() + ($minutes * 60);
        
        $options = [
            'expires' => $expiry,
            'path' => '/',
            'domain' => '', 
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', 
            'httponly' => true, 
            'samesite' => 'Lax' 
        ];

        setcookie($name, $value, $options);
    }

    public static function forever(string $name, string $value): void
    {
        self::set($name, $value, 2628000);
    }

    public static function get(string $name, string $default = ''): string
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]) && !empty($_COOKIE[$name]);
    }

    public static function delete(string $name): void
    {
        if (isset($_COOKIE[$name])) {
            $options = [
                'expires' => time() - 3600, 
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            setcookie($name, '', $options);
            unset($_COOKIE[$name]); 
        }
    }
}