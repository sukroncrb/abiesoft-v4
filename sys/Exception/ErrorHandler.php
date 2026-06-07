<?php

namespace Abiesoft\System\Exception;

use Abiesoft\App\Shared\Helpers\ApiResult;
use Abiesoft\App\Shared\Helpers\Define;
use Throwable;

class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $exception): void
    {
        self::renderErrorPage($exception);
    }

    public static function handleError(int $level, string $message, string $file, int $line): void
    {
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

        if (ob_get_length()) {
            ob_clean();
        }

        $isDevelopment = ($_ENV['MODE'] ?? 'develope') === 'develope';
        $isJsonRequest = self::shouldReturnJson();

        if ($isJsonRequest) {
            header('Content-Type: application/json');
            header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");

            if (!$isDevelopment) {
                echo json_encode([
                    'status' => 'error',
                    'msg'    => 'Internal Server Error',
                    'data'   => null
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'msg'    => $exception->getMessage(),
                    'data'   => [
                        'exception' => get_class($exception),
                        'file'      => $exception->getFile(),
                        'line'      => $exception->getLine(),
                        'trace'     => array_slice($exception->getTrace(), 0, 5)
                    ]
                ]);
            }
            exit;
        }

        if (!$isDevelopment) {
            header('location: /');
            // header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
            // echo "<h1>500 Internal Server Error</h1><p>Sesuatu yang salah telah terjadi pada server kami.</p>";
            exit;
        }

        include __DIR__ . '/templates/error_view.php';
        exit;
    }

    private static function shouldReturnJson(): bool
    {
        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json')) {
            return true;
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        if (isset($_SERVER['REQUEST_URI']) && str_contains(strtolower($_SERVER['REQUEST_URI']), '/api/')) {
            return true;
        }

        return false;
    }
}