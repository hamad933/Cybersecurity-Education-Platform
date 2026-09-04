<?php

use App\Modules\Evidence\IntakeReview\Application\ProvenanceDigest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $digest = new ProvenanceDigest();

        // Structural/content preflight before DDL.
        $duplicatesCount = DB::table('evidence_candidates')
            ->select('subject_actor_id', 'semantic_identity_digest')
            ->groupBy('subject_actor_id', 'semantic_identity_digest')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        if ($duplicatesCount > 0) {
            throw new \Exception('Cannot upgrade: legacy schema contains ambiguous candidates.');
        }

        $candidates = DB::table('evidence_candidates')->get();
        $legacyCanonical = [];
        foreach ($candidates as $candidate) {
            if ($candidate->state === 'ADMITTED') {
                $admissionsCount = DB::table('evidence_admission_records')->where('candidate_id', $candidate->id)->count();
                if ($admissionsCount !== 1) {
                    throw new \Exception("Cannot upgrade: Admitted candidate {$candidate->id} resolves to {$admissionsCount} Admissions. Exactly 1 is required for safe upgrade.");
                }
            }

            $factsJson = $digest->canonicalJson($this->decodeLegacyJson($candidate->proposed_facts, 'proposed_facts'));
            $metadataJson = $digest->canonicalJson($this->decodeLegacyJson($candidate->metadata, 'metadata'));
            if (strlen($factsJson) > 65536 || strlen($metadataJson) > 65536) {
                throw new \Exception("Cannot upgrade: Candidate {$candidate->id} contains JSON above the 64KiB canonical boundary.");
            }
            $legacyCanonical[(string) $candidate->id] = [
                'facts' => $factsJson,
                'metadata' => $metadataJson,
                'content_digest' => $digest->digest([
                    'candidate_id' => (string) $candidate->id,
                    'proposed_title' => (string) $candidate->proposed_title,
                    'proposed_summary' => (string) $candidate->proposed_summary,
                    'proposed_facts' => $factsJson,
                    'metadata' => $metadataJson,
                ]),
            ];
        }

        DB::statement('ALTER TABLE evidence_admission_records DROP CONSTRAINT IF EXISTS evidence_admission_records_evidence_id_unique');
        DB::statement('DROP INDEX IF EXISTS evidence_admission_records_evidence_id_unique');
        Schema::table('evidence_admission_records', function (Blueprint $table) {
            $table->unique(['evidence_id', 'evidence_revision_id']);
        });

        Schema::table('evidence_candidates', function (Blueprint $table) {
            $table->integer('preparation_revision')->default(1)->after('semantic_identity_digest');
            $table->uuid('target_evidence_id')->nullable()->after('preparation_revision');
            $table->uuid('target_evidence_revision_id')->nullable()->after('target_evidence_id');
            $table->foreign('target_evidence_id')->references('id')->on('governed_evidence');
            $table->foreign('target_evidence_revision_id')->references('id')->on('governed_evidence_revisions');
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

        DB::unprepared("
            CREATE OR REPLACE FUNCTION cep_validate_evidence_admission_record()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM governed_evidence AS evidence
                    INNER JOIN governed_evidence_revisions AS revision ON revision.evidence_id = evidence.id
                    INNER JOIN evidence_candidates AS candidate ON candidate.admitted_evidence_id = evidence.id
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

        foreach ($candidates as $candidate) {
            $revisionId = (string) Str::uuid7();
            $canonical = $legacyCanonical[(string) $candidate->id];
            DB::table('evidence_candidate_revisions')->insert([
                'id' => $revisionId,
                'candidate_id' => $candidate->id,
                'preparation_revision' => 1,
                'proposed_title' => $candidate->proposed_title,
                'proposed_summary' => $candidate->proposed_summary,
                'proposed_facts' => $canonical['facts'],
                'metadata' => $canonical['metadata'],
                'content_digest' => $canonical['content_digest'],
                'created_by' => $candidate->submitted_by,
                'created_at' => $candidate->created_at,
                'updated_at' => $candidate->created_at,
            ]);
            if ($candidate->state === 'ADMITTED') {
                $admission = DB::table('evidence_admission_records')->where('candidate_id', $candidate->id)->first();
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
        $duplicatesCount = DB::table('evidence_admission_records')
            ->select('evidence_id')
            ->groupBy('evidence_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        if ($duplicatesCount > 0) {
            throw new \Exception('Cannot rollback: database contains multiple admissions for the same evidence, violating the legacy schema.');
        }

        DB::unprepared("
            CREATE OR REPLACE FUNCTION cep_validate_evidence_admission_record()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM governed_evidence AS evidence
                    INNER JOIN governed_evidence_revisions AS revision ON revision.evidence_id = evidence.id
                    INNER JOIN evidence_candidates AS candidate ON candidate.id = evidence.candidate_id
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

        DB::unprepared("
            DROP TRIGGER IF EXISTS trg_evidence_admission_candidate_revisions_immutable ON evidence_admission_candidate_revisions;
            DROP TRIGGER IF EXISTS trg_evidence_candidate_revisions_immutable ON evidence_candidate_revisions;
            DROP FUNCTION IF EXISTS cep_reject_update_delete();
        ");
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

    /** @return array<mixed> */
    private function decodeLegacyJson(mixed $value, string $field): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! is_string($value) || strlen($value) > 65536) {
            throw new \Exception("Cannot upgrade: legacy {$field} is not bounded JSON.");
        }
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new \Exception("Cannot upgrade: legacy {$field} is invalid or non-associative JSON.");
        }
        return $decoded;
    }
};
