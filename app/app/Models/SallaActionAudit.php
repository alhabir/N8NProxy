<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SallaActionAudit extends Model
{
    protected $fillable = [
        'merchant_id',
        'salla_merchant_id',
        'resource',
        'action',
        'method',
        'endpoint',
        'request_meta',
        'status_code',
        'response_meta',
        'duration_ms',
    ];

    protected $casts = [
        'request_meta' => 'array',
        'response_meta' => 'array',
    ];
}
