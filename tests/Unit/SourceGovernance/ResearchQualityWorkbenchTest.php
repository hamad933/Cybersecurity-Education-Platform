<?php

namespace Tests\Unit\SourceGovernance;

use App\Modules\SourceGovernance\Application\ResearchQuality\ResearchQualityWorkbench;
use PHPUnit\Framework\TestCase;

final class ResearchQualityWorkbenchTest extends TestCase
{
    public function test_conflicting_claim_variants_require_human_reconciliation_without_system_truth_decision(): void
    {
        $analysis = (new ResearchQualityWorkbench)->analyze([
            $this->source('SRC-A', 'المصدر أ', 'scope A', 'excluded A', 'supported'),
            $this->source('SRC-B', 'المصدر ب', 'scope B', 'excluded B', 'qualified'),
        ], ['CLAIM-1', 'CLAIM-MISSING']);

        self::assertCount(1, $analysis['conflicts']);
        self::assertSame('CLAIM-1', $analysis['conflicts'][0]['claim_id']);
        self::assertSame('requires_human_reconciliation', $analysis['conflicts'][0]['status']);
        self::assertNull($analysis['conflicts'][0]['preferred_source_id']);
        self::assertNull($analysis['conflicts'][0]['system_truth_decision']);
        self::assertTrue($analysis['reconciliation']['human_judgment_required']);
        self::assertNull($analysis['reconciliation']['system_truth_decision']);
        self::assertSame('human', $analysis['review']['decision_authority']);
        self::assertFalse($analysis['review']['system_may_decide_truth']);
        self::assertFalse($analysis['review']['evidence_review']);
        self::assertSame(['CLAIM-MISSING'], $analysis['revision_reasoning']['unresolved_claim_ids']);
    }

    public function test_equivalent_claim_variants_are_not_fabricated_as_conflicts(): void
    {
        $analysis = (new ResearchQualityWorkbench)->analyze([
            $this->source('SRC-A', 'المصدر أ', 'same scope', 'same excluded', 'supported'),
            $this->source('SRC-B', 'المصدر ب', 'same scope', 'same excluded', 'supported'),
        ], ['CLAIM-1']);

        self::assertSame([], $analysis['conflicts']);
        self::assertFalse($analysis['reconciliation']['human_judgment_required']);
        self::assertSame(['CLAIM-1'], $analysis['revision_reasoning']['resolved_claim_ids']);
        self::assertSame([], $analysis['revision_reasoning']['unresolved_claim_ids']);
        self::assertSame('descriptive_source_comparison_not_truth_ranking', $analysis['comparison']['meaning']);
        self::assertSame('traceability_inspection_not_truth_decision', $analysis['provenance']['meaning']);
    }

    /** @return array<string, mixed> */
    private function source(
        string $id,
        string $title,
        string $supportedScope,
        string $excludedSemantics,
        string $assessment,
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'authority_class' => 'primary',
            'review_status' => 'reviewed',
            'sha256' => str_repeat('a', 64),
            'exact_url' => 'https://example.test/'.$id,
            'relative_path' => null,
            'claims' => [[
                'id' => $id.':claim',
                'claim_id' => 'CLAIM-1',
                'segment_ref' => 'section:1',
                'supported_scope' => $supportedScope,
                'excluded_semantics' => $excludedSemantics,
                'assessment' => $assessment,
                'used_by_active_revision' => true,
            ]],
        ];
    }
}
