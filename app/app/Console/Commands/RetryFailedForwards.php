<?php

namespace App\Console\Commands;

use App\Models\Merchant;
use App\Models\WebhookEvent;
use App\Services\Forwarder;
use Illuminate\Console\Command;

class RetryFailedForwards extends Command
{
    protected $signature = 'webhooks:retry-failed';
    protected $description = 'Retry forwarding failed webhook events once per run';

    public function handle(): int
    {
        $maxAttempts = (int) env('FORWARD_RETRY_SCHEDULE_MAX_ATTEMPTS', 6);
        $forwarder = new Forwarder();

        WebhookEvent::where('forward_status', 'failed')
            ->where('forward_attempts', '<', $maxAttempts)
            ->orderBy('received_at')
            ->chunkById(100, function ($events) use ($forwarder) {
                foreach ($events as $event) {
                    $merchant = Merchant::find($event->merchant_id);
                    if (!$merchant || !$merchant->is_active || empty($merchant->n8n_base_url)) {
                        $event->update(['forward_status' => 'skipped']);
                        continue;
                    }
                    $result = $forwarder->forward($event, $merchant);
                    $event->update([
                        'forward_attempts' => $event->forward_attempts + ($result['attempts'] ?? 1),
                        'forwarded_response_code' => $result['code'] ?? null,
                        'forwarded_response_body' => $result['body'] ?? null,
                        'last_forward_error' => $result['error'] ?? null,
                        'forwarded_at' => $result['ok'] ? now() : null,
                        'forward_status' => $result['ok'] ? 'sent' : 'failed',
                    ]);
                }
            }, 'id');

        return self::SUCCESS;
    }
}


