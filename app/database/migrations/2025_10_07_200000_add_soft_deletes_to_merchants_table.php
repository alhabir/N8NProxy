<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('merchants')) {
            return;
        }

        Schema::table('merchants', function (Blueprint $table) {
            if (! Schema::hasColumn('merchants', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('merchants')) {
            return;
        }

        Schema::table('merchants', function (Blueprint $table) {
            if (Schema::hasColumn('merchants', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
