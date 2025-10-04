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
        Schema::create('salla_action_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('merchant_id')->nullable();
            $table->string('salla_merchant_id')->nullable();
            $table->string('resource')->index();             // orders/products/customers/...
            $table->string('action')->index();               // create/get/list/update/delete/...
            $table->string('method', 10);
            $table->string('endpoint');                      // resolved URL
            $table->json('request_meta')->nullable();        // query, payload (sanitized)
            $table->integer('status_code')->nullable();
            $table->json('response_meta')->nullable();       // truncated body, timing
            $table->integer('duration_ms')->nullable();
            $table->timestampsTz();
            
            $table->index(['merchant_id', 'resource']);
            $table->index(['salla_merchant_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salla_action_audits');
    }
};