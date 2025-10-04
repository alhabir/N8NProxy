<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Merchant extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'email',
        'password',
        'salla_merchant_id',
        'store_name',
        'n8n_base_url',
        'n8n_path',
        'n8n_auth_type',
        'n8n_bearer_token',
        'n8n_basic_user',
        'n8n_basic_pass',
        'is_active',
        'is_approved',
        'last_ping_ok_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_approved' => 'boolean',
        'last_ping_ok_at' => 'datetime',
        'n8n_bearer_token' => 'encrypted',
        'n8n_basic_user' => 'encrypted',
        'n8n_basic_pass' => 'encrypted',
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

    public function token()
    {
        return $this->hasOne(MerchantToken::class);
    }

    public function actionAudits()
    {
        return $this->hasMany(SallaActionAudit::class);
    }
}
