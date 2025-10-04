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
            $table->uuid('merchant_id')->nullable()->index();
            $table->string('salla_merchant_id')->nullable()->index();
            $table->string('resource')->index();
            $table->string('action')->index();
            $table->string('method', 10);
            $table->string('endpoint');
            $table->json('request_meta')->nullable();
            $table->integer('status_code')->nullable();
            $table->json('response_meta')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('set null');
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
