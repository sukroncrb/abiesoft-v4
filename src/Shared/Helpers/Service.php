<?php

declare(strict_types=1);

namespace Abiesoft\App\Shared\Helpers;

use Abiesoft\App\Shared\Helpers\Keamanan\SecretCode;
use Abiesoft\App\Shared\Helpers\Konten\Define;
use Abiesoft\App\Shared\Helpers\Konten\Info;
use Abiesoft\App\Shared\Helpers\Konten\Upload;
use Abiesoft\App\Shared\Helpers\Utilities\ApiResult;
use Abiesoft\App\Shared\Helpers\Utilities\Cleaner;
use Abiesoft\App\Shared\Helpers\Utilities\Tanggal;
use Abiesoft\App\Shared\Helpers\Utilities\Uuid;

class Service
{
    use 
        SecretCode,
        ApiResult, 
        Cleaner, 
        PiGoCaller, 
        Tanggal,  
        Upload, 
        Info,
        Define,
        Uuid;
}