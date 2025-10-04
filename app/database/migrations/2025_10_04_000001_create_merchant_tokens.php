<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_tokens', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('merchant_id')->index();
            $t->string('salla_merchant_id')->index();
            $t->text('access_token');
            $t->text('refresh_token');
            $t->timestamp('access_token_expires_at')->nullable();
            $t->timestampsTz();
            $t->unique(['merchant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_tokens');
    }
};
