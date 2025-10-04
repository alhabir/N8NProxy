<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForwardingAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'webhook_event_id',
        'target_url',
        'response_status',
        'response_body',
        'duration_ms',
    ];

    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class);
    }
}