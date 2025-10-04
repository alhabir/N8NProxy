<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MerchantToken extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'merchant_id',
        'salla_merchant_id',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'access_token_expires_at' => 'datetime',
    ];

    /**
     * Get the merchant that owns the token.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}