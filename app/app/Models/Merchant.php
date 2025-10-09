<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Merchant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'claimed_by_user_id',
        'store_id',
        'salla_merchant_id',
        'store_name',
        'store_domain',
        'email',
        'salla_access_token',
        'salla_refresh_token',
        'salla_token_expires_at',
        'n8n_base_url',
        'n8n_webhook_path',
        'n8n_auth_type',
        'n8n_auth_token',
        'is_active',
        'is_approved',
        'last_ping_ok_at',
        'connected_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_approved' => 'boolean',
        'last_ping_ok_at' => 'datetime',
        'n8n_auth_token' => 'encrypted',
        'salla_access_token' => 'encrypted',
        'salla_refresh_token' => 'encrypted',
        'salla_token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (Merchant $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function webhookEvents()
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function claimedBy()
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function user()
    {
        return $this->claimedBy();
    }

    public function token()
    {
        return $this->hasOne(MerchantToken::class);
    }

    public function actionAudits()
    {
        return $this->hasMany(SallaActionAudit::class);
    }
}
