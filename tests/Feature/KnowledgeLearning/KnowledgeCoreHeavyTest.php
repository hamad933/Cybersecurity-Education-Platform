<?php

namespace Tests\Feature\KnowledgeLearning;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Knowledge\Content\LessonContentContract;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Knowledge\Publication\LessonRevisionWorkflow;
use App\Modules\SourceGovernance\Models\SourceClaim;
use App\Modules\SourceGovernance\Models\SourceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class KnowledgeCoreHeavyTest extends TestCase
{
    use RefreshDatabase;

    private OwnerAccount $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = app(CreateOwner::class)->execute(
            'W02 Knowledge Owner',
            'w02-heavy@example.test',
            'KnowledgeCore!Pass9',
            (string) Str::uuid7(),
        );
    }

    #[Test]
    public function library_and_editor_receive_the_authoritative_knowledge_content_contract(): void
    {
        $unit = $this->unit('KU-W02-CONTRACT');
        $draft = app(LessonRevisionWorkflow::class)->createDraft(
            $unit->id,
            [['type' => 'paragraph', 'body' => 'محتوى قانوني مشترك.', 'depth' => 0]],
            ['WEB-AUTH-901'],
            actorId: (string) $this->owner->id,
            authorityBaselineId: 'W02-AUTHORITY',
        );

        $this->actingAs($this->owner)->get("/knowledge?object={$unit->id}&revision={$draft->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('content_contract.version', LessonContentContract::VERSION)
                ->where('content_contract.canonical_owner', 'knowledge')
                ->where('content_contract.identity.canonical_object', 'knowledge_unit')
                ->where('content_contract.identity.content_record', 'lesson_revision')
                ->where('content_contract.constraints.max_depth', LessonContentContract::MAX_BLOCK_DEPTH)
                ->where('content_contract.citation.pattern', LessonContentContract::CITATION_PATTERN_JAVASCRIPT)
                ->where('active.canonical_ref.id', $unit->id)
                ->where('active.revision.id', (string) $draft->id)
                ->where('active.revision.blocks.0.depth', 0)
                ->where('active.revision_selection.state', 'REQUESTED_REVISION'));

        $controller = file_get_contents(app_path('Http/Controllers/KnowledgeLearning/KnowledgeLearningController.php'));
        $workflow = file_get_contents(app_path('Modules/Knowledge/Publication/LessonRevisionWorkflow.php'));
        self::assertIsString($controller);
        self::assertIsString($workflow);
        self::assertStringContainsString('requestValidationRules()', $controller);
        self::assertStringContainsString('validateAndNormalize', $workflow);
        self::assertStringNotContainsString("Rule::in(['heading'", $controller);
    }

    #[Test]
    public function learn_delivers_the_latest_published_revision_and_never_leaks_a_newer_draft(): void
    {
        $unit = $this->unit('KU-W02-LEARN-CONTINUITY');
        $source = SourceRecord::query()->create([
            'authority_class' => 'Technical Authority',
            'title' => 'W02 published lesson source',
            'exact_url' => 'https://example.test/w02-source',
            'relative_path' => null,
            'sha256' => str_repeat('b', 64),
            'review_status' => 'reviewed',
            'metadata' => [],
        ]);
        SourceClaim::query()->create([
            'source_record_id' => $source->id,
            'claim_id' => 'WEB-AUTH-902',
            'segment_ref' => 'section:published',
            'supported_scope' => 'Published lesson support.',
            'excluded_semantics' => 'No mastery or Evidence decision.',
            'assessment' => 'supported',
        ]);

        $published = $this->publishedRevision(
            $unit,
            1,
            [['type' => 'heading', 'body' => 'المحتوى المنشور المعتمد', 'depth' => 0]],
            ['WEB-AUTH-902'],
        );
        $draft = app(LessonRevisionWorkflow::class)->createDraft(
            $unit->id,
            [['type' => 'paragraph', 'body' => 'مسودة أحدث لا تظهر في Learn.', 'depth' => 0]],
            ['WEB-AUTH-902'],
            derivedFrom: (string) $published->id,
            actorId: (string) $this->owner->id,
            authorityBaselineId: 'W02-AUTHORITY',
        );

        self::assertSame(2, $draft->revision);

        $this->actingAs($this->owner)->get("/knowledge/learn?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('active.id', $unit->id)
                ->where('active.canonical_ref.id', $unit->id)
                ->missing('active.revision')
                ->where('lesson.availability', 'AVAILABLE_PUBLISHED_REVISION')
                ->where('lesson.selection_policy', 'latest_published_revision_only')
                ->where('lesson.revision.id', (string) $published->id)
                ->where('lesson.revision.state', 'published')
                ->where('lesson.revision.blocks.0.body', 'المحتوى المنشور المعتمد')
                ->where('lesson.revision.editable', false)
                ->where('context.sources.0.id', (string) $source->id)
                ->where('context.resume.server_persisted', false)
                ->where('context.resume.semantic_scope', 'reading_position_only_not_completion_or_mastery')
                ->where('semantic_boundary.completion', 'lesson_navigation_and_practice_activity_only')
                ->where('semantic_boundary.mastery', 'owned_by_progress_evidence'));
    }

    #[Test]
    public function learn_and_library_report_unavailable_or_invalid_selection_states_without_fabrication(): void
    {
        $unit = $this->unit('KU-W02-UNPUBLISHED');
        app(LessonRevisionWorkflow::class)->createDraft(
            $unit->id,
            [['type' => 'paragraph', 'body' => 'مسودة فقط.', 'depth' => 0]],
            ['WIN-AUTH-903'],
            actorId: (string) $this->owner->id,
        );

        $this->actingAs($this->owner)->get('/knowledge/learn?object=KU-DOES-NOT-EXIST')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selection.requested_id', 'KU-DOES-NOT-EXIST')
                ->where('selection.resolved_id', $unit->id)
                ->where('selection.state', 'REQUESTED_UNIT_NOT_FOUND_FALLBACK')
                ->where('lesson.availability', 'UNAVAILABLE_NO_PUBLISHED_REVISION')
                ->where('lesson.revision', null)
                ->where('context.prerequisites.state', 'not_listed')
                ->has('context.prerequisites.items', 0)
                ->has('context.objectives', 0));

        $this->actingAs($this->owner)->get("/knowledge?object={$unit->id}&revision=".Str::uuid())
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('active.revision_selection.state', 'REQUESTED_REVISION_NOT_FOUND_FALLBACK')
                ->where('active.revision_selection.selected_id', fn (mixed $id): bool => is_string($id) && $id !== ''));
    }

    #[Test]
    public function research_quality_exposes_the_required_persistent_reconciliation_owner_boundary(): void
    {
        $unit = $this->unit('KU-W02-RQ-BOUNDARY');
        app(LessonRevisionWorkflow::class)->createDraft(
            $unit->id,
            [['type' => 'paragraph', 'body' => 'محتوى جودة.', 'depth' => 0]],
            ['WIN-AUTH-904'],
            actorId: (string) $this->owner->id,
        );

        $this->actingAs($this->owner)->get("/knowledge/research-quality?object={$unit->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where(
                    'quality.analysis.reconciliation.persistence_boundary.state',
                    'RQ_PERSISTENT_RECONCILIATION_OWNER_REQUIRED',
                )
                ->where('quality.analysis.reconciliation.persistence_boundary.durable_write_authorized', false)
                ->where('quality.analysis.reconciliation.persistence_boundary.persistent_owner', null)
                ->where('quality.analysis.reconciliation.persistence_boundary.decision_record', null)
                ->where('semantic_boundary.evidence_review', 'owned_by_progress_evidence')
                ->missing('reconciliation_decision')
                ->missing('evidence_decision'));

        self::assertFalse(collect((new LessonContentContract)->manifest())->has('reconciliation_store'));
    }

    private function unit(string $id): KnowledgeUnit
    {
        return KnowledgeUnit::query()->create([
            'id' => $id,
            'title_ar' => 'وحدة معرفة W02',
            'title_en' => 'W02 Knowledge Unit',
        ]);
    }

    /**
     * @param  list<array{type: string, body: string, depth: int}>  $blocks
     * @param  list<string>  $citations
     */
    private function publishedRevision(KnowledgeUnit $unit, int $revision, array $blocks, array $citations): LessonRevision
    {
        $contract = app(LessonContentContract::class);
        $content = $contract->validateAndNormalize($blocks, $citations);

        return LessonRevision::query()->create([
            'knowledge_unit_id' => $unit->id,
            'revision' => $revision,
            'state' => 'published',
            'lock_version' => 4,
            'blocks' => $content['blocks'],
            'citations' => $content['citations'],
            'authority_baseline_id' => 'W02-AUTHORITY',
            'content_digest' => $contract->contentDigest($content['blocks'], $content['citations']),
            'review_decision' => 'APPROVED',
            'published_by' => $this->owner->id,
            'published_at' => now(),
        ]);
    }
}
