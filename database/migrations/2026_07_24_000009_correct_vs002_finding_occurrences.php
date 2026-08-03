<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_findings', function (Blueprint $table): void {
            $table->dropUnique(['finding_key']);
            $table->char('occurrence_key', 64)->unique();
        });
        Schema::table('finding_verifications', function (Blueprint $table): void {
            $table->uuid('actor_id')->nullable();
            $table->char('vulnerable_trace_digest', 64)->nullable();
            $table->char('verification_trace_digest', 64)->nullable();
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('security_findings', function (Blueprint $table): void {
            $table->dropUnique(['occurrence_key']);
            $table->dropColumn('occurrence_key');
            $table->unique('finding_key');
        });
        Schema::table('finding_verifications', function (Blueprint $table): void {
            $table->dropForeign(['actor_id']);
            $table->dropColumn(['actor_id', 'vulnerable_trace_digest', 'verification_trace_digest']);
        });
    }
};
