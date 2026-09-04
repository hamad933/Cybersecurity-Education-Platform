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
        // 1. Structural preflight: Ensure representability BEFORE any DDL
        $duplicatesCount = DB::table('evidence_candidates')
            ->select('subject_actor_id', 'semantic_identity_digest')
            ->groupBy('subject_actor_id', 'semantic_identity_digest')
            ->havingRaw('COUNT(*) > 1')
            ->count();
            
        if ($duplicatesCount > 0) {
            throw new \Exception('Cannot upgrade: legacy schema contains ambiguous candidates.');
        }

        // Admitted candidates must map to exactly one admission record
        $admittedCandidates = DB::table('evidence_candidates')->where('state', 'ADMITTED')->get();
        foreach ($admittedCandidates as $c) {
            $admissionsCount = DB::table('evidence_admission_records')->where('candidate_id', $c->id)->count();
            if ($admissionsCount !== 1) {
                throw new \Exception("Cannot upgrade: Admitted candidate {$c->id} resolves to {$admissionsCount} Admissions. Exactly 1 is required for safe upgrade.");
            }
        }

        // 2. Add structural changes
        DB::statement('ALTER TABLE evidence_admission_records DROP CONSTRAINT IF EXISTS evidence_admission_records_evidence_id_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_admission_records_evidence_id_unique');
        Schema::table('evidence_admission_records', function (Blueprint $table) {
            $table->unique(['evidence_id', 'evidence_revision_id']);
        });

        Schema::table('evidence_candidates', function (Blueprint $table) {
            $table->integer('preparation_revision')->default(1)->after('semantic_identity_digest');
            $table->uuid('target_evidence_id')->nullable()->after('preparation_revision');
            $table->uuid('target_evidence_revision_id')->nullable()->after('target_evidence_id');
            
            $table->foreign('target_evidence_id')
                ->references('id')
                ->on('governed_evidence');
            $table->foreign('target_evidence_revision_id')
                ->references('id')
                ->on('governed_evidence_revisions');
        });

        Schema::create('evidence_candidate_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('candidate_id');
            $table->integer('preparation_revision');
            $table->string('proposed_title', 180);
            $table->text('proposed_summary');
            $table->jsonb('proposed_facts');
            $table->jsonb('metadata');
            $table->string('content_digest', 64);
            $table->uuid('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            
            $table->foreign('candidate_id')->references('id')->on('evidence_candidates');
            $table->unique(['candidate_id', 'preparation_revision']);
        });

        Schema::create('evidence_admission_candidate_revisions', function (Blueprint $table) {
            $table->uuid('admission_id')->primary();
            $table->uuid('candidate_revision_id');
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('admission_id')->references('id')->on('evidence_admission_records');
            $table->foreign('candidate_revision_id')->references('id')->on('evidence_candidate_revisions');
        });
        
        // Update admission validation trigger to support N+1 candidates
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cep_validate_evidence_admission_record()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM governed_evidence AS evidence
                    INNER JOIN governed_evidence_revisions AS revision
                        ON revision.evidence_id = evidence.id
                    INNER JOIN evidence_candidates AS candidate
                        ON candidate.admitted_evidence_id = evidence.id
                    WHERE evidence.id = NEW.evidence_id
                      AND candidate.id = NEW.candidate_id
                      AND revision.id = NEW.evidence_revision_id
                      AND candidate.state = 'ADMITTED'
                ) THEN
                    RAISE EXCEPTION 'Evidence admission provenance does not match the canonical Candidate/Evidence/Revision chain';
                END IF;
            
                RETURN NEW;
            END;
            $$;
        ");

        // 3. Add immutability DB-level triggers
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cep_reject_update_delete()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION 'Updates and Deletes are not allowed on this immutable table.';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_evidence_candidate_revisions_immutable
            BEFORE UPDATE OR DELETE ON evidence_candidate_revisions
            FOR EACH ROW EXECUTE FUNCTION cep_reject_update_delete();
            
            CREATE TRIGGER trg_evidence_admission_candidate_revisions_immutable
            BEFORE UPDATE OR DELETE ON evidence_admission_candidate_revisions
            FOR EACH ROW EXECUTE FUNCTION cep_reject_update_delete();
        ");

        // 4. Backfill existing data
        $candidates = DB::table('evidence_candidates')->get();
        foreach ($candidates as $c) {
            $revisionId = (string) Str::uuid7();
            
            // Canonical content digest reconstruction for legacy candidate (no sequence!)
            $payload = json_encode([
                'candidate_id' => $c->id,
                'proposed_title' => $c->proposed_title,
                'proposed_summary' => $c->proposed_summary,
                'proposed_facts' => $c->proposed_facts,
                'metadata' => $c->metadata,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            $contentDigest = hash('sha256', $payload);
            
            // Note: intentionally preserving legacy timestamp via $c->created_at
            DB::table('evidence_candidate_revisions')->insert([
                'id' => $revisionId,
                'candidate_id' => $c->id,
                'preparation_revision' => 1,
                'proposed_title' => $c->proposed_title,
                'proposed_summary' => $c->proposed_summary,
                'proposed_facts' => $c->proposed_facts,
                'metadata' => $c->metadata,
                'content_digest' => $contentDigest,
                'created_by' => $c->submitted_by,
                'created_at' => $c->created_at,
                'updated_at' => $c->created_at,
            ]);
            
            // Link admissions for admitted candidates
            if ($c->state === 'ADMITTED') {
                $admissions = DB::table('evidence_admission_records')
                    ->where('candidate_id', $c->id)
                    ->get();
                    
                $admission = $admissions->first();
                DB::table('evidence_admission_candidate_revisions')->insert([
                    'admission_id' => $admission->id,
                    'candidate_revision_id' => $revisionId,
                    'created_at' => $admission->admitted_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Preflight DOWN: Ensure no duplicate admissions exist that would violate the original unique index BEFORE any DDL
        $duplicatesCount = DB::table('evidence_admission_records')
            ->select('evidence_id')
            ->groupBy('evidence_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
            
        if ($duplicatesCount > 0) {
            throw new \Exception('Cannot rollback: database contains multiple admissions for the same evidence, violating the legacy schema.');
        }

        // Restore old admission validation trigger
        DB::unprepared("
            CREATE OR REPLACE FUNCTION cep_validate_evidence_admission_record()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM governed_evidence AS evidence
                    INNER JOIN governed_evidence_revisions AS revision
                        ON revision.evidence_id = evidence.id
                    INNER JOIN evidence_candidates AS candidate
                        ON candidate.id = evidence.candidate_id
                    WHERE evidence.id = NEW.evidence_id
                      AND evidence.candidate_id = NEW.candidate_id
                      AND revision.id = NEW.evidence_revision_id
                      AND candidate.state = 'ADMITTED'
                      AND candidate.admitted_evidence_id = evidence.id
                ) THEN
                    RAISE EXCEPTION 'Evidence admission provenance does not match the canonical Candidate/Evidence/Revision chain';
                END IF;
            
                RETURN NEW;
            END;
            $$;
        ");

        // Drop triggers
        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_evidence_admission_candidate_revisions_immutable ON evidence_admission_candidate_revisions;
            DROP TRIGGER IF EXISTS trg_evidence_candidate_revisions_immutable ON evidence_candidate_revisions;
            DROP FUNCTION IF EXISTS cep_reject_update_delete();
        ");

        // Drop new schema structures explicitly
        Schema::dropIfExists('evidence_admission_candidate_revisions');
        Schema::dropIfExists('evidence_candidate_revisions');

        DB::statement('ALTER TABLE evidence_admission_records DROP CONSTRAINT IF EXISTS evidence_admission_records_evidence_id_evidence_revision_id_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_admission_records_evidence_id_evidence_revision_id_unique');
        DB::statement('ALTER TABLE evidence_admission_records DROP CONSTRAINT IF EXISTS evidence_admission_records_evidence_id_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_admission_records_evidence_id_unique');
        
        Schema::table('evidence_admission_records', function (Blueprint $table) {
            $table->unique('evidence_id');
        });

        DB::statement('ALTER TABLE evidence_candidates DROP CONSTRAINT IF EXISTS evidence_candidates_target_evidence_id_foreign');
        DB::statement('ALTER TABLE evidence_candidates DROP CONSTRAINT IF EXISTS evidence_candidates_target_evidence_revision_id_foreign');
        
        Schema::table('evidence_candidates', function (Blueprint $table) {
            if (Schema::hasColumn('evidence_candidates', 'preparation_revision')) {
                $table->dropColumn('preparation_revision');
            }
            if (Schema::hasColumn('evidence_candidates', 'target_evidence_id')) {
                $table->dropColumn('target_evidence_id');
            }
            if (Schema::hasColumn('evidence_candidates', 'target_evidence_revision_id')) {
                $table->dropColumn('target_evidence_revision_id');
            }
        });
    }
};
