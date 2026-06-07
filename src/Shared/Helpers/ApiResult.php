<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers;

use Throwable;

trait ApiResult
{
    public function unauthorized(string $message = "UNAUTHORIZED"): void
    {
        $this->jsonResponse(401, $message);
    }

    public function forbidden(string $message = "FORBIDDEN"): void
    {
        $this->jsonResponse(403, $message);
    }

    public function badrequest(string|Throwable $message = "BAD REQUEST"): void
    {
        if ($message instanceof Throwable) {
            $msgString = $message->getMessage();
        } else {
            $msgString = $message;
        }

        $this->jsonResponse(400, $msgString);
    }

    public function success(mixed $result = []): void
    {
        $this->jsonResponse(200, $result);
    }

    private function jsonResponse(int $code, mixed $data = null): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        header('Content-Type: application/json');
        http_response_code($code);

        $response = [
            'code'    => $code,
            'status'  => $code === 200 ? 'success' : 'error'
        ];

        if ($data !== null || $code === 200) {
            $response['data'] = $data ?? [];
        }

        echo json_encode($response);
        exit;
    }
}