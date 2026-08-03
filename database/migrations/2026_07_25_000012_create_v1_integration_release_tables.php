<?php

use App\Modules\Platform\Audit\AuditHash;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_records', function (Blueprint $table): void {
            $table->unsignedBigInteger('sequence_no')->nullable()->unique();
            $table->char('previous_hash', 64)->nullable();
            $table->char('record_hash', 64)->nullable()->unique();
        });
        $previous = null;
        $sequence = 0;
        DB::table('audit_records')->orderBy('occurred_at')->orderBy('id')->get()->each(function (object $row) use (&$previous, &$sequence): void {
            $sequence++;
            $occurred = CarbonImmutable::parse($row->occurred_at)->utc()->format('Y-m-d\\TH:i:s.u\\Z');
            $metadata = is_string($row->safe_metadata) ? json_decode($row->safe_metadata, true, 512, JSON_THROW_ON_ERROR) : (array) $row->safe_metadata;
            $hash = AuditHash::calculate([
                'sequence_no' => $sequence,
                'previous_hash' => $previous,
                'actor_identifier' => $row->actor_identifier,
                'action' => $row->action,
                'target_type' => $row->target_type,
                'target_identifier' => $row->target_identifier,
                'correlation_id' => (string) $row->correlation_id,
                'outcome' => $row->outcome,
                'safe_metadata' => $metadata,
                'occurred_at' => $occurred,
            ]);
            DB::table('audit_records')->where('id', $row->id)->update([
                'sequence_no' => $sequence,
                'previous_hash' => $previous,
                'record_hash' => $hash,
            ]);
            $previous = $hash;
        });
        DB::statement('ALTER TABLE audit_records ALTER COLUMN sequence_no SET NOT NULL');
        DB::statement('ALTER TABLE audit_records ALTER COLUMN record_hash SET NOT NULL');
        DB::statement("ALTER TABLE audit_records ADD CONSTRAINT audit_record_hash_check CHECK (record_hash ~ '^[0-9a-f]{64}$')");
        DB::statement("ALTER TABLE audit_records ADD CONSTRAINT audit_previous_hash_check CHECK (previous_hash IS NULL OR previous_hash ~ '^[0-9a-f]{64}$')");

        Schema::table('blob_objects', function (Blueprint $table): void {
            $table->string('owner_module', 16)->default('MOD-PLT');
            $table->string('purpose', 80)->default('generic');
            $table->string('owner_identifier', 160)->nullable();
            $table->index(['owner_module', 'purpose']);
        });
        Schema::table('processing_runs', function (Blueprint $table): void {
            $table->timestampTz('leased_until')->nullable();
            $table->timestampTz('next_attempt_at')->nullable();
            $table->unsignedInteger('max_attempts')->default(3);
            $table->string('worker_identifier', 160)->nullable();
        });

        Schema::create('portable_packages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('package_type', 80);
            $table->unsignedSmallInteger('schema_version');
            $table->string('owner_module', 16);
            $table->uuid('actor_id');
            $table->jsonb('scope');
            $table->jsonb('manifest');
            $table->char('package_digest', 64);
            $table->uuid('blob_object_id');
            $table->string('status', 24);
            $table->timestampTz('created_at');
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
            $table->foreign('blob_object_id')->references('id')->on('blob_objects')->restrictOnDelete();
            $table->index(['package_type', 'created_at']);
        });
        DB::statement("ALTER TABLE portable_packages ADD CONSTRAINT portable_package_status_check CHECK (status IN ('verified','exported','rejected'))");
        DB::statement("ALTER TABLE portable_packages ADD CONSTRAINT portable_package_digest_check CHECK (package_digest ~ '^[0-9a-f]{64}$')");

        Schema::create('source_imports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->uuid('blob_object_id');
            $table->uuid('source_record_id')->nullable();
            $table->string('original_name', 300);
            $table->string('detected_media_type', 160);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('status', 24);
            $table->string('rejection_code', 80)->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
            $table->foreign('blob_object_id')->references('id')->on('blob_objects')->restrictOnDelete();
            $table->foreign('source_record_id')->references('id')->on('source_records')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });
        DB::statement("ALTER TABLE source_imports ADD CONSTRAINT source_import_status_check CHECK (status IN ('quarantined','accepted','rejected'))");

        Schema::create('search_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('document_type', 80);
            $table->string('document_identifier', 160);
            $table->string('title_ar', 500)->default('');
            $table->string('title_en', 500)->default('');
            $table->text('body_ar')->default('');
            $table->text('body_en')->default('');
            $table->jsonb('facets')->default('{}');
            $table->timestampTz('indexed_at');
            $table->timestampsTz();
            $table->unique(['document_type', 'document_identifier']);
        });
        DB::statement("ALTER TABLE search_documents ADD COLUMN search_vector tsvector GENERATED ALWAYS AS (to_tsvector('simple', coalesce(title_ar,'') || ' ' || coalesce(title_en,'') || ' ' || coalesce(body_ar,'') || ' ' || coalesce(body_en,''))) STORED");
        DB::statement('CREATE INDEX search_documents_vector_gin ON search_documents USING GIN (search_vector)');

        Schema::create('prompt_packages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->string('purpose', 120);
            $table->string('status', 24);
            $table->unsignedInteger('current_revision')->default(1);
            $table->timestampsTz();
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
        });
        Schema::create('prompt_package_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('prompt_package_id');
            $table->unsignedInteger('revision');
            $table->uuid('portable_package_id');
            $table->char('input_digest', 64);
            $table->jsonb('declared_scope');
            $table->timestampTz('exported_at');
            $table->unique(['prompt_package_id', 'revision']);
            $table->foreign('prompt_package_id')->references('id')->on('prompt_packages')->restrictOnDelete();
            $table->foreign('portable_package_id')->references('id')->on('portable_packages')->restrictOnDelete();
        });
        Schema::create('imported_ai_results', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->uuid('prompt_package_revision_id');
            $table->uuid('portable_package_id');
            $table->char('result_digest', 64);
            $table->jsonb('structured_result');
            $table->string('status', 24);
            $table->timestampTz('imported_at');
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
            $table->foreign('prompt_package_revision_id')->references('id')->on('prompt_package_revisions')->restrictOnDelete();
            $table->foreign('portable_package_id')->references('id')->on('portable_packages')->restrictOnDelete();
            $table->unique(['prompt_package_revision_id', 'result_digest']);
        });
        Schema::create('ai_proposal_decisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('imported_ai_result_id')->unique();
            $table->uuid('actor_id');
            $table->string('decision', 24);
            $table->text('rationale');
            $table->uuid('lesson_revision_id')->nullable();
            $table->timestampTz('decided_at');
            $table->foreign('imported_ai_result_id')->references('id')->on('imported_ai_results')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
            $table->foreign('lesson_revision_id')->references('id')->on('lesson_revisions')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE prompt_packages ADD CONSTRAINT prompt_package_status_check CHECK (status IN ('exported','result_imported','decided','cancelled'))");
        DB::statement("ALTER TABLE imported_ai_results ADD CONSTRAINT imported_ai_status_check CHECK (status IN ('pending_review','accepted','rejected'))");
        DB::statement("ALTER TABLE ai_proposal_decisions ADD CONSTRAINT ai_proposal_decision_check CHECK (decision IN ('ACCEPT_AS_DRAFT','REJECT'))");

        Schema::create('imported_evidence_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->uuid('portable_package_id');
            $table->string('origin', 40);
            $table->string('capability_id', 80);
            $table->string('knowledge_unit_id', 80);
            $table->string('status', 24);
            $table->jsonb('claims');
            $table->jsonb('limitations');
            $table->char('content_digest', 64);
            $table->uuid('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
            $table->foreign('portable_package_id')->references('id')->on('portable_packages')->restrictOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('owner_accounts')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE imported_evidence_records ADD CONSTRAINT imported_evidence_origin_check CHECK (origin IN ('REAL_LAB','MANUAL_ASSESSMENT','SOURCE_REVIEW'))");
        DB::statement("ALTER TABLE imported_evidence_records ADD CONSTRAINT imported_evidence_status_check CHECK (status IN ('pending_review','accepted','rejected'))");

        Schema::create('backup_manifests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->uuid('portable_package_id');
            $table->string('status', 24);
            $table->string('database_driver', 40);
            $table->jsonb('table_counts');
            $table->jsonb('blob_inventory');
            $table->char('content_digest', 64);
            $table->timestampTz('created_at');
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
            $table->foreign('portable_package_id')->references('id')->on('portable_packages')->restrictOnDelete();
        });
        Schema::create('restore_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('actor_id')->nullable();
            $table->uuid('backup_manifest_id');
            $table->string('target_database', 160);
            $table->string('status', 24);
            $table->jsonb('verification');
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->nullOnDelete();
            $table->foreign('backup_manifest_id')->references('id')->on('backup_manifests')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE backup_manifests ADD CONSTRAINT backup_manifest_status_check CHECK (status IN ('verified','failed'))");
        DB::statement("ALTER TABLE restore_runs ADD CONSTRAINT restore_run_status_check CHECK (status IN ('staged','verified','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('restore_runs');
        Schema::dropIfExists('backup_manifests');
        Schema::dropIfExists('imported_evidence_records');
        Schema::dropIfExists('ai_proposal_decisions');
        Schema::dropIfExists('imported_ai_results');
        Schema::dropIfExists('prompt_package_revisions');
        Schema::dropIfExists('prompt_packages');
        Schema::dropIfExists('search_documents');
        Schema::dropIfExists('source_imports');
        Schema::dropIfExists('portable_packages');
        Schema::table('processing_runs', function (Blueprint $table): void {
            $table->dropColumn(['leased_until', 'next_attempt_at', 'max_attempts', 'worker_identifier']);
        });
        Schema::table('blob_objects', function (Blueprint $table): void {
            $table->dropIndex(['owner_module', 'purpose']);
            $table->dropColumn(['owner_module', 'purpose', 'owner_identifier']);
        });
        DB::statement('ALTER TABLE audit_records DROP CONSTRAINT IF EXISTS audit_record_hash_check');
        DB::statement('ALTER TABLE audit_records DROP CONSTRAINT IF EXISTS audit_previous_hash_check');
        Schema::table('audit_records', function (Blueprint $table): void {
            $table->dropUnique(['sequence_no']);
            $table->dropUnique(['record_hash']);
            $table->dropColumn(['sequence_no', 'previous_hash', 'record_hash']);
        });
    }
};
