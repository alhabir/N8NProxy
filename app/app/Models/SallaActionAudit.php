<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SallaActionAudit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_meta' => 'array',
        'response_meta' => 'array',
    ];

    /**
     * Get the merchant associated with this audit.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}