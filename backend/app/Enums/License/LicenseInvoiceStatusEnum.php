<?php

namespace App\Enums\License;

use App\Attributes\Description;
use App\Traits\AttributableEnum;

enum LicenseInvoiceStatusEnum: string
{
    use AttributableEnum;

    #[Description('pending')]
    case INVOICE_STATUS_001 = 'INVOICE_STATUS_001'; // Pendiente

    #[Description('paid')]
    case INVOICE_STATUS_002 = 'INVOICE_STATUS_002'; // Pagada

    #[Description('overdue')]
    case INVOICE_STATUS_003 = 'INVOICE_STATUS_003'; // Vencida

    #[Description('canceled')]
    case INVOICE_STATUS_004 = 'INVOICE_STATUS_004'; // Cancelada (opcional)
}
