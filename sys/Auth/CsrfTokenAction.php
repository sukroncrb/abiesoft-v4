<?php

declare(strict_types=1);

namespace Abiesoft\System\Auth;

use Abiesoft\App\Shared\Helpers\ApiResult;
use Abiesoft\System\Utilities\Generate;

readonly class CsrfTokenAction
{
    use ApiResult;
    public function __invoke($token): void
    {
        $generate = new Generate();
        $form = $token;
        $fid = $generate->formID($form);
        $data = [
            'form' => $form,
            'fid' => $fid,
            'csrf' => $generate->csrf($fid),
        ];
        $this->success($data);
    }
}