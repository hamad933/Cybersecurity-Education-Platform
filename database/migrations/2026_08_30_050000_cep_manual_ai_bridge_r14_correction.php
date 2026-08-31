<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE prompt_packages DROP CONSTRAINT IF EXISTS prompt_package_status_check');
        DB::statement('ALTER TABLE imported_ai_results DROP CONSTRAINT IF EXISTS imported_ai_status_check');
        
        DB::statement('ALTER TABLE prompt_packages ALTER COLUMN status TYPE VARCHAR(40)');
        DB::statement('ALTER TABLE imported_ai_results ALTER COLUMN status TYPE VARCHAR(40)');

        DB::table('prompt_packages')->where('status', 'exported')->update(['status' => 'exported']);
        DB::table('prompt_packages')->where('status', 'result_imported')->update(['status' => 'result_imported']);
        DB::table('prompt_packages')->where('status', 'decided')->update(['status' => 'superseded']);
        DB::table('prompt_packages')->where('status', 'cancelled')->update(['status' => 'superseded']);
        
        DB::table('imported_ai_results')->where('status', 'pending_review')->update(['status' => 'awaiting_human_review']);
        DB::table('imported_ai_results')->where('status', 'accepted')->update(['status' => 'accepted']);
        DB::table('imported_ai_results')->where('status', 'rejected')->update(['status' => 'rejected']);

        DB::statement('ALTER TABLE ai_proposal_decisions DROP CONSTRAINT IF EXISTS ai_proposal_decision_check');
        DB::statement('ALTER TABLE ai_proposal_decisions ALTER COLUMN decision TYPE VARCHAR(40)');

        Schema::table('ai_proposal_decisions', function (Blueprint $table) {
            $table->string('proposal_id', 36)->nullable();
            $table->unsignedInteger('sequence')->default(1);
        });

        $decisions = DB::table('ai_proposal_decisions')
            ->join('imported_ai_results', 'ai_proposal_decisions.imported_ai_result_id', '=', 'imported_ai_results.id')
            ->select('ai_proposal_decisions.id', 'imported_ai_results.structured_result')
            ->get();

        foreach ($decisions as $decision) {
            $result = json_decode($decision->structured_result, true);
            $blocks = $result['proposed_blocks'] ?? [];
            if (!is_array($blocks) || count($blocks) !== 1 || !isset($blocks[0]['proposal_id']) || trim($blocks[0]['proposal_id']) === '') {
                throw new LogicException("Cannot backfill legacy decision {$decision->id}: Ambiguous or missing proposal_id mapping.");
            }
            DB::table('ai_proposal_decisions')
                ->where('id', $decision->id)
                ->update(['proposal_id' => $blocks[0]['proposal_id']]);
        }

        Schema::table('ai_proposal_decisions', function (Blueprint $table) {
            $table->string('proposal_id', 36)->nullable(false)->change();
            $table->dropUnique(['imported_ai_result_id']);
            $table->unique(['imported_ai_result_id', 'proposal_id', 'sequence']);
        });

        DB::table('ai_proposal_decisions')->where('decision', 'ACCEPT_AS_DRAFT')->update(['decision' => 'accept']);
        DB::table('ai_proposal_decisions')->where('decision', 'REJECT')->update(['decision' => 'reject']);

        DB::statement("ALTER TABLE prompt_packages ADD CONSTRAINT prompt_package_status_check CHECK (status IN ('draft', 'exported', 'awaiting_manual_processing', 'result_imported', 'superseded'))");
        DB::statement("ALTER TABLE imported_ai_results ADD CONSTRAINT imported_ai_status_check CHECK (status IN ('result_imported', 'structural_validation_failed', 'provenance_validation_failed', 'awaiting_human_review', 'partially_accepted', 'accepted', 'rejected', 'superseded'))");
        DB::statement("ALTER TABLE ai_proposal_decisions ADD CONSTRAINT ai_proposal_decision_check CHECK (decision IN ('accept', 'edit_into_new_draft', 'reject', 'defer', 'request_evidence'))");
    }

    public function down(): void
    {
        $hasMulti = DB::table('ai_proposal_decisions')
            ->select('imported_ai_result_id')
            ->groupBy('imported_ai_result_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
            
        if ($hasMulti) {
            throw new LogicException('Down migration failed: Cannot collapse multiple decisions per imported result to a single decision row.');
        }

        $unrepresentableDecisions = DB::table('ai_proposal_decisions')
            ->whereNotIn('decision', ['ACCEPT_AS_DRAFT', 'REJECT', 'edit_into_new_draft', 'reject', 'accept'])
            ->exists();
        if ($unrepresentableDecisions) {
            throw new LogicException('Down migration failed: Cannot revert new decision types to legacy.');
        }

        $unrepresentableResults = DB::table('imported_ai_results')
            ->whereNotIn('status', ['pending_review', 'accepted', 'rejected', 'awaiting_human_review'])
            ->exists();
        if ($unrepresentableResults) {
            throw new LogicException('Down migration failed: Cannot revert new lifecycle statuses to legacy.');
        }
        
        $unrepresentablePrompts = DB::table('prompt_packages')
            ->whereNotIn('status', ['exported', 'result_imported', 'superseded']) // we can't revert draft or awaiting_manual_processing reliably without losing info
            ->exists();
        if ($unrepresentablePrompts) {
            throw new LogicException('Down migration failed: Cannot revert new prompt statuses to legacy.');
        }

        // Now destructively revert
        DB::statement('ALTER TABLE ai_proposal_decisions DROP CONSTRAINT IF EXISTS ai_proposal_decision_check');
        DB::statement('ALTER TABLE prompt_packages DROP CONSTRAINT IF EXISTS prompt_package_status_check');
        DB::statement('ALTER TABLE imported_ai_results DROP CONSTRAINT IF EXISTS imported_ai_status_check');

        DB::table('ai_proposal_decisions')->whereIn('decision', ['accept', 'edit_into_new_draft'])->update(['decision' => 'ACCEPT_AS_DRAFT']);
        DB::table('ai_proposal_decisions')->where('decision', 'reject')->update(['decision' => 'REJECT']);
        
        Schema::table('ai_proposal_decisions', function (Blueprint $table) {
            $table->dropUnique(['imported_ai_result_id', 'proposal_id', 'sequence']);
            $table->dropColumn('sequence');
            $table->dropColumn('proposal_id');
            $table->unique('imported_ai_result_id');
        });

        DB::table('imported_ai_results')->where('status', 'awaiting_human_review')->update(['status' => 'pending_review']);
        
        DB::table('prompt_packages')->where('status', 'superseded')->update(['status' => 'decided']);

        DB::statement('ALTER TABLE prompt_packages ALTER COLUMN status TYPE VARCHAR(24)');
        DB::statement('ALTER TABLE imported_ai_results ALTER COLUMN status TYPE VARCHAR(24)');
        DB::statement('ALTER TABLE ai_proposal_decisions ALTER COLUMN decision TYPE VARCHAR(24)');

        DB::statement("ALTER TABLE prompt_packages ADD CONSTRAINT prompt_package_status_check CHECK (status IN ('exported','result_imported','decided','cancelled'))");
        DB::statement("ALTER TABLE imported_ai_results ADD CONSTRAINT imported_ai_status_check CHECK (status IN ('pending_review','accepted','rejected'))");
        DB::statement("ALTER TABLE ai_proposal_decisions ADD CONSTRAINT ai_proposal_decision_check CHECK (decision IN ('ACCEPT_AS_DRAFT','REJECT'))");
    }
};
