<?php

namespace App\Enums\Rip;

use App\Attributes\Description;
use App\Traits\AttributableEnum;

enum RipTypeEnum: string
{
    use AttributableEnum;

    #[Description('Rips Zip')]
    case RIP_TYPE_001 = 'RIP_TYPE_001';

    #[Description('Rips Xml')]
    case RIP_TYPE_002 = 'RIP_TYPE_002';
}
