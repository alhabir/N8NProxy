<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('merchants')) {
            if (! Schema::hasColumn('merchants', 'store_domain')) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->string('store_domain')->nullable()->after('store_name');
                });
            }

            if (! Schema::hasColumn('merchants', 'salla_access_token')) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->text('salla_access_token')->nullable()->after('salla_merchant_id');
                });
            }

            if (! Schema::hasColumn('merchants', 'salla_refresh_token')) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->text('salla_refresh_token')->nullable()->after('salla_access_token');
                });
            }

            if (! Schema::hasColumn('merchants', 'salla_token_expires_at')) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->timestamp('salla_token_expires_at')->nullable()->after('salla_refresh_token');
                });
            }

            if (! Schema::hasColumn('merchants', 'claimed_by_user_id')) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->foreignId('claimed_by_user_id')
                        ->nullable()
                        ->after('user_id')
                        ->constrained('users')
                        ->nullOnDelete();
                });

                if (Schema::hasColumn('merchants', 'user_id')) {
                    DB::table('merchants')
                        ->whereNull('claimed_by_user_id')
                        ->update(['claimed_by_user_id' => DB::raw('user_id')]);
                }
            }

            if (! Schema::hasColumn('merchants', 'connected_at')) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->timestamp('connected_at')->nullable()->after('is_approved');
                });
            }

            if (Schema::hasColumn('merchants', 'salla_merchant_id')
                && ! Schema::hasIndex('merchants', 'merchants_salla_merchant_id_unique')
            ) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->unique('salla_merchant_id', 'merchants_salla_merchant_id_unique');
                });
            }
        }

        if (Schema::hasTable('webhook_events')) {
            if (! Schema::hasColumn('webhook_events', 'response_status')) {
                Schema::table('webhook_events', function (Blueprint $table) {
                    $table->unsignedSmallInteger('response_status')->nullable()->after('status');
                });
            }

            if (! Schema::hasColumn('webhook_events', 'response_body_excerpt')) {
                Schema::table('webhook_events', function (Blueprint $table) {
                    $table->text('response_body_excerpt')->nullable()->after('response_status');
                });
            }

            if (! Schema::hasColumn('webhook_events', 'forwarded_at')) {
                Schema::table('webhook_events', function (Blueprint $table) {
                    $table->timestamp('forwarded_at')->nullable()->after('updated_at');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webhook_events')) {
            Schema::table('webhook_events', function (Blueprint $table) {
                if (Schema::hasColumn('webhook_events', 'forwarded_at')) {
                    $table->dropColumn('forwarded_at');
                }
                if (Schema::hasColumn('webhook_events', 'response_body_excerpt')) {
                    $table->dropColumn('response_body_excerpt');
                }
                if (Schema::hasColumn('webhook_events', 'response_status')) {
                    $table->dropColumn('response_status');
                }
            });
        }

        if (Schema::hasTable('merchants')) {
            if (Schema::hasIndex('merchants', 'merchants_salla_merchant_id_unique')) {
                Schema::table('merchants', function (Blueprint $table) {
                    $table->dropUnique('merchants_salla_merchant_id_unique');
                });
            }

            Schema::table('merchants', function (Blueprint $table) {
                if (Schema::hasColumn('merchants', 'connected_at')) {
                    $table->dropColumn('connected_at');
                }
                if (Schema::hasColumn('merchants', 'claimed_by_user_id')) {
                    $table->dropConstrainedForeignId('claimed_by_user_id');
                }
                if (Schema::hasColumn('merchants', 'salla_token_expires_at')) {
                    $table->dropColumn('salla_token_expires_at');
                }
                if (Schema::hasColumn('merchants', 'salla_refresh_token')) {
                    $table->dropColumn('salla_refresh_token');
                }
                if (Schema::hasColumn('merchants', 'salla_access_token')) {
                    $table->dropColumn('salla_access_token');
                }
                if (Schema::hasColumn('merchants', 'store_domain')) {
                    $table->dropColumn('store_domain');
                }
            });
        }
    }
};
