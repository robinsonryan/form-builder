<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('form_extensions', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->uuid('base_form_id');
      $table->uuid('account_id')->nullable();
      $table->string('name');
      $table->jsonb('extension_schema_json');
      $table->jsonb('extension_ui_json');
      $table->timestamps();
      $table->foreign('base_form_id')->references('id')->on('forms')->cascadeOnDelete();
    });

    Schema::create('form_publications', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->uuid('base_form_id');
      $table->uuid('account_id')->nullable();
      $table->uuid('form_version_id');
      $table->jsonb('extension_ids')->nullable();
      $table->timestamps();
      $table->foreign('base_form_id')->references('id')->on('forms')->cascadeOnDelete();
      $table->foreign('form_version_id')->references('id')->on('form_versions')->cascadeOnDelete();
    });
  }
  public function down(): void {
    Schema::dropIfExists('form_publications');
    Schema::dropIfExists('form_extensions');
  }
};
