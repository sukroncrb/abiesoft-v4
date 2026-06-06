<?php

namespace Abiesoft\System\Exception;

use Throwable;

class ErrorHandler
{
    public static function register(): void
    {
        // Tangkap Exception yang tidak ditangkap oleh try-catch
        set_exception_handler([self::class, 'handleException']);

        // Tangkap Error standar PHP (Warning, Notice, dll)
        set_error_handler([self::class, 'handleError']);

        // Tangkap Fatal Error saat aplikasi shutdown
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $exception): void
    {
        self::renderErrorPage($exception);
    }

    public static function handleError(int $level, string $message, string $file, int $line): void
    {
        // Ubah error standar PHP menjadi ErrorException agar bisa diproses sebagai Throwable
        if (error_reporting() & $level) {
            self::renderErrorPage(new \ErrorException($message, 0, $level, $file, $line));
        }
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && self::isFatal($error['type'])) {
            self::renderErrorPage(new \ErrorException(
                $error['message'], 0, $error['type'], $error['file'], $error['line']
            ));
        }
    }

    private static function isFatal(int $type): bool
    {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR]);
    }

    private static function renderErrorPage(Throwable $exception): void
    {
        // Bersihkan output buffer jika ada isi halaman yang terlanjur bocor
        if (ob_get_length()) {
            ob_clean();
        }

        // Cek mode aplikasi dari .env (jika production, sembunyikan detail error)
        $isDevelopment = ($_ENV['APP_MODE'] ?? 'develop') === 'develop';

        if (!$isDevelopment) {
            header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
            echo "<h1>500 Internal Server Error</h1><p>Sesuatu yang salah telah terjadi pada server kami.</p>";
            exit;
        }

        // Tampilan Error ala Laravel/Symfony (Gunakan template HTML bawaan)
        include __DIR__ . '/templates/error_view.php';
        exit;
    }
}