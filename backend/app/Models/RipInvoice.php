<?php

namespace App\Models;

use App\Enums\Rip\RipInvoiceStatusEnum;
use App\Enums\Rip\RipInvoiceStatusXmlEnum;
use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RipInvoice extends Model
{
    use Cacheable, HasUuids, SoftDeletes;

    protected $guarded = [];

    protected function casts()
    {
        return [
            'status_xml' => RipInvoiceStatusXmlEnum::class,
            'status' => RipInvoiceStatusEnum::class,
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function rip()
    {
        return $this->belongsTo(Rip::class);
    }
}
