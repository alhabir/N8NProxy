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
        'salla_event',
        'salla_event_id',
        'salla_merchant_id',
        'headers',
        'payload',
        'status',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
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

    public function forwardingAttempts()
    {
        return $this->hasMany(ForwardingAttempt::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
