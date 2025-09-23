<?php

namespace App\Enums\Rip;

use App\Attributes\BackgroundColor;
use App\Attributes\Description;
use App\Traits\AttributableEnum;

enum RipStatusEnum: string
{
    use AttributableEnum;

    #[Description('Datos incompletos')]
    #[BackgroundColor('warning')]
    case RIP_STATUS_001 = 'RIP_STATUS_001';


    #[Description('Sin validar')]
    #[BackgroundColor('info')]
    case RIP_STATUS_002 = 'RIP_STATUS_002';

    #[Description('Error Excel')]
    #[BackgroundColor('error')]
    case RIP_STATUS_003 = 'RIP_STATUS_003';
}
