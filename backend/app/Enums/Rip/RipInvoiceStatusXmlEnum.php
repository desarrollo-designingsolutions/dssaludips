<?php

namespace App\Enums\Rip;

use App\Attributes\BackgroundColor;
use App\Attributes\Description;
use App\Traits\AttributableEnum;

enum RipInvoiceStatusXmlEnum: string
{
    use AttributableEnum;

    #[Description('Validado')]
    #[BackgroundColor('success')]
    case RIP_INVOICE_STATUS_XML_001 = 'RIP_INVOICE_STATUS_XML_001';

    #[Description('Sin validar')]
    #[BackgroundColor('secondary')]
    case RIP_INVOICE_STATUS_XML_002 = 'RIP_INVOICE_STATUS_XML_002';

    #[Description('Error XML')]
    #[BackgroundColor('error')]
    case RIP_INVOICE_STATUS_XML_003 = 'RIP_INVOICE_STATUS_XML_003';
}
