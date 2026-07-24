<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_revisions', function (Blueprint $table): void {
            $table->text('review_rationale')->nullable();
        });
        Schema::table('scenario_runs', function (Blueprint $table): void {
            $table->uuid('actor_id')->nullable();
            $table->char('request_digest', 64)->nullable();
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
        });
        Schema::table('evidence_records', function (Blueprint $table): void {
            $table->uuid('actor_id')->nullable();
            $table->foreign('actor_id')->references('id')->on('owner_accounts')->restrictOnDelete();
        });
        Schema::table('review_triggers', function (Blueprint $table): void {
            $table->string('source_type', 40)->nullable();
            $table->uuid('source_id')->nullable();
            $table->uuid('rule_revision_id')->nullable();
            $table->string('schedule_reason', 300)->nullable();
        });

        $ownerId = DB::table('owner_accounts')->where('is_active', true)->value('id');
        if (is_string($ownerId)) {
            DB::table('scenario_runs')->whereNull('actor_id')->update(['actor_id' => $ownerId]);
            DB::table('evidence_records')->whereNull('actor_id')->update(['actor_id' => $ownerId]);
        }

        DB::statement("CREATE UNIQUE INDEX review_triggers_active_unique ON review_triggers (actor_id, knowledge_unit_id, failure_class, COALESCE(case_id, '')) WHERE status IN ('open', 'scheduled')");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS review_triggers_active_unique');
        Schema::table('review_triggers', function (Blueprint $table): void {
            $table->dropColumn(['source_type', 'source_id', 'rule_revision_id', 'schedule_reason']);
        });
        Schema::table('evidence_records', function (Blueprint $table): void {
            $table->dropForeign(['actor_id']);
            $table->dropColumn('actor_id');
        });
        Schema::table('scenario_runs', function (Blueprint $table): void {
            $table->dropForeign(['actor_id']);
            $table->dropColumn(['actor_id', 'request_digest']);
        });
        Schema::table('lesson_revisions', function (Blueprint $table): void {
            $table->dropColumn('review_rationale');
        });
    }
};
