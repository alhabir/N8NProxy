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
            $table->uuid('merchant_id');
            $table->string('salla_event_id')->unique();
            $table->string('event')->index();
            $table->timestampTz('received_at')->index();
            $table->boolean('signature_valid');
            $table->json('headers');
            $table->json('payload');
            $table->enum('forward_status', ['pending','sent','failed','skipped'])->index();
            $table->unsignedInteger('forward_attempts')->default(0);
            $table->text('last_forward_error')->nullable();
            $table->unsignedSmallInteger('forwarded_response_code')->nullable();
            $table->mediumText('forwarded_response_body')->nullable();
            $table->timestampTz('forwarded_at')->nullable();
            $table->timestampsTz();

            $table->foreign('merchant_id')->references('id')->on('merchants')->cascadeOnDelete();
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
