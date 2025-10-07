<?php

use App\Models\Merchant;
use App\Models\WebhookEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('webhook_events')) {
            return;
        }

        if (!Schema::hasColumn('webhook_events', 'merchant_id')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->foreignUuid('merchant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('merchants')
                    ->nullOnDelete();
                $table->index('merchant_id');
            });
        }

        $hasMerchantId = Schema::hasColumn('webhook_events', 'merchant_id');
        $hasSallaMerchantId = Schema::hasColumn('webhook_events', 'salla_merchant_id');

        if ($hasMerchantId && $hasSallaMerchantId) {
            WebhookEvent::query()
                ->whereNull('merchant_id')
                ->orderBy('id')
                ->chunk(100, function ($events) {
                    foreach ($events as $event) {
                        if (!$event->salla_merchant_id) {
                            continue;
                        }

                        $merchant = Merchant::where('salla_merchant_id', $event->salla_merchant_id)->first();

                        if ($merchant) {
                            $event->merchant_id = $merchant->id;
                            $event->save();
                        }
                    }
                });
        }

        if ($hasMerchantId && $hasSallaMerchantId) {
            $remaining = DB::table('webhook_events')
                ->whereNull('merchant_id')
                ->count();

            if ($remaining === 0 && Schema::getConnection()->getDriverName() === 'mysql') {
                Schema::table('webhook_events', function (Blueprint $table) {
                    $table->dropIndex('webhook_events_salla_merchant_id_index');
                    $table->dropIndex('webhook_events_salla_merchant_id_created_at_index');
                    $table->dropColumn('salla_merchant_id');
                });
            } elseif ($remaining === 0) {
                // TODO: drop webhook_events.salla_merchant_id on SQLite manually once supported.
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('webhook_events')) {
            return;
        }

        if (!Schema::hasColumn('webhook_events', 'salla_merchant_id')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->string('salla_merchant_id')->nullable()->index();
                $table->index(['salla_merchant_id', 'created_at']);
            });
        }

        if (Schema::hasColumn('webhook_events', 'merchant_id')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                $table->dropConstrainedForeignId('merchant_id');
            });
        }
    }
};
