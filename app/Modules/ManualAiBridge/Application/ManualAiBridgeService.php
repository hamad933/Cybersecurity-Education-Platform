<?php

namespace App\Modules\ManualAiBridge\Application;

use App\Modules\ManualAiBridge\Models\AiProposalDecision;
use App\Modules\ManualAiBridge\Models\ImportedAiResult;
use App\Modules\ManualAiBridge\Models\PromptPackage;
use App\Modules\ManualAiBridge\Models\PromptPackageRevision;
use App\Modules\Platform\Audit\AuditWriter;
use App\Modules\Platform\Packages\SafePackageService;
use App\Modules\Platform\Support\CanonicalJson;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ManualAiBridgeService
{
    public function __construct(
        private readonly SafePackageService $packages,
        private readonly AiDraftCreationService $drafts,
        private readonly AuditWriter $audit,
    ) {}

    /**
     * @param  array<string,mixed>  $scope
     * @param  array<string,mixed>  $input
     * @return array{prompt:PromptPackage,revision:PromptPackageRevision,package_id:string,package_digest:string}
     */
    public function exportPrompt(string $actorId, string $purpose, array $scope, array $input): array
    {
        $purpose = trim($purpose);
        if ($purpose === '' || mb_strlen($purpose) > 120 || $input === []) {
            throw new InvalidArgumentException('Manual AI prompt purpose or input is invalid.');
        }

        $exported = DB::transaction(function () use ($actorId, $purpose, $scope, $input): array {
            $prompt = PromptPackage::query()->create([
                'actor_id' => $actorId,
                'purpose' => $purpose,
                'status' => 'exported',
                'current_revision' => 1,
            ]);
            $declared = [
                'prompt_package_id' => (string) $prompt->id,
                'prompt_revision' => 1,
                'purpose' => $purpose,
                'scope' => $scope,
                'manual_execution_only' => true,
                'automatic_network_provider' => false,
            ];
            $payload = ['contract' => $declared, 'input' => $input];
            $inputDigest = CanonicalJson::sha256($payload);

            $provenance = [
                'prompt_package_id' => (string) $prompt->id,
                'prompt_revision' => 1,
                'input_digest' => $inputDigest,
            ];

            $package = $this->packages->create(
                'manual-ai-prompt',
                1,
                $actorId,
                $declared,
                [
                    'prompt.json' => CanonicalJson::encode($payload)."\n",
                    'provenance.json' => CanonicalJson::encode($provenance)."\n",
                    'RESULT_SCHEMA.json' => CanonicalJson::encode($this->resultSchema())."\n",
                    'README.txt' => "Run this prompt manually. Your final output must be exactly one JSON file matching RESULT_SCHEMA.json. Include the exact fields from provenance.json in your result.\n",
                ],
                ownerModule: 'MOD-AIB',
            );
            $revision = PromptPackageRevision::query()->create([
                'prompt_package_id' => $prompt->id,
                'revision' => 1,
                'portable_package_id' => $package['record']->id,
                'input_digest' => $inputDigest,
                'declared_scope' => $declared,
                'exported_at' => now(),
            ]);
            $this->audit->append([
                'actor_identifier' => $actorId,
                'action' => 'manual_ai.prompt.exported',
                'target_type' => 'prompt_package',
                'target_identifier' => (string) $prompt->id,
                'correlation_id' => (string) $revision->id,
                'outcome' => 'success',
                'safe_metadata' => ['input_digest' => $inputDigest],
            ]);

            return [
                'prompt' => $prompt,
                'revision' => $revision,
                'package_id' => (string) $package['record']->id,
                'package_digest' => $package['manifest']['package_digest'],
            ];
        });

        DB::transaction(function () use ($exported, $actorId): void {
            $prompt = PromptPackage::query()->lockForUpdate()->whereKey($exported['prompt']->id)->firstOrFail();
            $prompt->status = 'awaiting_manual_processing';
            $prompt->save();

            $this->audit->append([
                'actor_identifier' => $actorId,
                'action' => 'manual_ai.prompt.awaiting_processing',
                'target_type' => 'prompt_package',
                'target_identifier' => (string) $prompt->id,
                'correlation_id' => (string) $exported['revision']->id,
                'outcome' => 'success',
            ]);
        });

        return $exported;
    }

    /** @param resource $stream */
    public function importResult($stream, string $actorId): ImportedAiResult
    {
        if (! is_resource($stream)) {
            throw new InvalidArgumentException('A readable result stream is required.');
        }

        $content = stream_get_contents($stream);
        if ($content === false) {
            throw new InvalidArgumentException('Failed to read AI result stream.');
        }

        if (!str_starts_with($content, "PK\x03\x04")) {
            throw new \InvalidArgumentException('Manual AI Result import must use a ZIP SafePackage, raw JSON transport is rejected.');
        }

        $zipStream = fopen('php://memory', 'r+');
        if ($zipStream === false) {
            throw new LogicException('Unable to open memory stream for package verification.');
        }
        fwrite($zipStream, $content);
        rewind($zipStream);
        try {
            $verified = $this->packages->verifyStream($zipStream, ['manual-ai-result']);
        } finally {
            fclose($zipStream);
        }

        $scope = $verified->manifest['scope'] ?? null;
        if (! is_array($scope) || ($verified->manifest['actor_id'] ?? null) !== $actorId) {
            throw new InvalidArgumentException('AI result package actor or scope is invalid.');
        }

        try {
            $result = json_decode($verified->files['result.json'] ?? '', true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new InvalidArgumentException('AI result payload is not valid JSON.');
        }
        if (! is_array($result)) {
            throw new InvalidArgumentException('AI result payload must be a JSON object.');
        }

        $this->validateResult($result);

        $promptId = $scope['prompt_package_id'] ?? null;
        $revisionNumber = $scope['prompt_revision'] ?? null;
        $inputDigest = $scope['input_digest'] ?? null;

        if (! is_string($promptId) || ! is_int($revisionNumber) || ! is_string($inputDigest)) {
            throw new InvalidArgumentException('AI result provenance scope fields are incomplete or invalid.');
        }

        if ($result['prompt_package_id'] !== $promptId || $result['prompt_revision'] !== $revisionNumber || $result['input_digest'] !== $inputDigest) {
            throw new InvalidArgumentException('AI returned provenance claims mismatch the verified package scope.');
        }

        $promptPackage = PromptPackage::query()
            ->whereKey($promptId)
            ->firstOrFail();

        if ($promptPackage->actor_id !== $actorId) {
            throw new InvalidArgumentException('Actor does not own the Prompt Package.');
        }

        $revision = PromptPackageRevision::query()
            ->where('prompt_package_id', $promptPackage->id)
            ->where('revision', $revisionNumber)
            ->firstOrFail();

        if (! hash_equals($revision->input_digest, $inputDigest)) {
            throw new InvalidArgumentException('AI result input digest does not match the exported prompt.');
        }

        $digest = CanonicalJson::sha256($result);

        return DB::transaction(function () use ($actorId, $revision, $result, $digest, $scope, $verified): ImportedAiResult {
            // Obtain a transaction-level advisory lock deterministically scoped to this revision and digest
            // to arbitrate concurrent imports before creating expensive durable side effects (mirror/audit).
            // We use the two 32-bit integer signature of pg_advisory_xact_lock to ensure safety across PostgreSQL versions.
            $lockKeys = unpack('l2', md5($revision->id . ':' . $digest, true));
            if ($lockKeys === false) {
                throw new LogicException('Unable to derive deterministic advisory lock keys.');
            }
            DB::statement('SELECT pg_advisory_xact_lock(?, ?)', [$lockKeys[1], $lockKeys[2]]);

            $existing = ImportedAiResult::query()
                ->where('prompt_package_revision_id', $revision->id)
                ->where('result_digest', $digest)
                ->first();

            if ($existing !== null) {
                if ($existing->actor_id !== $actorId) {
                    throw new InvalidArgumentException('Existing AI result is actor-bound to another owner.');
                }
                return $existing;
            }

            $mirror = $this->packages->create('manual-ai-result', 1, $actorId, $scope, $verified->files, ownerModule: 'MOD-AIB');

            $import = ImportedAiResult::query()->create([
                'prompt_package_revision_id' => $revision->id,
                'result_digest' => $digest,
                'actor_id' => $actorId,
                'portable_package_id' => $mirror['record']->id,
                'structured_result' => $result,
                'status' => 'awaiting_human_review',
                'imported_at' => now(),
            ]);

            PromptPackage::query()->whereKey($revision->prompt_package_id)->update(['status' => 'result_imported']);

            $this->audit->append([
                'actor_identifier' => $actorId,
                'action' => 'manual_ai.result.imported',
                'target_type' => 'imported_ai_result',
                'target_identifier' => (string) $import->id,
                'correlation_id' => (string) $revision->prompt_package_id,
                'outcome' => 'success',
                'safe_metadata' => ['result_digest' => $digest, 'prompt_revision' => (int) $revision->revision],
            ]);

            return $import;
        });
    }

    public function decide(string $resultId, string $proposalId, string $actorId, string $decision, string $rationale): AiProposalDecision
    {
        if (! in_array($decision, ['accept', 'edit_into_new_draft', 'reject', 'defer', 'request_evidence'], true) || trim($rationale) === '' || mb_strlen($rationale) > 2000) {
            throw new InvalidArgumentException('AI review decision or rationale is invalid.');
        }
        if (trim($proposalId) === '') {
            throw new InvalidArgumentException('Proposal ID cannot be blank.');
        }

        return DB::transaction(function () use ($resultId, $proposalId, $actorId, $decision, $rationale): AiProposalDecision {
            $result = ImportedAiResult::query()->lockForUpdate()->whereKey($resultId)->where('actor_id', $actorId)->firstOrFail();

            if ($result->status === 'superseded') {
                throw new LogicException('AI result is superseded and cannot be modified.');
            }

            $structuredResult = $result->getAttribute('structured_result');
            if (! is_array($structuredResult)) {
                throw new LogicException('Imported AI result payload is malformed.');
            }

            $blocks = $structuredResult['proposed_blocks'] ?? [];
            if (! is_array($blocks)) {
                throw new LogicException('Imported AI result missing proposed blocks.');
            }

            $selectedBlock = null;
            foreach ($blocks as $block) {
                if (($block['proposal_id'] ?? null) === $proposalId) {
                    $selectedBlock = $block;
                    break;
                }
            }

            if ($selectedBlock === null) {
                throw new LogicException('Proposal ID not found in imported result blocks.');
            }

            $existingDecisions = AiProposalDecision::query()
                ->where('imported_ai_result_id', $result->id)
                ->where('proposal_id', $proposalId)
                ->orderBy('sequence', 'asc')
                ->get();

            $nextSequence = 1;
            if ($existingDecisions->isNotEmpty()) {
                $latest = $existingDecisions->last();

                // Idempotent exact retry logic (returns safely even if result is globally accepted/rejected)
                if ($latest->decision === $decision && $latest->rationale === trim($rationale)) {
                    return $latest;
                }

                // Terminality check on the proposal
                if (in_array($latest->decision, ['accept', 'edit_into_new_draft', 'reject'], true)) {
                    throw new LogicException('This proposal already has a terminal decision.');
                }

                $nextSequence = $latest->sequence + 1;
            }

            if (in_array($result->status, ['accepted', 'rejected'], true)) {
                throw new LogicException('AI result already has a final overall decision.');
            }

            $lessonId = in_array($decision, ['accept', 'edit_into_new_draft'], true) ? $this->drafts->create($structuredResult, $proposalId, $actorId) : null;

            $record = AiProposalDecision::query()->create([
                'imported_ai_result_id' => $result->id,
                'proposal_id' => $proposalId,
                'sequence' => $nextSequence,
                'actor_id' => $actorId,
                'decision' => $decision,
                'rationale' => trim($rationale),
                'lesson_revision_id' => $lessonId,
                'decided_at' => now(),
            ]);

            $allDecisions = AiProposalDecision::query()
                ->where('imported_ai_result_id', $result->id)
                ->orderBy('sequence', 'asc')
                ->get();

            $allProposedIds = array_map(fn($b) => $b['proposal_id'] ?? '', $blocks);

            $latestDecisionPerProposal = [];
            foreach ($allDecisions as $d) {
                $latestDecisionPerProposal[$d->proposal_id] = $d->decision;
            }

            $allTerminalReject = true;
            $allTerminal = true;
            $hasAcceptOrEdit = false;

            foreach ($allProposedIds as $pid) {
                $dec = $latestDecisionPerProposal[$pid] ?? null;

                if ($dec === null || in_array($dec, ['defer', 'request_evidence'], true)) {
                    $allTerminal = false;
                    $allTerminalReject = false;
                } else {
                    if ($dec !== 'reject') {
                        $allTerminalReject = false;
                    }
                    if (in_array($dec, ['accept', 'edit_into_new_draft'], true)) {
                        $hasAcceptOrEdit = true;
                    }
                }
            }

            $newStatus = 'awaiting_human_review';
            if ($allTerminal) {
                $newStatus = $allTerminalReject ? 'rejected' : 'accepted';
            } elseif ($hasAcceptOrEdit) {
                $newStatus = 'partially_accepted';
            }

            $result->forceFill(['status' => $newStatus])->save();

            PromptPackageRevision::query()->whereKey($result->prompt_package_revision_id)->firstOrFail();

            $this->audit->append([
                'actor_identifier' => $actorId,
                'action' => 'manual_ai.result.decided',
                'target_type' => 'ai_proposal_decision',
                'target_identifier' => (string) $record->id,
                'correlation_id' => (string) $result->id,
                'outcome' => 'success',
                'safe_metadata' => ['decision' => $decision, 'proposal_id' => $proposalId, 'lesson_revision_created' => $lessonId !== null],
            ]);

            return $record;
        });
    }
private function validateResult(mixed $result): void
    {
        if (! is_array($result) || array_diff(array_keys($result), [
            'prompt_package_id', 'prompt_revision', 'input_digest',
            'knowledge_unit_id', 'proposed_blocks', 'citation_claim_ids', 'derived_from_revision_id',
            'authority_baseline_id', 'limitations', 'confidence',
        ]) !== []) {
            throw new InvalidArgumentException('AI result contains invalid or unknown fields.');
        }
        if (! is_string($result['prompt_package_id'] ?? null) || ! is_int($result['prompt_revision'] ?? null) || ! is_string($result['input_digest'] ?? null)) {
            throw new InvalidArgumentException('AI result provenance fields are invalid.');
        }
        if (! is_string($result['knowledge_unit_id'] ?? null) || ! is_array($result['proposed_blocks'] ?? null) || ! is_array($result['citation_claim_ids'] ?? null)) {
            throw new InvalidArgumentException('AI result required fields are invalid.');
        }
        if (! is_array($result['limitations'] ?? null) || ! is_string($result['confidence'] ?? null)) {
            throw new InvalidArgumentException('AI result limitations and confidence are required.');
        }

        $proposalIds = [];
        foreach ($result['proposed_blocks'] as $block) {
            $pid = $block['proposal_id'] ?? null;
            if (! is_string($pid) || trim($pid) === '') {
                throw new InvalidArgumentException('Proposal block missing valid proposal_id.');
            }
            if (in_array($pid, $proposalIds, true)) {
                throw new InvalidArgumentException('Duplicate proposal_id found in proposed blocks.');
            }
            $proposalIds[] = $pid;
        }

        $encoded = CanonicalJson::encode($result);
        if (strlen($encoded) > (int) config('platform.manual_ai_result_max_bytes', 262_144)) {
            throw new InvalidArgumentException('AI result exceeds the bounded size.');
        }
    }

    /** @return array<string,mixed> */
    private function resultSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['prompt_package_id', 'prompt_revision', 'input_digest', 'knowledge_unit_id', 'proposed_blocks', 'citation_claim_ids', 'limitations', 'confidence'],
            'properties' => [
                'prompt_package_id' => ['type' => 'string'],
                'prompt_revision' => ['type' => 'integer'],
                'input_digest' => ['type' => 'string'],
                'knowledge_unit_id' => ['type' => 'string'],
                'proposed_blocks' => ['type' => 'array'],
                'citation_claim_ids' => ['type' => 'array', 'items' => ['type' => 'string']],
                'derived_from_revision_id' => ['type' => ['string', 'null']],
                'authority_baseline_id' => ['type' => ['string', 'null']],
                'limitations' => ['type' => 'array'],
                'confidence' => ['type' => 'string'],
            ],
        ];
    }
}
