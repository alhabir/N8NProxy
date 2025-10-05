<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merchant_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('merchant_id')->index();
            $table->string('salla_merchant_id')->unique();
            $table->text('access_token');
            $table->text('refresh_token');
            $table->timestamp('access_token_expires_at');
            $table->timestamps();

            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants')
                ->cascadeOnDelete();
            $table->unique('merchant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_tokens');
    }
};
