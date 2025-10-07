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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('salla_event')->index();
            $table->string('salla_event_id')->unique();
            $table->foreignUuid('merchant_id')->nullable()->constrained('merchants')->nullOnDelete();
            $table->string('salla_merchant_id')->index();
            $table->json('headers');
            $table->json('payload');
            $table->enum('status', ['stored','sent','skipped','failed'])->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['salla_merchant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
