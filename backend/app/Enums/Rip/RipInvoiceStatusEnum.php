<?php

namespace App\Enums\Rip;

use App\Attributes\BackgroundColor;
use App\Attributes\Description;
use App\Traits\AttributableEnum;

enum RipInvoiceStatusEnum: string
{
    use AttributableEnum;

    #[Description('Incompleto')]
    #[BackgroundColor('warning')]
    case RIP_INVOICE_STATUS_001 = 'RIP_INVOICE_STATUS_001';

    #[Description('Completado')]
    #[BackgroundColor('warning')]
    case RIP_INVOICE_STATUS_002 = 'RIP_INVOICE_STATUS_002';

    #[Description('Error Excel')]
    #[BackgroundColor('warning')]
    case RIP_INVOICE_STATUS_003 = 'RIP_INVOICE_STATUS_003';
}
