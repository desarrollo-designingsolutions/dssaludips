<?php

namespace App\Models;

use App\Enums\YesNoEnum;
use App\Traits\Cacheable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RipInvoiceUser extends Model
{
    use Cacheable, HasUuids, SoftDeletes;

    protected $guarded = [];

}
