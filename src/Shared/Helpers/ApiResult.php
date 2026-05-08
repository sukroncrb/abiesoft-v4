<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers;

trait ApiResult
{
    public function unauthorized($message = "UNAUTHORIZED")
    {
        $data = [];
        $data['code'] = 401;
        $data['message'] = $message;
        echo json_encode($data);
        exit;
    }

    public function forbidden($message = "FORBIDDEN")
    {
        $data = [];
        $data['code'] = 403;
        $data['message'] = $message;
        echo json_encode($data);
        exit;
    }

    public function badrequest($message = "BAD REQUEST")
    {
        $data = [];
        $data['code'] = 400;
        $data['message'] = $message;
        echo json_encode($data);
        exit;
    }

    public function success($result = [])
    {
        $data = [];
        $data['code'] = 200;
        $data['message'] = 'OK';
        $data['data'] = $result;
        echo json_encode($data);
        exit;
    }
}