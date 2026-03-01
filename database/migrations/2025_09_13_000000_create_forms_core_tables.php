<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('forms', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('key');
      $table->string('title');
      $table->enum('owner_scope', ['global','tenant']);
      $table->uuid('account_id')->nullable();
      $table->boolean('tenant_visible')->default(true);
      $table->uuid('parent_form_id')->nullable();
      $table->string('status')->default('active');
      $table->timestamps();
      $table->unique(['owner_scope', 'account_id', 'key']);
    });

    Schema::create('form_drafts', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->uuid('form_id');
      $table->uuid('account_id')->nullable();
      $table->jsonb('schema_json');
      $table->jsonb('ui_schema_json');
      $table->jsonb('slots_json')->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();
      $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
    });

    Schema::create('form_versions', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->uuid('form_id');
      $table->uuid('account_id')->nullable();
      $table->string('semver');
      $table->jsonb('schema_json');
      $table->jsonb('ui_schema_json');
      $table->jsonb('slots_json')->nullable();
      $table->jsonb('ui_step_maps');
      $table->jsonb('fragment_version_ids')->nullable();
      $table->string('content_hash');
      $table->uuid('published_by')->nullable();
      $table->timestampTz('published_at');
      $table->timestamps();
      $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
      $table->unique(['form_id','semver']);
    });

    Schema::create('form_variants', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->uuid('account_id')->nullable();
      $table->uuid('form_version_id');
      $table->string('key');
      $table->string('ui_schema_key');
      $table->integer('traffic_allocation')->default(100);
      $table->timestamps();
      $table->foreign('form_version_id')->references('id')->on('form_versions')->cascadeOnDelete();
      $table->unique(['form_version_id','key']);
    });

    Schema::create('form_access_periods', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->uuid('account_id')->nullable();
      $table->uuid('form_version_id');
      $table->timestampTz('starts_at')->nullable();
      $table->timestampTz('ends_at')->nullable();
      $table->string('status')->default('scheduled');
      $table->timestamps();
      $table->foreign('form_version_id')->references('id')->on('form_versions')->cascadeOnDelete();
    });
  }
  public function down(): void {
    Schema::dropIfExists('form_access_periods');
    Schema::dropIfExists('form_variants');
    Schema::dropIfExists('form_versions');
    Schema::dropIfExists('form_drafts');
    Schema::dropIfExists('forms');
  }
};
