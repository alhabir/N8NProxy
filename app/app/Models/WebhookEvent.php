<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'merchant_id',
        'salla_event_id',
        'event',
        'received_at',
        'signature_valid',
        'headers',
        'payload',
        'forward_status',
        'forward_attempts',
        'last_forward_error',
        'forwarded_response_code',
        'forwarded_response_body',
        'forwarded_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'received_at' => 'datetime',
        'signature_valid' => 'boolean',
        'forward_attempts' => 'integer',
        'forwarded_response_code' => 'integer',
        'forwarded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (WebhookEvent $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
