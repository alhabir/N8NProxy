<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salla_action_audits', function (Blueprint $t) {
            $t->id();
            $t->uuid('merchant_id')->nullable();
            $t->string('salla_merchant_id')->nullable();
            $t->string('resource')->index();
            $t->string('action')->index();
            $t->string('method', 10);
            $t->string('endpoint');
            $t->json('request_meta')->nullable();
            $t->integer('status_code')->nullable();
            $t->json('response_meta')->nullable();
            $t->integer('duration_ms')->nullable();
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salla_action_audits');
    }
};
