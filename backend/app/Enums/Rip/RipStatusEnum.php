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

    #[Description('Procesando')]
    #[BackgroundColor('info')]
    case RIP_STATUS_004 = 'RIP_STATUS_004';

    #[Description('Error XML')]
    #[BackgroundColor('error')]
    case RIP_STATUS_005 = 'RIP_STATUS_005';

    #[Description('Pendiente de Excel')]
    #[BackgroundColor('info')]
    case RIP_STATUS_006 = 'RIP_STATUS_006';

    #[Description('Pendiente de XML')]
    #[BackgroundColor('info')]
    case RIP_STATUS_007 = 'RIP_STATUS_007';
}
