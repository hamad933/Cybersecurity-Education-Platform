<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\ManualAiBridge\Application\ManualAiBridgeService;
use App\Modules\ManualAiBridge\Models\ImportedAiResult;
use App\Modules\ManualAiBridge\Models\PromptPackage;
use App\Modules\ManualAiBridge\Models\PromptPackageRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class ManualAiBridgeCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_export_prompt_action_creates_package_and_revision(): void
    {
        $this->seed();
        $owner = $this->owner();

        $response = $this->actingAs($owner)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('prompt_packages', [
            'actor_id' => (string) $owner->getAuthIdentifier(),
            'purpose' => 'Correct KU-SEC-10',
            'status' => 'exported',
        ]);

        $prompt = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $this->assertDatabaseHas('prompt_package_revisions', [
            'prompt_package_id' => $prompt->id,
            'revision' => 1,
        ]);
    }

    public function test_import_raw_json_result_package_via_http_endpoint(): void
    {
        $this->seed();
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        $bridge = app(ManualAiBridgeService::class);
        $export = $bridge->exportPrompt($actorId, 'Draft revision', ['knowledge_unit_id' => 'KU-SEC-20'], ['instruction' => 'Draft instruction.']);

        $resultPayload = [
            'prompt_package_id' => (string) $export['prompt']->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $export['revision']->input_digest,
            'knowledge_unit_id' => 'KU-SEC-20',
            'proposed_blocks' => [['type' => 'paragraph', 'body' => 'محتوى تجريبي من الذكاء الاصطناعي اليدوي.']],
            'citation_claim_ids' => ['CLAIM-001'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['Manual AI output'],
            'confidence' => 'high',
        ];

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'result.json',
            json_encode($resultPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );

        $response = $this->actingAs($owner)->post('/system/ai-bridge/results/import', [
            'package' => $uploadedFile,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('imported_ai_results', [
            'actor_id' => $actorId,
            'prompt_package_revision_id' => $export['revision']->id,
            'status' => 'pending_review',
        ]);
    }

    public function test_human_decision_accept_as_draft_creates_draft_lesson_and_updates_status(): void
    {
        $this->seed();
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        $bridge = app(ManualAiBridgeService::class);
        $export = $bridge->exportPrompt($actorId, 'Draft for decision', ['knowledge_unit_id' => 'KU-SEC-30'], ['instruction' => 'Draft.']);

        $result = [
            'prompt_package_id' => (string) $export['prompt']->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $export['revision']->input_digest,
            'knowledge_unit_id' => 'KU-SEC-30',
            'proposed_blocks' => [['type' => 'paragraph', 'body' => 'مقترح مسودة معتمد بعد مراجعة دقيقة.']],
            'citation_claim_ids' => ['CLAIM-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['human review required'],
            'confidence' => 'high',
        ];

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        rewind($stream);

        try {
            $imported = $bridge->importResult($stream, $actorId);
        } finally {
            fclose($stream);
        }

        $response = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'decision' => 'ACCEPT_AS_DRAFT',
            'rationale' => 'Approved by curriculum team after verification against baseline standard.',
        ]);

        $response->assertRedirect();

        $imported->refresh();
        $this->assertSame('accepted', $imported->status);

        $this->assertDatabaseHas('ai_proposal_decisions', [
            'imported_ai_result_id' => $imported->id,
            'actor_id' => $actorId,
            'decision' => 'ACCEPT_AS_DRAFT',
            'rationale' => 'Approved by curriculum team after verification against baseline standard.',
        ]);
    }

    private function owner()
    {
        return app(CreateOwner::class)->execute(
            'Manual AI Owner',
            'manual-ai-owner-'.Str::lower(Str::random(8)).'@example.test',
            'VeryStrong!Pass9',
            (string) Str::uuid7(),
        );
    }
}
