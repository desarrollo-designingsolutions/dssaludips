<?php

namespace App\Enums\Rip;

use App\Attributes\BackgroundColor;
use App\Attributes\Description;
use App\Traits\AttributableEnum;

enum RipInvoiceStatusEnum: string
{
    use AttributableEnum;

    #[Description('Validado')]
    #[BackgroundColor('success')]
    case FILINGINVOICE_EST_001 = 'FILINGINVOICE_EST_001';

    #[Description('Sin validar')]
    #[BackgroundColor('')]
    case FILINGINVOICE_EST_002 = 'FILINGINVOICE_EST_002';
}
