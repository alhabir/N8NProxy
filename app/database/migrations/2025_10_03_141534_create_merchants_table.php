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
        Schema::create('merchants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('store_id')->unique()->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable()->unique();
            $table->string('salla_merchant_id')->nullable()->index();
            $table->string('store_name')->nullable();
            $table->string('n8n_base_url')->nullable();
            $table->string('n8n_webhook_path')->nullable();
            $table->string('n8n_auth_type')->default('none');
            $table->text('n8n_auth_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->timestamp('last_ping_ok_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
