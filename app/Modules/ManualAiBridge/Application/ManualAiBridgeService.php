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

        return DB::transaction(function () use ($actorId, $purpose, $scope, $input): array {
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
                'target_type' => 'prompt_package_revision',
                'target_identifier' => (string) $revision->id,
                'correlation_id' => (string) $prompt->id,
                'outcome' => 'success',
                'safe_metadata' => ['revision' => 1, 'input_digest' => $revision->input_digest, 'package_digest' => $package['manifest']['package_digest']],
            ]);

            return [
                'prompt' => $prompt,
                'revision' => $revision,
                'package_id' => (string) $package['record']->id,
                'package_digest' => $package['manifest']['package_digest'],
            ];
        });
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

        if (str_starts_with($content, "PK\x03\x04")) {
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
            $promptId = $scope['prompt_package_id'] ?? null;
            $revisionNumber = $scope['prompt_revision'] ?? null;
            $inputDigest = $scope['input_digest'] ?? null;
            if (! is_string($promptId) || ! is_int($revisionNumber) || ! is_string($inputDigest)) {
                throw new InvalidArgumentException('AI result provenance is incomplete.');
            }
            $revision = PromptPackageRevision::query()
                ->where('prompt_package_id', $promptId)
                ->where('revision', $revisionNumber)
                ->firstOrFail();
            if (! hash_equals($revision->input_digest, $inputDigest)) {
                throw new InvalidArgumentException('AI result input digest does not match the exported prompt.');
            }

            $result = json_decode($verified->files['result.json'] ?? '', true, 64, JSON_THROW_ON_ERROR);
            $this->validateResult($result);
            $digest = CanonicalJson::sha256($result);
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
        } else {
            $result = json_decode($content, true, 64, JSON_THROW_ON_ERROR);
            $this->validateResult($result);

            $promptId = $result['prompt_package_id'] ?? null;
            $revisionNumber = $result['prompt_revision'] ?? null;
            $inputDigest = $result['input_digest'] ?? null;

            if (! is_string($promptId) || ! is_int($revisionNumber) || ! is_string($inputDigest)) {
                throw new InvalidArgumentException('AI result provenance fields are invalid.');
            }

            $revision = PromptPackageRevision::query()
                ->where('prompt_package_id', $promptId)
                ->where('revision', $revisionNumber)
                ->firstOrFail();

            if (! hash_equals($revision->input_digest, $inputDigest)) {
                throw new InvalidArgumentException('AI result input digest does not match the exported prompt.');
            }

            $digest = CanonicalJson::sha256($result);
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

            $scope = [
                'prompt_package_id' => $promptId,
                'prompt_revision' => $revisionNumber,
                'input_digest' => $inputDigest,
            ];
            $mirror = $this->packages->create(
                'manual-ai-result',
                1,
                $actorId,
                $scope,
                ['result.json' => CanonicalJson::encode($result)."\n"],
                ownerModule: 'MOD-AIB'
            );
        }

        return DB::transaction(function () use ($actorId, $revision, $result, $digest, $mirror): ImportedAiResult {
            $import = ImportedAiResult::query()->firstOrCreate(
                ['prompt_package_revision_id' => $revision->id, 'result_digest' => $digest],
                [
                    'actor_id' => $actorId,
                    'portable_package_id' => $mirror['record']->id,
                    'structured_result' => $result,
                    'status' => 'pending_review',
                    'imported_at' => now(),
                ],
            );
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

    public function decide(string $resultId, string $actorId, string $decision, string $rationale): AiProposalDecision
    {
        if (! in_array($decision, ['ACCEPT_AS_DRAFT', 'REJECT'], true) || trim($rationale) === '' || mb_strlen($rationale) > 2000) {
            throw new InvalidArgumentException('AI review decision or rationale is invalid.');
        }

        return DB::transaction(function () use ($resultId, $actorId, $decision, $rationale): AiProposalDecision {
            $result = ImportedAiResult::query()->lockForUpdate()->whereKey($resultId)->where('actor_id', $actorId)->firstOrFail();
            if ($result->status !== 'pending_review') {
                throw new LogicException('AI result already has a final decision.');
            }
            $structuredResult = $result->getAttribute('structured_result');
            if (! is_array($structuredResult)) {
                throw new LogicException('Imported AI result payload is malformed.');
            }
            $lessonId = $decision === 'ACCEPT_AS_DRAFT' ? $this->drafts->create($structuredResult, $actorId) : null;
            $record = AiProposalDecision::query()->create([
                'imported_ai_result_id' => $result->id,
                'actor_id' => $actorId,
                'decision' => $decision,
                'rationale' => trim($rationale),
                'lesson_revision_id' => $lessonId,
                'decided_at' => now(),
            ]);
            $result->forceFill(['status' => $decision === 'ACCEPT_AS_DRAFT' ? 'accepted' : 'rejected'])->save();
            PromptPackageRevision::query()->whereKey($result->prompt_package_revision_id)->firstOrFail();
            PromptPackage::query()
                ->whereKey(PromptPackageRevision::query()->findOrFail($result->prompt_package_revision_id)->prompt_package_id)
                ->update(['status' => 'decided']);
            $this->audit->append([
                'actor_identifier' => $actorId,
                'action' => 'manual_ai.result.decided',
                'target_type' => 'ai_proposal_decision',
                'target_identifier' => (string) $record->id,
                'correlation_id' => (string) $result->id,
                'outcome' => 'success',
                'safe_metadata' => ['decision' => $decision, 'lesson_revision_created' => $lessonId !== null],
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
