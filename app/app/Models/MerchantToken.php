<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantToken extends Model
{
    use HasUuids;

    protected $fillable = [
        'merchant_id',
        'salla_merchant_id',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'access_token_expires_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Check if the access token is expired or expiring soon
     */
    public function isTokenExpiring(int $bufferSeconds = 60): bool
    {
        if (!$this->access_token_expires_at) {
            return false;
        }

        return $this->access_token_expires_at->lte(now()->addSeconds($bufferSeconds));
    }
}