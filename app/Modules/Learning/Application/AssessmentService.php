<?php

namespace App\Modules\Learning\Application;

use App\Modules\Learning\Domain\AssessmentResultDto;
use App\Modules\Learning\Models\AssessmentAttempt;
use App\Modules\Learning\Models\AssessmentDefinition;
use App\Modules\Learning\Models\AssessmentResult;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

final class AssessmentService
{
    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function recursiveKeySort(array $data): array
    {
        ksort($data);
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    // Do not sort lists, just sort objects inside the list
                    foreach ($value as &$item) {
                        if (is_array($item)) {
                            $item = $this->recursiveKeySort($item);
                        }
                    }
                } else {
                    $value = $this->recursiveKeySort($value);
                }
            }
        }
        return $data;
    }

    /**
     * @param array<mixed> $definition
     * @return array<string, mixed>
     */
    private function validateAndNormalizeDefinition(array $definition): array
    {
        if (array_is_list($definition)) {
            throw new InvalidArgumentException("Assessment definition must be an associative object.");
        }

        $expectedAnswers = $definition['expected_answers'] ?? null;
        if (!is_array($expectedAnswers) || empty($expectedAnswers) || array_is_list($expectedAnswers)) {
            throw new InvalidArgumentException("Assessment definition is malformed or missing a valid associative 'expected_answers' map.");
        }

        foreach ($expectedAnswers as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw new InvalidArgumentException("Assessment definition 'expected_answers' keys must be non-empty strings.");
            }
            if (strlen($key) > 100) {
                throw new InvalidArgumentException("Assessment definition 'expected_answers' keys must not exceed 100 characters.");
            }
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
                throw new InvalidArgumentException("Assessment definition 'expected_answers' keys must contain only alphanumeric characters, dashes, and underscores.");
            }
            if (!is_string($value) && !is_int($value) && !is_bool($value)) {
                throw new InvalidArgumentException("Assessment definition 'expected_answers' values must be scalar (string, int, bool).");
            }
        }

        return $this->recursiveKeySort($definition);
    }

    /**
     * @param array<string, mixed> $definition
     */
    public function createDefinition(string $assessmentId, string $capabilityId, string $knowledgeUnitId, array $definition): AssessmentDefinition
    {
        $normalizedDefinition = $this->validateAndNormalizeDefinition($definition);
        $encoded = json_encode($normalizedDefinition, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $digest = hash('sha256', $encoded);

        return DB::transaction(function () use ($assessmentId, $capabilityId, $knowledgeUnitId, $normalizedDefinition, $digest) {
            $latestRevision = AssessmentDefinition::query()
                ->where('assessment_id', $assessmentId)
                ->max('revision') ?? 0;
            
            $nextRevision = $latestRevision + 1;

            $def = new AssessmentDefinition();
            $def->assessment_id = $assessmentId;
            $def->revision = $nextRevision;
            $def->capability_id = $capabilityId;
            $def->knowledge_unit_id = $knowledgeUnitId;
            $def->definition = $normalizedDefinition;
            $def->forceFill(['digest' => $digest])->save();

            return $def;
        });
    }

    public function startAttempt(string $assessmentDefinitionId, string $actorId): AssessmentAttempt
    {
        $definition = AssessmentDefinition::query()->find($assessmentDefinitionId);
        if (! $definition instanceof AssessmentDefinition) {
            throw new InvalidArgumentException("Assessment definition not found.");
        }

        return AssessmentAttempt::query()->create([
            'assessment_definition_id' => $definition->id,
            'actor_id' => $actorId,
            'status' => 'in_progress',
            'answers' => null,
            'started_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $answers
     */
    public function submitAttempt(AssessmentAttempt $attempt, array $answers): void
    {
        if ($attempt->status === 'submitted') {
            throw new LogicException("Assessment attempt is already submitted and terminal.");
        }

        $definition = AssessmentDefinition::query()->find($attempt->assessment_definition_id);
        if (! $definition instanceof AssessmentDefinition) {
            throw new RuntimeException("Assessment definition not found during submission.");
        }

        $definitionPayload = $definition->definitionPayload();
        $expectedAnswers = $definitionPayload['expected_answers'] ?? [];
        if (!is_array($expectedAnswers)) {
            $expectedAnswers = [];
        }

        if (array_is_list($answers) && !empty($answers)) {
            throw new InvalidArgumentException("Submitted answers must be an associative map.");
        }

        foreach ($answers as $key => $value) {
            if (!array_key_exists($key, $expectedAnswers)) {
                throw new InvalidArgumentException("Submitted answer contains unauthorized or unknown key: '{$key}'.");
            }
            if (!is_string($value) && !is_int($value) && !is_bool($value)) {
                throw new InvalidArgumentException("Submitted answer value for '{$key}' must be a supported scalar type (string, int, bool).");
            }
            if (gettype($value) !== gettype($expectedAnswers[$key])) {
                throw new InvalidArgumentException("Submitted answer type for '{$key}' is incompatible with the expected definition type.");
            }
        }

        DB::transaction(function () use ($attempt, $answers) {
            $updatedRows = DB::table('assessment_attempts')
                ->where('id', $attempt->id)
                ->where('status', 'in_progress')
                ->update([
                    'status' => 'submitted',
                    'answers' => json_encode($answers, JSON_THROW_ON_ERROR),
                    'submitted_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updatedRows === 0) {
                 throw new LogicException("Assessment attempt is already submitted and terminal, or modified concurrently.");
            }
            
            $attempt->refresh();
            $this->evaluateAttempt($attempt);
        });
    }

    public function evaluateAttempt(AssessmentAttempt $attempt): AssessmentResult
    {
        if ($attempt->status !== 'submitted') {
            throw new LogicException("Cannot evaluate an attempt that is not submitted.");
        }

        return DB::transaction(function () use ($attempt) {
            // Postgres-safe concurrency: lock the attempt row for update inside this transaction.
            $lockedAttempt = AssessmentAttempt::query()
                ->where('id', $attempt->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedAttempt instanceof AssessmentAttempt) {
                throw new RuntimeException("Assessment attempt could not be locked for evaluation.");
            }

            // Must re-check authoritative state on the locked row to prevent stale evaluation.
            if ($lockedAttempt->status !== 'submitted') {
                throw new LogicException("Authoritative assessment attempt state is not submitted.");
            }

            // Authoritative readback under lock
            $existingResult = AssessmentResult::query()
                ->where('assessment_attempt_id', $lockedAttempt->id)
                ->first();
                
            if ($existingResult instanceof AssessmentResult) {
                return $existingResult;
            }

            /** @var array<string, mixed>|null $answers */
            $answers = $lockedAttempt->answers;
            if (!is_array($answers)) {
                $answers = [];
            }

            $definition = AssessmentDefinition::query()->find($lockedAttempt->assessment_definition_id);
            if (! $definition instanceof AssessmentDefinition) {
                throw new RuntimeException("Assessment definition not found during evaluation.");
            }

            $definitionPayload = $definition->definitionPayload();
            
            // Re-validate legacy or corrupt persisted definition through the exact same contract
            try {
                $validDefinition = $this->validateAndNormalizeDefinition($definitionPayload);
            } catch (InvalidArgumentException $e) {
                throw new LogicException("Assessment definition is malformed and unscoreable: " . $e->getMessage());
            }

            /** @var array<string, mixed> $expectedAnswers */
            $expectedAnswers = $validDefinition['expected_answers'];
            
            $score = 0;
            $total = count($expectedAnswers);

            foreach ($expectedAnswers as $key => $expectedValue) {
                if (isset($answers[$key]) && $answers[$key] === $expectedValue) {
                    $score++;
                }
            }

            $outcome = ($score === $total) ? 'passed' : 'failed';

            return AssessmentResult::query()->create([
                'assessment_attempt_id' => $lockedAttempt->id,
                'outcome' => $outcome,
                'score_details' => [
                    'score' => $score,
                    'total' => $total,
                ],
                'evaluated_at' => now(),
            ]);
        });
    }

    public function getCandidateEvidence(AssessmentResult $result): AssessmentResultDto
    {
        $attempt = AssessmentAttempt::query()->find($result->assessment_attempt_id);
        if (! $attempt instanceof AssessmentAttempt) {
            throw new RuntimeException("Assessment attempt not found for result.");
        }

        $definition = AssessmentDefinition::query()->find($attempt->assessment_definition_id);
        if (! $definition instanceof AssessmentDefinition) {
            throw new RuntimeException("Assessment definition not found for result.");
        }
        
        return new AssessmentResultDto(
            origin: 'MANUAL_ASSESSMENT',
            capability_id: (string) $definition->capability_id,
            knowledge_unit_id: (string) $definition->knowledge_unit_id,
            outcome: $result->outcome,
            payload: [
                'result_id' => $result->id,
                'assessment_definition_id' => $definition->id,
                'assessment_id' => $definition->assessment_id,
                'revision' => $definition->revision,
                'definition_digest' => $definition->digest,
                'attempt_id' => $attempt->id,
                'actor_id' => $attempt->actor_id,
                'outcome' => $result->outcome,
                'score_details' => $result->score_details,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'evaluated_at' => $result->evaluated_at?->toIso8601String(),
            ]
        );
    }
}
