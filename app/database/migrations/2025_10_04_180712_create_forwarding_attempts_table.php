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
        Schema::create('forwarding_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('webhook_event_id');
            $table->string('target_url');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedInteger('duration_ms');
            $table->timestamps();

            $table->foreign('webhook_event_id')->references('id')->on('webhook_events')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forwarding_attempts');
    }
};