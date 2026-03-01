<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_responses', function (Blueprint $table) {
            // Use UUIDs as PK
            $table->uuid('id')->primary();
            $table->uuid('account_id')->nullable();

            // Tenant / scope (UUID) — use foreignUuid helper
            /*$table->foreignUuid(config('forms.tenant_column_name', 'account') . '_id')
                ->constrained(config('forms.tenant_model', 'app\Models\Account'))
                ->cascadeOnDelete()
                ->cascadeOnUpdate();*/

            // Form references (UUIDs)
            $table->foreignUuid('form_id')
                ->constrained('forms')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('form_version_id')
                ->constrained('form_versions')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('form_variant_id')
                ->nullable()
                ->constrained('form_variants')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Polymorphic subject (no FK) — store as UUID + type
            $table->string('subject_type');
            $table->uuid('subject_id');

            // Payload
            $table->jsonb('responses_json')->default(DB::raw("'{}'::jsonb"));

            // Audit
           /* $table->foreignUuid('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();*/

            $table->timestampTz('submitted_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->timestamps();

            // Indexes for common access patterns
            $table->index(['account_id', 'form_id', 'created_at'], 'idx_form_responses_account_form_created_at');
            $table->index(['form_version_id'], 'idx_form_responses_form_version_id');
            $table->index(['subject_type', 'subject_id'], 'idx_form_responses_subject');
            //$table->index(['submitted_by'], 'idx_form_responses_submitted_by');
        });

        // GIN index for JSONB payloads (Postgres)
        DB::statement("CREATE INDEX idx_form_responses_responses_gin ON form_responses USING GIN (responses_json jsonb_path_ops)");
    }

    public function down(): void
    {
        // Remove GIN index (Postgres) then drop table (which drops FKs)
        DB::statement('DROP INDEX IF EXISTS idx_form_responses_responses_gin');
        Schema::dropIfExists('form_responses');
    }
};
