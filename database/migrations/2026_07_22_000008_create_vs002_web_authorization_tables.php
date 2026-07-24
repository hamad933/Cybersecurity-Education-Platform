<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_endpoint_contract_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('contract_id', 100);
            $table->unsignedInteger('revision');
            $table->string('state', 24);
            $table->string('method', 16);
            $table->string('route_template', 200);
            $table->string('requested_action', 80);
            $table->jsonb('allowed_request_fields');
            $table->string('response_shape_id', 100);
            $table->jsonb('allowed_response_fields');
            $table->string('authority_baseline_id', 100);
            $table->char('digest', 64);
            $table->timestampTz('published_at');
            $table->timestampsTz();
            $table->unique(['contract_id', 'revision']);
        });

        Schema::create('web_authorization_policy_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('policy_id', 100);
            $table->unsignedInteger('revision');
            $table->string('state', 24);
            $table->string('mode', 24);
            $table->jsonb('rules');
            $table->jsonb('source_claim_ids');
            $table->char('digest', 64);
            $table->uuid('remediates_revision_id')->nullable();
            $table->timestampTz('published_at');
            $table->timestampsTz();
            $table->unique(['policy_id', 'revision']);
        });
        Schema::table('web_authorization_policy_revisions', function (Blueprint $table): void {
            $table->foreign('remediates_revision_id')->references('id')->on('web_authorization_policy_revisions')->restrictOnDelete();
        });

        Schema::table('scenario_runs', function (Blueprint $table): void {
            $table->uuid('policy_revision_id')->nullable();
            $table->uuid('endpoint_contract_revision_id')->nullable();
            $table->string('request_id', 80)->nullable();
            $table->string('correlation_id', 80)->nullable();
            $table->uuid('remediation_revision_id')->nullable();
            $table->uuid('verification_of_run_id')->nullable();
            $table->foreign('policy_revision_id')->references('id')->on('web_authorization_policy_revisions')->restrictOnDelete();
            $table->foreign('endpoint_contract_revision_id')->references('id')->on('web_endpoint_contract_revisions')->restrictOnDelete();
            $table->foreign('remediation_revision_id')->references('id')->on('web_authorization_policy_revisions')->restrictOnDelete();
            $table->foreign('verification_of_run_id')->references('id')->on('scenario_runs')->restrictOnDelete();
        });

        Schema::table('evidence_records', function (Blueprint $table): void {
            $table->uuid('policy_revision_id')->nullable();
            $table->uuid('endpoint_contract_revision_id')->nullable();
            $table->string('request_case_id', 100)->nullable();
            $table->jsonb('finding_ids')->default(DB::raw("'[]'::jsonb"));
            $table->uuid('remediation_revision_id')->nullable();
            $table->uuid('verification_run_id')->nullable();
            $table->foreign('policy_revision_id')->references('id')->on('web_authorization_policy_revisions')->restrictOnDelete();
            $table->foreign('endpoint_contract_revision_id')->references('id')->on('web_endpoint_contract_revisions')->restrictOnDelete();
            $table->foreign('remediation_revision_id')->references('id')->on('web_authorization_policy_revisions')->restrictOnDelete();
            $table->foreign('verification_run_id')->references('id')->on('scenario_runs')->restrictOnDelete();
        });

        Schema::create('security_findings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('finding_key', 64)->unique();
            $table->string('category', 40);
            $table->uuid('scenario_run_id');
            $table->uuid('actor_id');
            $table->string('target_resource_id', 100);
            $table->uuid('policy_revision_id');
            $table->string('decisive_missing_check', 120);
            $table->char('trace_digest', 64);
            $table->jsonb('source_claim_ids');
            $table->jsonb('safe_details');
            $table->string('status', 24)->default('open');
            $table->timestampTz('verified_at')->nullable();
            $table->timestampsTz();
            $table->foreign('scenario_run_id')->references('id')->on('scenario_runs')->restrictOnDelete();
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
            $table->foreign('policy_revision_id')->references('id')->on('web_authorization_policy_revisions')->restrictOnDelete();
        });

        Schema::create('finding_verifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('security_finding_id')->unique();
            $table->uuid('vulnerable_run_id');
            $table->uuid('remediation_policy_revision_id');
            $table->uuid('verification_run_id')->unique();
            $table->string('status', 24);
            $table->char('verification_digest', 64);
            $table->timestampTz('verified_at');
            $table->timestampsTz();
            $table->foreign('security_finding_id')->references('id')->on('security_findings')->restrictOnDelete();
            $table->foreign('vulnerable_run_id')->references('id')->on('scenario_runs')->restrictOnDelete();
            $table->foreign('remediation_policy_revision_id')->references('id')->on('web_authorization_policy_revisions')->restrictOnDelete();
            $table->foreign('verification_run_id')->references('id')->on('scenario_runs')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE web_endpoint_contract_revisions ADD CONSTRAINT web_endpoint_state_check CHECK (state = 'published')");
        DB::statement("ALTER TABLE web_authorization_policy_revisions ADD CONSTRAINT web_policy_state_check CHECK (state = 'published')");
        DB::statement("ALTER TABLE web_authorization_policy_revisions ADD CONSTRAINT web_policy_mode_check CHECK (mode IN ('vulnerable','secure','unsupported'))");
        DB::statement("ALTER TABLE security_findings ADD CONSTRAINT security_finding_category_check CHECK (category IN ('access_control','serialization'))");
        DB::statement("ALTER TABLE security_findings ADD CONSTRAINT security_finding_status_check CHECK (status IN ('open','verified_fixed'))");
        DB::statement("ALTER TABLE finding_verifications ADD CONSTRAINT finding_verification_status_check CHECK (status = 'VERIFIED_FIXED')");
        DB::statement('ALTER TABLE scenario_runs DROP CONSTRAINT scenario_run_outcome_check');
        DB::statement("ALTER TABLE scenario_runs ADD CONSTRAINT scenario_run_outcome_check CHECK (outcome IS NULL OR outcome IN ('ALLOW','DENY','UNAUTHENTICATED','NOT_FOUND','INSUFFICIENT_STATE','UNSUPPORTED_STATE'))");
        DB::statement('ALTER TABLE evidence_records DROP CONSTRAINT evidence_result_check');
        DB::statement("ALTER TABLE evidence_records ADD CONSTRAINT evidence_result_check CHECK (result IN ('ALLOW','DENY','UNAUTHENTICATED','NOT_FOUND','INSUFFICIENT_STATE','UNSUPPORTED_STATE'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE evidence_records DROP CONSTRAINT evidence_result_check');
        DB::statement("ALTER TABLE evidence_records ADD CONSTRAINT evidence_result_check CHECK (result IN ('ALLOW','DENY','INSUFFICIENT_STATE','UNSUPPORTED_STATE'))");
        DB::statement('ALTER TABLE scenario_runs DROP CONSTRAINT scenario_run_outcome_check');
        DB::statement("ALTER TABLE scenario_runs ADD CONSTRAINT scenario_run_outcome_check CHECK (outcome IS NULL OR outcome IN ('ALLOW','DENY','INSUFFICIENT_STATE','UNSUPPORTED_STATE'))");
        Schema::dropIfExists('finding_verifications');
        Schema::dropIfExists('security_findings');
        Schema::table('evidence_records', function (Blueprint $table): void {
            $table->dropForeign(['policy_revision_id']);
            $table->dropForeign(['endpoint_contract_revision_id']);
            $table->dropForeign(['remediation_revision_id']);
            $table->dropForeign(['verification_run_id']);
            $table->dropColumn(['policy_revision_id', 'endpoint_contract_revision_id', 'request_case_id', 'finding_ids', 'remediation_revision_id', 'verification_run_id']);
        });
        Schema::table('scenario_runs', function (Blueprint $table): void {
            $table->dropForeign(['policy_revision_id']);
            $table->dropForeign(['endpoint_contract_revision_id']);
            $table->dropForeign(['remediation_revision_id']);
            $table->dropForeign(['verification_of_run_id']);
            $table->dropColumn(['policy_revision_id', 'endpoint_contract_revision_id', 'request_id', 'correlation_id', 'remediation_revision_id', 'verification_of_run_id']);
        });
        Schema::dropIfExists('web_authorization_policy_revisions');
        Schema::dropIfExists('web_endpoint_contract_revisions');
    }
};
