<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\ManualAiBridge\Application\ManualAiBridgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ManualAiBridgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_ai_result_is_provenance_bound_and_requires_human_decision_before_draft(): void
    {
        $this->seed();
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $bridge = app(ManualAiBridgeService::class);
        $export = $bridge->exportPrompt((string) $owner->id, 'Draft a bounded lesson improvement', ['knowledge_unit_id' => 'KU-AD-02'], ['instruction' => 'Improve one paragraph.']);

        $result = [
            'prompt_package_id' => (string) $export['prompt']->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $export['revision']->input_digest,
            'knowledge_unit_id' => 'KU-AD-02',
            'proposed_blocks' => [['type' => 'paragraph', 'body' => 'مقترح يحتاج مراجعة بشرية قبل النشر.']],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['manual result', 'not authoritative'],
            'confidence' => 'medium',
        ];
        
        $json = json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        try {
            $import = $bridge->importResult($stream, (string) $owner->id);
        } finally {
            fclose($stream);
        }
        
        $this->assertSame('pending_review', $import->status);
        $this->assertDatabaseCount('ai_proposal_decisions', 0);

        $decision = $bridge->decide((string) $import->id, (string) $owner->id, 'ACCEPT_AS_DRAFT', 'Reviewed and accepted only as a new draft.');
        $this->assertNotNull($decision->lesson_revision_id);
        $this->assertDatabaseHas('lesson_revisions', ['id' => $decision->lesson_revision_id, 'state' => 'draft']);
        $this->assertDatabaseMissing('lesson_revisions', ['id' => $decision->lesson_revision_id, 'state' => 'published']);
    }
}
