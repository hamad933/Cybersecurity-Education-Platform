<?php

namespace Tests\Feature\IntakeReview;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function postgres_installs_w04_c01_validation_and_immutability_triggers_for_additive_truth(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());

        $triggerNames = collect(DB::select(
            "SELECT tgname
               FROM pg_trigger
              WHERE NOT tgisinternal
                AND tgname IN (
                    'evidence_candidate_intake_events_immutable',
                    'evidence_admission_records_validate',
                    'evidence_admission_records_immutable',
                    'evidence_review_scope_items_validate',
                    'evidence_review_scope_items_immutable',
                    'evidence_review_decision_items_validate',
                    'evidence_review_decision_items_immutable'
                )",
        ))->pluck('tgname');

        $this->assertCount(7, $triggerNames);
        $this->assertTrue($triggerNames->contains('evidence_review_decision_items_validate'));
        $this->assertTrue($triggerNames->contains('evidence_review_decision_items_immutable'));
    }
}
