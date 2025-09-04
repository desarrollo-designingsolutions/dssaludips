<?php

namespace App\Enums\Rip;

use App\Attributes\BackgroundColor;
use App\Attributes\Description;
use App\Traits\AttributableEnum;

enum RipStatusEnum: string
{
    use AttributableEnum;

    #[Description('En proceso')]
    #[BackgroundColor('warning')]
    case RIP_STATUS_001 = 'RIP_STATUS_001';
}
