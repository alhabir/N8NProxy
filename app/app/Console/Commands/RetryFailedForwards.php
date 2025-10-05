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

        WebhookEvent::where('status', 'failed')
            ->where('attempts', '<', $maxAttempts)
            ->orderBy('created_at')
            ->chunkById(100, function ($events) use ($forwarder) {
                foreach ($events as $event) {
                    $merchant = Merchant::where('salla_merchant_id', $event->salla_merchant_id)->first();
                    if (!$merchant || !$merchant->is_active || empty($merchant->n8n_base_url)) {
                        $event->update([
                            'status' => 'skipped',
                            'last_error' => 'inactive_merchant',
                        ]);
                        continue;
                    }
                    $result = $forwarder->forward($event, $merchant);
                    $event->update([
                        'attempts' => $event->attempts + ($result['attempts'] ?? 1),
                        'last_error' => $result['error'] ?? null,
                        'status' => $result['ok'] ? 'sent' : 'failed',
                    ]);
                }
            }, 'id');

        return self::SUCCESS;
    }
}


