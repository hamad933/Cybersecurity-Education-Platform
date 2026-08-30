<?php

namespace Tests\Feature\KnowledgeLearning\Learn;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Learning\Models\PracticeAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LearnJourneyProjectionTest extends TestCase
{
    use RefreshDatabase;

    private OwnerAccount $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = app(CreateOwner::class)->execute(
            'Learn Journey Owner',
            'learn-journey-owner@example.test',
            'LearnJourney!Pass9',
            (string) Str::uuid7(),
        );
    }

    #[Test]
    public function learn_projects_persisted_practice_definition_attempts_and_today_context(): void
    {
        $unit = $this->knowledgeUnit('KU-W02-C02-001');
        $practice = $this->practice($unit, 'PRACTICE-C02-001', 1, [
            'kind' => 'guided-analysis',
            'title_ar' => 'تحليل حدود الثقة',
            'title_en' => 'Trust boundary analysis',
            'prompt_ar' => 'حدّد مصدر البيانات وحدود الثقة قبل اختيار الإجراء.',
            'difficulty' => 'intermediate',
            'estimated_minutes' => 12,
            'tags' => ['web', 'trust-boundary'],
        ]);

        PracticeAttempt::query()->create([
            'micro_practice_id' => $practice->id,
            'actor_id' => $this->owner->id,
            'case_id' => 'CASE-C02-001',
            'answer' => ['selected' => 'source-first'],
            'outcome' => 'incorrect',
            'rationale_valid' => false,
            'failure_class' => 'missed-boundary',
        ]);

        $this->actingAs($this->owner)->get("/knowledge/learn?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('KnowledgeLearning/Learn')
                ->where('active.id', $unit->id)
                ->has('journey.items', 1)
                ->where('journey.items.0.practice_id', 'PRACTICE-C02-001')
                ->where('journey.items.0.definition.title_ar', 'تحليل حدود الثقة')
                ->where('journey.items.0.definition.estimated_minutes', 12)
                ->where('journey.items.0.attempt_count', 1)
                ->where('journey.items.0.activity_state', 'IN_PROGRESS')
                ->has('journey.items.0.recent_attempts', 1)
                ->where('journey.next.state', 'CONTINUE_PRACTICE')
                ->where('journey.next.practice_id', 'PRACTICE-C02-001')
                ->where('journey.today_projection.knowledge_unit_id', $unit->id)
                ->where('journey.today_projection.recommended_practice_id', 'PRACTICE-C02-001')
                ->where('journey.today_projection.source', 'persisted_learning_activity')
                ->where('journey.today_projection.mastery_included', false));
    }

    #[Test]
    public function activity_completion_is_explicitly_not_mastery_and_next_context_uses_incomplete_persisted_work(): void
    {
        $unit = $this->knowledgeUnit('KU-W02-C02-002');
        $completed = $this->practice($unit, 'PRACTICE-C02-010', 1, [
            'kind' => 'knowledge-check',
            'title_ar' => 'فحص مكتمل كنشاط',
        ]);
        $incomplete = $this->practice($unit, 'PRACTICE-C02-020', 1, [
            'kind' => 'knowledge-check',
            'title_ar' => 'فحص يحتاج متابعة',
        ]);

        PracticeAttempt::query()->create([
            'micro_practice_id' => $completed->id,
            'actor_id' => $this->owner->id,
            'case_id' => 'CASE-C02-010',
            'answer' => ['value' => 'correct-fixture'],
            'outcome' => 'correct',
            'rationale_valid' => true,
            'failure_class' => null,
        ]);
        PracticeAttempt::query()->create([
            'micro_practice_id' => $incomplete->id,
            'actor_id' => $this->owner->id,
            'case_id' => 'CASE-C02-020',
            'answer' => ['value' => 'incorrect-fixture'],
            'outcome' => 'incorrect',
            'rationale_valid' => false,
            'failure_class' => 'fixture-gap',
        ]);

        $this->actingAs($this->owner)->get("/knowledge/learn?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('journey.activity.practice_count', 2)
                ->where('journey.activity.started_practice_count', 2)
                ->where('journey.activity.completed_practice_count', 1)
                ->where('journey.activity.completion_is_mastery', false)
                ->where('journey.items.0.activity_completed', true)
                ->where('journey.items.0.completion_semantics', 'practice_activity_only_not_mastery')
                ->where('journey.items.1.activity_completed', false)
                ->where('journey.next.state', 'CONTINUE_PRACTICE')
                ->where('journey.next.practice_id', 'PRACTICE-C02-020')
                ->where('semantic_boundary.mastery', 'owned_by_progress_evidence')
                ->missing('mastery')
                ->missing('mastery_state')
                ->missing('journey.mastery'));

        $this->assertDatabaseCount('knowledge_units', 1);
        $this->assertDatabaseHas('knowledge_units', ['id' => $unit->id]);
    }

    #[Test]
    public function learn_selects_only_the_latest_canonical_practice_revision_for_activity(): void
    {
        $unit = $this->knowledgeUnit('KU-W02-C02-003');
        $old = $this->practice($unit, 'PRACTICE-C02-REV', 1, [
            'kind' => 'revision-fixture',
            'title_ar' => 'نسخة قديمة',
        ]);
        $latest = $this->practice($unit, 'PRACTICE-C02-REV', 2, [
            'kind' => 'revision-fixture',
            'title_ar' => 'النسخة القانونية الأحدث',
        ]);

        PracticeAttempt::query()->create([
            'micro_practice_id' => $old->id,
            'actor_id' => $this->owner->id,
            'case_id' => 'CASE-OLD',
            'answer' => ['value' => 'historical'],
            'outcome' => 'correct',
            'rationale_valid' => true,
            'failure_class' => null,
        ]);
        PracticeAttempt::query()->create([
            'micro_practice_id' => $latest->id,
            'actor_id' => $this->owner->id,
            'case_id' => 'CASE-LATEST',
            'answer' => ['value' => 'current'],
            'outcome' => 'incorrect',
            'rationale_valid' => false,
            'failure_class' => 'current-gap',
        ]);

        $this->actingAs($this->owner)->get("/knowledge/learn?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('journey.items', 1)
                ->where('journey.items.0.practice_id', 'PRACTICE-C02-REV')
                ->where('journey.items.0.revision', 2)
                ->where('journey.items.0.definition.title_ar', 'النسخة القانونية الأحدث')
                ->where('journey.items.0.attempt_count', 1)
                ->where('journey.items.0.recent_attempts.0.case_id', 'CASE-LATEST'));
    }

    #[Test]
    public function lab_preview_remains_contextual_and_run_preparation_requires_parent_integration(): void
    {
        $unit = $this->knowledgeUnit('KU-W02-C02-004');
        $this->practice($unit, 'PRACTICE-C02-LAB', 1, [
            'kind' => 'lab-preparation',
            'title_ar' => 'تهيئة مختبر محلي',
            'lab_reference' => [
                'id' => 'LAB-C02-SAFE-001',
                'title_ar' => 'مختبر محلي معزول',
                'title_en' => 'Isolated local lab',
                'summary_ar' => 'مرجع سياقي فقط إلى تعريف Lab المملوك للمحاكاة والمؤسسات.',
            ],
        ]);

        $this->actingAs($this->owner)->get("/knowledge/learn?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('journey.labs', 1)
                ->where('journey.labs.0.id', 'LAB-C02-SAFE-001')
                ->where('journey.labs.0.preview_state', 'REFERENCE_ONLY_FROM_LEARNING_DEFINITION')
                ->where('journey.labs.0.canonical_owner', 'simulation_enterprise')
                ->where('journey.labs.0.prepare_run_handoff.target_workspace', 'simulation_enterprise')
                ->where('journey.labs.0.prepare_run_handoff.target_area', 'labs')
                ->where('journey.labs.0.prepare_run_handoff.state', 'PARENT_INTEGRATION_REQUIRED')
                ->where('journey.labs.0.prepare_run_handoff.reason', 'W03_AUTHORITATIVE_HANDOFF_ROUTE_AND_PAYLOAD_UNAVAILABLE')
                ->where('journey.labs.0.prepare_run_handoff.executable', false)
                ->where('journey.labs.0.prepare_run_handoff.href', null));
    }

    #[Test]
    public function non_object_definition_is_normalized_before_lab_reference_is_read(): void
    {
        $unit = $this->knowledgeUnit('KU-W02-C02-NON-OBJECT');
        MicroPractice::query()->create([
            'practice_id' => 'PRACTICE-C02-NON-OBJECT',
            'revision' => 1,
            'capability_id' => 'CAP-W02-C02',
            'knowledge_unit_id' => $unit->id,
            'definition' => 'legacy-string-definition',
            'digest' => hash('sha256', 'PRACTICE-C02-NON-OBJECT-1'),
        ]);

        $this->actingAs($this->owner)->get("/knowledge/learn?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('journey.items', 1)
                ->where('journey.items.0.definition', [])
                ->has('journey.labs', 0));
    }

    #[Test]
    public function assessment_gap_is_truthful_and_does_not_relabel_practice_activity_as_assessment_results(): void
    {
        $unit = $this->knowledgeUnit('KU-W02-C02-005');
        $this->practice($unit, 'PRACTICE-C02-ASSESS-BOUNDARY', 1, [
            'kind' => 'knowledge-check',
            'title_ar' => 'ممارسة لا تتحول إلى Assessment',
        ]);

        $this->actingAs($this->owner)->get("/knowledge/learn?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('journey.assessments.state', 'NO_CANONICAL_ASSESSMENT_PERSISTENCE_IN_CURRENT_ARCHITECTURE')
                ->where('journey.assessments.integration_state', 'AUTHORITATIVE_ASSESSMENT_CONTRACT_REQUIRED')
                ->where('journey.assessments.semantic_owner', null)
                ->where('journey.assessments.fake_fallback_allowed', false)
                ->where('journey.assessments.executable', false)
                ->has('journey.assessments.definitions', 0)
                ->has('journey.assessments.results', 0)
                ->has('journey.items', 1));
    }

    private function knowledgeUnit(string $id): KnowledgeUnit
    {
        return KnowledgeUnit::query()->create([
            'id' => $id,
            'title_ar' => 'وحدة معرفة للتعلّم',
            'title_en' => 'Learning knowledge unit',
        ]);
    }

    /** @param array<string, mixed> $definition */
    private function practice(KnowledgeUnit $unit, string $practiceId, int $revision, array $definition): MicroPractice
    {
        return MicroPractice::query()->create([
            'practice_id' => $practiceId,
            'revision' => $revision,
            'capability_id' => 'CAP-W02-C02',
            'knowledge_unit_id' => $unit->id,
            'definition' => $definition,
            'digest' => hash('sha256', $practiceId.'-'.$revision),
        ]);
    }
}
