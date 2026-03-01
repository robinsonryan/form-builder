<?php

declare(strict_types=1);

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
        Schema::create('form_idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key');
            $table->uuid('account_id')->nullable();
            $table->string('request_hash')->nullable();
            $table->integer('response_status')->nullable();
            $table->jsonb('response_body')->nullable();
            $table->jsonb('response_headers')->nullable();
            $table->timestamps();

            $table->unique(['key', 'account_id'], 'form_idempotency_unique_key_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_idempotency_keys');
    }
};
