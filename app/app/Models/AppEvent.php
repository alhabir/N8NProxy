<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppEvent extends Model
{
    use HasFactory;

    protected $table = 'app_events';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'event_name',
        'salla_merchant_id',
        'merchant_id',
        'payload',
        'event_created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'event_created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (AppEvent $model) {
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
