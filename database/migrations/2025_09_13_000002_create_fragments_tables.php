<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('form_fragments', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('key');
      $table->string('title');
      $table->enum('owner_scope', ['global','tenant']);
      $table->boolean('tenant_visible')->default(true);
      $table->uuid('account_id')->nullable();
      $table->jsonb('schema_fragment_json');
      $table->jsonb('ui_fragment_json')->nullable();
      $table->jsonb('params_schema_json')->nullable();
      $table->jsonb('slots_json')->nullable();
      $table->string('status')->default('active');
      $table->timestamps();
      $table->unique(['owner_scope','account_id','key']);
    });

    Schema::create('form_fragment_versions', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->uuid('account_id')->nullable();
      $table->uuid('fragment_id');
      $table->string('semver');
      $table->jsonb('schema_fragment_json');
      $table->jsonb('ui_fragment_json')->nullable();
      $table->jsonb('params_schema_json')->nullable();
      $table->jsonb('slots_json')->nullable();
      $table->string('content_hash');
      $table->timestampTz('published_at');
      $table->timestamps();
      $table->foreign('fragment_id')->references('id')->on('form_fragments')->cascadeOnDelete();
      $table->unique(['fragment_id','semver']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('form_fragment_versions');
    Schema::dropIfExists('form_fragments');
  }
};
