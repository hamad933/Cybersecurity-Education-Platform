<?php

namespace App\Application\ProgressEvidenceBridge;

use App\Modules\Evidence\Application\ProgressEvidenceService;

/**
 * Neutral application port for immutable Result DTOs. It does not read or write
 * Simulator storage and stops at W04 Candidate Evidence intake.
 */
final class ProgressEvidenceResultIntakePort
{
    public function __construct(private readonly ProgressEvidenceService $evidence) {}

    /** @return array{receipt:array<string,mixed>,candidate:array<string,mixed>} */
    public function receive(ResultEvidenceHandoff $handoff): array
    {
        $facts = [
            ['key' => 'source_result_manifest_digest', 'value' => strtolower($handoff->manifestDigest)],
            ['key' => 'source_result_provenance', 'value' => $handoff->provenance],
        ];
        if ($handoff->sourceHandoffId !== null) {
            $facts[] = ['key' => 'source_handoff_id', 'value' => $handoff->sourceHandoffId];
        }

        $receipt = $this->evidence->registerSourceHandoffReceipt(
            $handoff->subjectActorId,
            $handoff->deliveredBy,
            [
                'source_type' => 'SIMULATION_RUN_RESULT',
                'source_id' => $handoff->sourceResultId,
                'source_revision' => (string) $handoff->sourceResultRevision,
                'source_digest' => strtolower($handoff->sourceResultDigest),
                'selected_material_refs' => $handoff->artifactRefs,
                'capability_id' => $handoff->capabilityId,
                'facts' => $facts,
                'metadata' => [
                    'schema' => 'cep.progress-evidence.result-intake.v1',
                    'source_fixture' => $handoff->sourceFixture,
                ],
            ],
        );

        $candidate = $this->evidence->intakeCandidate(
            $handoff->subjectActorId,
            $handoff->deliveredBy,
            (string) $receipt['id'],
            [
                'evidence_claim' => $handoff->evidenceClaim,
                'criterion_scope' => $handoff->criterionScope,
                'governed_purpose' => 'FORMAL_CAPABILITY_EVIDENCE',
                'title' => $handoff->title,
                'summary' => $handoff->summary,
            ],
        );

        return ['receipt' => $receipt, 'candidate' => $candidate];
    }
}
