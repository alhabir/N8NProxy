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
        Schema::create('app_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event_name')->index();
            $table->string('salla_merchant_id')->nullable()->index();
            $table->foreignUuid('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload');
            $table->dateTime('event_created_at')->nullable()->index();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_events');
    }
};
