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
            $table->uuid('merchant_id')->index();            // FK to merchants.id
            $table->string('salla_merchant_id')->index();    // redundant quick lookup
            $table->text('access_token');                    // encrypted cast
            $table->text('refresh_token');                   // encrypted cast
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestampsTz();
            $table->unique(['merchant_id']);
            
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
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