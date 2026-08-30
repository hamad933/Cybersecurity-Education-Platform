<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_definitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('assessment_id', 100);
            $table->unsignedInteger('revision');
            $table->string('capability_id', 80);
            $table->string('knowledge_unit_id', 80);
            $table->jsonb('definition');
            $table->char('digest', 64);
            $table->timestampsTz();
            $table->unique(['assessment_id', 'revision']);
        });

        Schema::create('assessment_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('assessment_definition_id');
            $table->uuid('actor_id');
            $table->string('status', 24);
            $table->jsonb('answers')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampsTz();
            
            $table->foreign('assessment_definition_id')->references('id')->on('assessment_definitions')->restrictOnDelete();
        });

        Schema::create('assessment_results', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('assessment_attempt_id')->unique();
            $table->string('outcome', 32);
            $table->jsonb('score_details');
            $table->timestampTz('evaluated_at');
            $table->timestampsTz();

            $table->foreign('assessment_attempt_id')->references('id')->on('assessment_attempts')->restrictOnDelete();
        });

        DB::statement("ALTER TABLE assessment_attempts ADD CONSTRAINT assessment_attempt_status_check CHECK (status IN ('in_progress','submitted'))");
        DB::statement("ALTER TABLE assessment_results ADD CONSTRAINT assessment_result_outcome_check CHECK (outcome IN ('passed','failed','needs_review'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessment_definitions');
    }
};
