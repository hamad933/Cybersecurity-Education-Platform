<?php

namespace Tests\Feature\KnowledgeLearning;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Knowledge\Publication\LessonRevisionWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KnowledgeEditorCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private OwnerAccount $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = app(CreateOwner::class)->execute(
            'Test Owner',
            'owner@example.com',
            'Password123!',
            (string) Str::uuid7()
        );
    }

    private function knowledgeUnit(): KnowledgeUnit
    {
        return KnowledgeUnit::query()->create([
            'id' => 'KU-W02-TEST-CORRECTION',
            'title_ar' => 'اختبار تصحيح المحرر',
            'title_en' => 'Editor Correction Test',
        ]);
    }

    private function draft(string $knowledgeUnitId): LessonRevision
    {
        return app(LessonRevisionWorkflow::class)->createDraft(
            $knowledgeUnitId,
            [['type' => 'paragraph', 'body' => 'محتوى تجريبي', 'depth' => 0]],
            ['WIN-AUTH-901'],
            actorId: (string) $this->owner->id,
            authorityBaselineId: 'TEST-AUTHORITY',
        );
    }

    public function test_rejects_invalid_citation_and_empty_citations(): void
    {
        $unit = $this->knowledgeUnit();
        $revision = $this->draft($unit->id);

        $payload = [
            'lock_version' => 1,
            'blocks' => [
                ['type' => 'paragraph', 'body' => 'Test updated', 'depth' => 0],
            ],
            'citations' => ['INVALID-CITATION-123'],
        ];

        // Ensure backend validates citations properly (UE-004)
        // Controller catches InvalidArgumentException and returns 'revision' error
        $this->actingAs($this->owner)
            ->patch("/knowledge/library/revisions/{$revision->id}", $payload)
            ->assertSessionHasErrors(['revision']);

        // Cannot have empty citations: the controller validation rejects empty arrays
        $payload['citations'] = [];
        $this->actingAs($this->owner)
            ->patch("/knowledge/library/revisions/{$revision->id}", $payload)
            ->assertSessionHasErrors(['citations']);
            
        // Prove no revision mutation/lock-version advance on rejected domain citation
        $this->assertSame(1, $revision->fresh()->lock_version);
    }

    public function test_rejects_unauthorized_block_types(): void
    {
        $unit = $this->knowledgeUnit();
        $revision = $this->draft($unit->id);

        $payload = [
            'lock_version' => 1,
            'blocks' => [
                ['type' => 'sql', 'body' => 'SELECT *', 'depth' => 0], // Not allowed
            ],
            'citations' => ['WIN-AUTH-001'],
        ];

        // Ensure backend rejects invalid block types (UE-002)
        // The type validation is in the Controller request validation rules
        $this->actingAs($this->owner)
            ->patch("/knowledge/library/revisions/{$revision->id}", $payload)
            ->assertSessionHasErrors(['blocks.0.type']);
            
        $this->assertSame(1, $revision->fresh()->lock_version);
    }
}
