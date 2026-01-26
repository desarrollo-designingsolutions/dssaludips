<?php

namespace App\Enums\License;

use App\Attributes\Description;
use App\Attributes\Model;
use App\Traits\AttributableEnum;

enum LicenseStatusEnum: string
{
    use AttributableEnum;

    #[Description('draft')]
    case LICENSE_STATUS_001 = 'LICENSE_STATUS_001';

    #[Description('active')]
    case LICENSE_STATUS_002 = 'LICENSE_STATUS_002';

    #[Description('expired')]
    case LICENSE_STATUS_003 = 'LICENSE_STATUS_003';

    #[Description('active')]
    case LICENSE_STATUS_004 = 'LICENSE_STATUS_004';
}
