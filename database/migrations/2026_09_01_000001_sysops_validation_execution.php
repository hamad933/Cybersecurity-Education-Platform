<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_execution_evidence', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('execution_id'); // E.g., ProcessingRun ID or equivalent execution bound
            $table->string('artifact_type', 120);
            $table->unsignedInteger('technical_findings_count')->default(0);
            $table->unsignedInteger('knowledge_findings_count')->default(0);
            $table->char('findings_digest', 64);
            $table->timestampTz('created_at');
            
            $table->foreign('execution_id')->references('id')->on('processing_runs')->cascadeOnDelete();
            
            // Same-execution validation binding ensures only ONE evidence set per execution+artifact_type.
            $table->unique(['execution_id', 'artifact_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_execution_evidence');
    }
};
