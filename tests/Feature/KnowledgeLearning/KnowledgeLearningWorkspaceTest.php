<?php

namespace Tests\Feature\KnowledgeLearning;

use App\Modules\Curriculum\Models\CurriculumPlacement;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Knowledge\Publication\LessonRevisionWorkflow;
use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Learning\Models\PracticeAttempt;
use App\Modules\SourceGovernance\Models\SourceClaim;
use App\Modules\SourceGovernance\Models\SourceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class KnowledgeLearningWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private OwnerAccount $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = app(CreateOwner::class)->execute(
            'Knowledge Owner',
            'knowledge-owner@example.test',
            'KnowledgeReady!Pass9',
            (string) Str::uuid7(),
        );
    }

    #[Test]
    public function knowledge_route_family_is_authenticated_and_uses_stable_workspace_routes(): void
    {
        foreach (['/knowledge', '/knowledge/learn', '/knowledge/visualize', '/knowledge/research-quality'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $this->actingAs($this->owner)->get('/knowledge')->assertOk()->assertInertia(fn (Assert $page) => $page->component('KnowledgeLearning/Library'));
        $this->actingAs($this->owner)->get('/knowledge/learn')->assertOk()->assertInertia(fn (Assert $page) => $page->component('KnowledgeLearning/Learn'));
        $this->actingAs($this->owner)->get('/knowledge/visualize')->assertOk()->assertInertia(fn (Assert $page) => $page->component('KnowledgeLearning/Visualize'));
        $this->actingAs($this->owner)->get('/knowledge/research-quality')->assertOk()->assertInertia(fn (Assert $page) => $page->component('KnowledgeLearning/ResearchQuality'));

        $this->assertSame('/knowledge', route('cep.knowledge.library', absolute: false));
        $this->assertSame('/knowledge/learn', route('cep.knowledge.learn', absolute: false));
        $this->assertSame('/knowledge/visualize', route('cep.knowledge.visualize', absolute: false));
        $this->assertSame('/knowledge/research-quality', route('cep.knowledge.research-quality', absolute: false));
    }

    #[Test]
    public function library_and_learn_project_the_same_persisted_canonical_object_without_duplication(): void
    {
        $unit = $this->knowledgeUnit();
        $this->draft($unit->id);

        $this->actingAs($this->owner)->get("/knowledge?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('KnowledgeLearning/Library')
                ->where('active.id', $unit->id)
                ->where('active.title_ar', 'اختبار معرفة قانوني')
                ->where('active.revision.state', 'draft'));

        $this->actingAs($this->owner)->get("/knowledge/learn?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('KnowledgeLearning/Learn')
                ->where('active.id', $unit->id)
                ->where('semantic_boundary.progress', 'journey_activity_context')
                ->where('semantic_boundary.mastery', 'owned_by_progress_evidence'));

        $this->assertDatabaseCount('knowledge_units', 1);
        $this->assertDatabaseHas('knowledge_units', ['id' => $unit->id]);
    }

    #[Test]
    public function library_reads_real_revisions_and_updates_only_drafts_with_optimistic_locking(): void
    {
        $unit = $this->knowledgeUnit();
        $draft = $this->draft($unit->id);

        $this->actingAs($this->owner)->patch("/knowledge/library/revisions/{$draft->id}", [
            'lock_version' => 1,
            'blocks' => [['type' => 'paragraph', 'body' => 'محتوى عربي محدث مع SQL Injection كمصطلح تقني.']],
            'citations' => ['WIN-AUTH-901'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('lesson_revisions', [
            'id' => $draft->id,
            'knowledge_unit_id' => $unit->id,
            'state' => 'draft',
            'lock_version' => 2,
        ]);
        $this->assertDatabaseHas('audit_records', [
            'action' => 'lesson.draft.updated',
            'actor_identifier' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)->patch("/knowledge/library/revisions/{$draft->id}", [
            'lock_version' => 1,
            'blocks' => [['type' => 'paragraph', 'body' => 'تعديل متعارض.']],
            'citations' => ['WIN-AUTH-901'],
        ])->assertSessionHasErrors('revision');

        $this->assertDatabaseHas('lesson_revisions', ['id' => $draft->id, 'lock_version' => 2]);
    }

    #[Test]
    public function published_history_is_preserved_when_restored_as_a_new_draft(): void
    {
        $unit = $this->knowledgeUnit();
        $published = LessonRevision::query()->create([
            'knowledge_unit_id' => $unit->id,
            'revision' => 1,
            'state' => 'published',
            'lock_version' => 4,
            'blocks' => [['type' => 'paragraph', 'body' => 'نسخة منشورة لا تعدل في مكانها.']],
            'citations' => ['WIN-AUTH-901'],
            'authority_baseline_id' => 'TEST-AUTHORITY',
            'content_digest' => hash('sha256', 'published'),
            'published_by' => $this->owner->id,
            'published_at' => now(),
        ]);

        $this->actingAs($this->owner)->post("/knowledge/library/revisions/{$published->id}/restore")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $published->refresh();
        $this->assertSame('published', $published->state);
        $this->assertDatabaseHas('lesson_revisions', [
            'knowledge_unit_id' => $unit->id,
            'revision' => 2,
            'state' => 'draft',
            'derived_from_revision_id' => $published->id,
        ]);
        $this->assertDatabaseCount('lesson_revisions', 2);
    }

    #[Test]
    public function learn_uses_real_practice_activity_without_loading_mastery_truth(): void
    {
        $unit = $this->knowledgeUnit();
        MicroPractice::query()->create([
            'practice_id' => 'PRACTICE-W02-001',
            'revision' => 1,
            'capability_id' => 'CAP-W02-001',
            'knowledge_unit_id' => $unit->id,
            'definition' => ['kind' => 'synthetic-test-fixture-v1'],
            'digest' => hash('sha256', 'practice-v1'),
        ]);
        $practice = MicroPractice::query()->create([
            'practice_id' => 'PRACTICE-W02-001',
            'revision' => 2,
            'capability_id' => 'CAP-W02-001',
            'knowledge_unit_id' => $unit->id,
            'definition' => ['kind' => 'synthetic-test-fixture-v2'],
            'digest' => hash('sha256', 'practice-v2'),
        ]);
        PracticeAttempt::query()->create([
            'micro_practice_id' => $practice->id,
            'actor_id' => $this->owner->id,
            'case_id' => 'CASE-W02-001',
            'answer' => ['value' => 'fixture'],
            'outcome' => 'correct',
            'rationale_valid' => true,
            'failure_class' => null,
        ]);

        $this->actingAs($this->owner)->get("/knowledge/learn?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('journey.activity.attempt_count', 1)
                ->where('journey.activity.completed_practice_count', 1)
                ->has('journey.items', 1)
                ->where('journey.items.0.practice_id', 'PRACTICE-W02-001')
                ->where('journey.items.0.revision', 2)
                ->missing('mastery')
                ->missing('mastery_state'));
    }

    #[Test]
    public function visualize_reads_persisted_curriculum_relationships_and_does_not_claim_an_unsaved_map(): void
    {
        $unit = $this->knowledgeUnit();
        CurriculumPlacement::query()->create([
            'capability_id' => 'CAP-W02-VIS',
            'knowledge_unit_id' => $unit->id,
            'revision' => 3,
            'lifecycle' => ['state' => 'active'],
        ]);

        $this->actingAs($this->owner)->get("/knowledge/visualize?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('KnowledgeLearning/Visualize')
                ->where('map.saved', false)
                ->where('map.state', 'NO_PERSISTED_MAP_MODEL_IN_WAVE1')
                ->where('graph.source', 'curriculum_placements')
                ->has('graph.nodes', 2)
                ->has('graph.edges', 1)
                ->where('graph.edges.0.type', 'canonical_placement')
                ->where('graph.edges.0.revision', 3));
    }

    #[Test]
    public function research_quality_reads_source_claim_truth_without_evidence_review_or_mastery_semantics(): void
    {
        $unit = $this->knowledgeUnit();
        $this->draft($unit->id);
        $source = SourceRecord::query()->create([
            'authority_class' => 'Technical Authority',
            'title' => 'Synthetic W02 Technical Authority',
            'exact_url' => 'https://example.test/authority',
            'relative_path' => null,
            'sha256' => str_repeat('a', 64),
            'review_status' => 'reviewed',
            'metadata' => ['fixture' => true],
        ]);
        SourceClaim::query()->create([
            'source_record_id' => $source->id,
            'claim_id' => 'WIN-AUTH-901',
            'segment_ref' => 'section-1',
            'supported_scope' => 'Synthetic supported scope for W02 feature testing.',
            'excluded_semantics' => 'Does not establish Evidence Review or Mastery.',
            'assessment' => 'supported',
        ]);

        $this->actingAs($this->owner)->get("/knowledge/research-quality?object={$unit->id}&source={$source->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('KnowledgeLearning/ResearchQuality')
                ->where('quality.review_semantics', 'knowledge_quality')
                ->where('quality.active_source.id', (string) $source->id)
                ->where('quality.active_source.claims.0.claim_id', 'WIN-AUTH-901')
                ->where('semantic_boundary.evidence_review', 'owned_by_progress_evidence')
                ->where('semantic_boundary.mastery_judgment', 'owned_by_progress_evidence')
                ->missing('evidence_decision')
                ->missing('mastery_state'));

        $controller = file_get_contents(app_path('Http/Controllers/KnowledgeLearning/KnowledgeLearningController.php'));
        $workspace = file_get_contents(app_path('Application/KnowledgeLearning/KnowledgeLearningWorkspace.php'));
        $this->assertIsString($controller);
        $this->assertIsString($workspace);
        $this->assertStringNotContainsString('Modules\\Evidence', $controller.$workspace);
        $this->assertStringNotContainsString('MasteryState', $controller.$workspace);
    }

    #[Test]
    public function representative_pages_define_arabic_rtl_and_explicit_ltr_technical_islands_without_legacy_target_routes(): void
    {
        foreach (['Library.vue', 'Learn.vue', 'Visualize.vue', 'ResearchQuality.vue'] as $page) {
            $source = file_get_contents(resource_path("js/pages/KnowledgeLearning/{$page}"));
            $this->assertIsString($source);
            $this->assertStringContainsString('dir="rtl"', $source);
            $this->assertStringContainsString('dir="ltr"', $source);
            $this->assertStringNotContainsString('/vs001', $source);
            $this->assertStringNotContainsString('/vs002', $source);
        }

        $routeFile = file_get_contents(base_path('routes/workspaces/knowledge-learning.php'));
        $this->assertIsString($routeFile);
        $this->assertStringContainsString("->prefix('knowledge')", $routeFile);
        $this->assertStringNotContainsString("prefix('vs001')", $routeFile);
        $this->assertStringNotContainsString("prefix('vs002')", $routeFile);
    }

    private function knowledgeUnit(): KnowledgeUnit
    {
        return KnowledgeUnit::query()->create([
            'id' => 'KU-W02-TEST',
            'title_ar' => 'اختبار معرفة قانوني',
            'title_en' => 'Canonical Knowledge Test',
        ]);
    }

    private function draft(string $knowledgeUnitId): LessonRevision
    {
        return app(LessonRevisionWorkflow::class)->createDraft(
            $knowledgeUnitId,
            [['type' => 'paragraph', 'body' => 'محتوى اختبار حقيقي من قاعدة البيانات.']],
            ['WIN-AUTH-901'],
            actorId: (string) $this->owner->id,
            authorityBaselineId: 'TEST-AUTHORITY',
        );
    }
}
