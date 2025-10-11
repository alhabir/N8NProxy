<?php

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('merchants')) {
            return;
        }

        if (!Schema::hasColumn('merchants', 'user_id')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('merchants', 'n8n_base_url')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->string('n8n_base_url')->nullable();
            });
        }

        if (!Schema::hasColumn('merchants', 'n8n_webhook_path')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->string('n8n_webhook_path')->nullable();
            });
        }

        if (!Schema::hasColumn('merchants', 'n8n_auth_type')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->string('n8n_auth_type')->default('none');
            });
        } else {
            DB::table('merchants')
                ->whereNull('n8n_auth_type')
                ->update(['n8n_auth_type' => 'none']);
        }

        if (!Schema::hasColumn('merchants', 'n8n_auth_token')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->text('n8n_auth_token')->nullable();
            });
        }

        $columnsToTrack = [
            'user_id',
            'email',
            'n8n_webhook_path',
            'n8n_path',
            'n8n_auth_type',
            'n8n_auth_token',
            'n8n_bearer_token',
            'n8n_basic_user',
            'n8n_basic_pass',
        ];
        $columnExists = [];
        $selectColumns = ['id'];
        foreach ($columnsToTrack as $column) {
            $columnExists[$column] = Schema::hasColumn('merchants', $column);
            if ($columnExists[$column]) {
                $selectColumns[] = $column;
            }
        }

        $merchantQuery = Merchant::query();

        if (!Schema::hasColumn('merchants', 'deleted_at')) {
            $merchantQuery = $merchantQuery->withoutGlobalScopes();
        }

        $merchantQuery
            ->select(array_unique($selectColumns))
            ->orderBy('id')
            ->chunk(100, function ($chunk) use ($columnExists) {
                foreach ($chunk as $merchant) {
                    $dirty = false;

                    if (($columnExists['email'] ?? false) && empty($merchant->email)) {
                        $merchant->email = sprintf('merchant_%s@example.com', $merchant->id);
                        $dirty = true;
                    }

                    if (($columnExists['email'] ?? false) && ($columnExists['user_id'] ?? false) && !$merchant->user_id && $merchant->email) {
                        $user = Schema::hasTable('users')
                            ? User::where('email', $merchant->email)->first()
                            : null;
                        if ($user) {
                            $merchant->user_id = $user->id;
                            $dirty = true;
                        }
                    }

                    if (($columnExists['n8n_webhook_path'] ?? false) && ($columnExists['n8n_path'] ?? false) && empty($merchant->n8n_webhook_path) && !empty($merchant->n8n_path)) {
                        $merchant->n8n_webhook_path = $merchant->n8n_path;
                        $dirty = true;
                    }

                    if (($columnExists['n8n_auth_token'] ?? false) && empty($merchant->n8n_auth_token)) {
                        if (($columnExists['n8n_auth_type'] ?? false) && $merchant->n8n_auth_type === 'bearer' && ($columnExists['n8n_bearer_token'] ?? false) && $merchant->n8n_bearer_token) {
                            $merchant->n8n_auth_token = $merchant->n8n_bearer_token;
                            $dirty = true;
                        } elseif (
                            ($columnExists['n8n_auth_type'] ?? false)
                            && $merchant->n8n_auth_type === 'basic'
                            && ($columnExists['n8n_basic_user'] ?? false)
                            && ($columnExists['n8n_basic_pass'] ?? false)
                            && $merchant->n8n_basic_user
                            && $merchant->n8n_basic_pass
                        ) {
                            $merchant->n8n_auth_token = json_encode([
                                'username' => $merchant->n8n_basic_user,
                                'password' => $merchant->n8n_basic_pass,
                            ]);
                            $dirty = true;
                        }
                    }

                    if ($dirty) {
                        $merchant->save();
                    }
                }
            });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('merchants', 'password') ? 'password' : null,
                Schema::hasColumn('merchants', 'n8n_path') ? 'n8n_path' : null,
                Schema::hasColumn('merchants', 'n8n_bearer_token') ? 'n8n_bearer_token' : null,
                Schema::hasColumn('merchants', 'n8n_basic_user') ? 'n8n_basic_user' : null,
                Schema::hasColumn('merchants', 'n8n_basic_pass') ? 'n8n_basic_pass' : null,
            ]));

            if (!empty($columnsToDrop)) {
                Schema::table('merchants', function (Blueprint $table) use ($columnsToDrop) {
                    $table->dropColumn($columnsToDrop);
                });
            }
        } else {
            // TODO: Drop legacy password/n8n auth columns on SQLite once supported.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('merchants')) {
            return;
        }

        if (Schema::hasColumn('merchants', 'user_id')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        foreach (['n8n_webhook_path', 'n8n_auth_token'] as $column) {
            if (Schema::hasColumn('merchants', $column)) {
                Schema::table('merchants', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (!Schema::hasColumn('merchants', 'password')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->string('password')->nullable();
            });
        }

        if (!Schema::hasColumn('merchants', 'n8n_path')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->string('n8n_path')->nullable();
            });
        }

        if (!Schema::hasColumn('merchants', 'n8n_bearer_token')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->text('n8n_bearer_token')->nullable();
            });
        }

        if (!Schema::hasColumn('merchants', 'n8n_basic_user')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->string('n8n_basic_user')->nullable();
            });
        }

        if (!Schema::hasColumn('merchants', 'n8n_basic_pass')) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->text('n8n_basic_pass')->nullable();
            });
        }
    }
};
