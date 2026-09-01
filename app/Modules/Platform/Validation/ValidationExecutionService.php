<?php

namespace App\Modules\Platform\Validation;

use Illuminate\Support\Facades\DB;
use App\Modules\Platform\Processing\ProcessingRun;
use InvalidArgumentException;

class ValidationExecutionService
{
    public function bindEvidence(
        string $executionId,
        string $artifactType,
        array $technicalFindings,
        array $knowledgeFindings
    ): ValidationExecutionEvidence {
        return DB::transaction(function () use ($executionId, $artifactType, $technicalFindings, $knowledgeFindings) {
            // Lock the owning run row FOR UPDATE
            $run = ProcessingRun::query()->where('id', $executionId)->lockForUpdate()->firstOrFail();
            
            if ($run->status !== 'running') {
                throw new InvalidArgumentException("Validation evidence can only be bound during an active running execution.");
            }
            
            // Normalize and strictly separate findings before hashing
            $payload = [
                'technical' => $technicalFindings,
                'knowledge' => $knowledgeFindings,
            ];
            
            $digest = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

            return ValidationExecutionEvidence::query()->create([
                'execution_id' => $executionId,
                'artifact_type' => $artifactType,
                'technical_findings_count' => count($technicalFindings),
                'knowledge_findings_count' => count($knowledgeFindings),
                'findings_digest' => $digest,
                'created_at' => now(),
            ]);
        });
    }
}
