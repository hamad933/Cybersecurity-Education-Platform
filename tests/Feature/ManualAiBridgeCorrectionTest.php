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

    public function test_import_result_package_via_http_endpoint(): void
    {
        $this->seed();
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        $response = $this->actingAs($owner)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);
        
        $prompt = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $revision = PromptPackageRevision::query()->where('prompt_package_id', $prompt->id)->firstOrFail();

        $resultPayload = [
            'prompt_package_id' => (string) $prompt->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $revision->input_digest,
            'knowledge_unit_id' => 'KU-SEC-10',
            'proposed_blocks' => [['proposal_id' => 'prop_1', 'type' => 'paragraph', 'body' => 'محتوى تجريبي من الذكاء الاصطناعي اليدوي.']],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['Manual AI output'],
            'confidence' => 'high',
        ];

        $created = app(\App\Modules\Platform\Packages\SafePackageService::class)->create(
            'manual-ai-result',
            1,
            $actorId,
            [
                'prompt_package_id' => (string) $prompt->id,
                'prompt_revision' => 1,
                'input_digest' => (string) $revision->input_digest,
            ],
            ['result.json' => json_encode($resultPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ownerModule: 'MOD-AIB',
        );

        $blobStream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($created['blob_key']);
        $zipPath = tempnam(sys_get_temp_dir(), 'aib_pkg_').'.zip';
        file_put_contents($zipPath, stream_get_contents($blobStream));
        fclose($blobStream);

        $uploadedFile = new \Illuminate\Http\UploadedFile($zipPath, 'result.zip', 'application/zip', null, true);

        $response = $this->actingAs($owner)->post('/system/ai-bridge/results/import', [
            'package' => $uploadedFile,
        ]);

        @unlink($zipPath);

        $response->assertRedirect();

        $this->assertDatabaseHas('imported_ai_results', [
            'actor_id' => $actorId,
            'prompt_package_revision_id' => $revision->id,
            'status' => 'awaiting_human_review',
        ]);
    }

    public function test_raw_json_import_is_rejected(): void
    {
        $this->seed();
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        $response = $this->actingAs($owner)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);
        
        $prompt = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $revision = PromptPackageRevision::query()->where('prompt_package_id', $prompt->id)->firstOrFail();

        $result = [
            'prompt_package_id' => (string) $prompt->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $revision->input_digest,
            'knowledge_unit_id' => 'KU-SEC-10',
            'proposed_blocks' => [['proposal_id' => 'prop_1', 'type' => 'paragraph', 'body' => 'مقترح']],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['human review required'],
            'confidence' => 'high',
        ];

        $jsonContent = json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        
        $jsonPath = tempnam(sys_get_temp_dir(), 'aib_res_').'.json';
        file_put_contents($jsonPath, $jsonContent);

        $uploadedFile = new \Illuminate\Http\UploadedFile($jsonPath, 'result.json', 'application/json', null, true);

        $response = $this->actingAs($owner)->post('/system/ai-bridge/results/import', [
            'package' => $uploadedFile,
        ]);

        @unlink($jsonPath);

        $response->assertSessionHasErrors();
    }

    public function test_human_decision_edit_into_new_draft_creates_draft_lesson_and_updates_status(): void
    {
        $this->seed();
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        $response = $this->actingAs($owner)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);
        
        $prompt = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $revision = PromptPackageRevision::query()->where('prompt_package_id', $prompt->id)->firstOrFail();

        $resultPayload = [
            'prompt_package_id' => (string) $prompt->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $revision->input_digest,
            'knowledge_unit_id' => 'KU-SEC-10',
            'proposed_blocks' => [['proposal_id' => 'prop_1', 'type' => 'paragraph', 'body' => 'مقترح مسودة معتمد بعد مراجعة دقيقة.']],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['human review required'],
            'confidence' => 'high',
        ];

        $created = app(\App\Modules\Platform\Packages\SafePackageService::class)->create(
            'manual-ai-result',
            1,
            $actorId,
            [
                'prompt_package_id' => (string) $prompt->id,
                'prompt_revision' => 1,
                'input_digest' => (string) $revision->input_digest,
            ],
            ['result.json' => json_encode($resultPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ownerModule: 'MOD-AIB',
        );

        $blobStream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($created['blob_key']);
        $bridge = app(ManualAiBridgeService::class);
        
        $zipPath = tempnam(sys_get_temp_dir(), 'aib_pkg_') . '.zip';
        file_put_contents($zipPath, stream_get_contents($blobStream));
        fclose($blobStream);
        
        $fp = fopen($zipPath, 'rb');
        $imported = $bridge->importResult($fp, $actorId);
        fclose($fp);
        @unlink($zipPath);

        $response = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_1',
            'decision' => 'edit_into_new_draft',
            'rationale' => 'Approved by curriculum team after verification against baseline standard.',
        ]);

        $response->assertRedirect();

        $imported->refresh();
        $this->assertSame('accepted', $imported->status);

        $this->assertDatabaseHas('ai_proposal_decisions', [
            'imported_ai_result_id' => $imported->id,
            'proposal_id' => 'prop_1',
            'actor_id' => $actorId,
            'decision' => 'edit_into_new_draft',
            'rationale' => 'Approved by curriculum team after verification against baseline standard.',
        ]);
    }

    public function test_multi_proposal_decisions_state_machine_and_append_only_history(): void
    {
        $this->seed();
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        $response = $this->actingAs($owner)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);
        
        $prompt = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $revision = PromptPackageRevision::query()->where('prompt_package_id', $prompt->id)->firstOrFail();

        $resultPayload = [
            'prompt_package_id' => (string) $prompt->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $revision->input_digest,
            'knowledge_unit_id' => 'KU-SEC-10',
            'proposed_blocks' => [
                ['proposal_id' => 'prop_1', 'type' => 'paragraph', 'body' => 'مقترح الأول'],
                ['proposal_id' => 'prop_2', 'type' => 'paragraph', 'body' => 'مقترح الثاني']
            ],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['human review required'],
            'confidence' => 'high',
        ];

        $created = app(\App\Modules\Platform\Packages\SafePackageService::class)->create(
            'manual-ai-result',
            1,
            $actorId,
            [
                'prompt_package_id' => (string) $prompt->id,
                'prompt_revision' => 1,
                'input_digest' => (string) $revision->input_digest,
            ],
            ['result.json' => json_encode($resultPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ownerModule: 'MOD-AIB',
        );

        $blobStream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($created['blob_key']);
        $bridge = app(ManualAiBridgeService::class);
        
        $zipPath = tempnam(sys_get_temp_dir(), 'aib_pkg_') . '.zip';
        file_put_contents($zipPath, stream_get_contents($blobStream));
        fclose($blobStream);
        
        $fp = fopen($zipPath, 'rb');
        $imported = $bridge->importResult($fp, $actorId);
        fclose($fp);
        @unlink($zipPath);

        // a) defer -> later accept appends two immutable events and creates one draft only at accept
        $response1 = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_1',
            'decision' => 'defer',
            'rationale' => 'Need more time.',
        ]);
        $response1->assertRedirect();
        $imported->refresh();
        $this->assertSame('awaiting_human_review', $imported->status); // all defer/pending is awaiting
        
        $this->assertDatabaseHas('ai_proposal_decisions', [
            'proposal_id' => 'prop_1',
            'decision' => 'defer',
            'sequence' => 1,
            'lesson_revision_id' => null, // no draft created for defer
        ]);

        $response2 = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_1',
            'decision' => 'accept',
            'rationale' => 'Approved 1.',
        ]);
        $response2->assertRedirect();
        $imported->refresh();
        $this->assertSame('partially_accepted', $imported->status); // mixed accepted + pending is partially_accepted
        
        $this->assertDatabaseHas('ai_proposal_decisions', [
            'proposal_id' => 'prop_1',
            'decision' => 'accept',
            'sequence' => 2,
        ]);
        $acceptEvent = \App\Modules\ManualAiBridge\Models\AiProposalDecision::query()->where('proposal_id', 'prop_1')->where('sequence', 2)->firstOrFail();
        $this->assertNotNull($acceptEvent->lesson_revision_id); // one draft created at accept
        
        // (g) terminal proposal cannot be silently re-decided
        $response3 = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_1',
            'decision' => 'reject',
            'rationale' => 'Wait nevermind.',
        ]);
        $response3->assertSessionHasErrors();
        $this->assertDatabaseMissing('ai_proposal_decisions', [
            'proposal_id' => 'prop_1',
            'sequence' => 3,
        ]);

        // (b) request_evidence -> later reject appends history and creates zero drafts
        $response4 = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_2',
            'decision' => 'request_evidence',
            'rationale' => 'Need evidence.',
        ]);
        $response4->assertRedirect();
        $imported->refresh();
        $this->assertSame('partially_accepted', $imported->status); // still mixed accepted + nonterminal
        $this->assertDatabaseHas('ai_proposal_decisions', [
            'proposal_id' => 'prop_2',
            'decision' => 'request_evidence',
            'sequence' => 1,
            'lesson_revision_id' => null, // no draft created
        ]);

        // (h) event retry does not duplicate (exact same request_evidence again)
        $response5 = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_2',
            'decision' => 'request_evidence',
            'rationale' => 'Need evidence.',
        ]);
        $response5->assertRedirect();
        $this->assertDatabaseMissing('ai_proposal_decisions', [
            'proposal_id' => 'prop_2',
            'sequence' => 2, // No new sequence created for exact retry
        ]);

        $response6 = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_2',
            'decision' => 'reject',
            'rationale' => 'Rejecting it.',
        ]);
        $response6->assertRedirect();
        $imported->refresh();
        // (f) all terminal with >=1 accept/edit => accepted
        $this->assertSame('accepted', $imported->status); 
        $this->assertDatabaseHas('ai_proposal_decisions', [
            'proposal_id' => 'prop_2',
            'decision' => 'reject',
            'sequence' => 2,
            'lesson_revision_id' => null, // no draft created for reject
        ]);
    }
    
    public function test_all_terminal_reject_rolls_up_to_rejected(): void
    {
        $this->seed();
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        $response = $this->actingAs($owner)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);
        
        $prompt = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $revision = PromptPackageRevision::query()->where('prompt_package_id', $prompt->id)->firstOrFail();

        $resultPayload = [
            'prompt_package_id' => (string) $prompt->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $revision->input_digest,
            'knowledge_unit_id' => 'KU-SEC-10',
            'proposed_blocks' => [
                ['proposal_id' => 'prop_1', 'type' => 'paragraph', 'body' => 'مقترح الأول']
            ],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['human review required'],
            'confidence' => 'high',
        ];

        $created = app(\App\Modules\Platform\Packages\SafePackageService::class)->create(
            'manual-ai-result',
            1,
            $actorId,
            [
                'prompt_package_id' => (string) $prompt->id,
                'prompt_revision' => 1,
                'input_digest' => (string) $revision->input_digest,
            ],
            ['result.json' => json_encode($resultPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ownerModule: 'MOD-AIB',
        );

        $blobStream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($created['blob_key']);
        $bridge = app(ManualAiBridgeService::class);
        
        $zipPath = tempnam(sys_get_temp_dir(), 'aib_pkg_') . '.zip';
        file_put_contents($zipPath, stream_get_contents($blobStream));
        fclose($blobStream);
        
        $fp = fopen($zipPath, 'rb');
        $imported = $bridge->importResult($fp, $actorId);
        fclose($fp);
        @unlink($zipPath);

        $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_1',
            'decision' => 'reject',
            'rationale' => 'Rejected 1.',
        ]);
        
        $imported->refresh();
        $this->assertSame('rejected', $imported->status);
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
    public function test_import_result_rejects_if_actor_does_not_own_prompt_package(): void
    {
        $this->seed();
        $ownerA = $this->owner();
        $ownerB = $this->owner();
        $actorIdA = (string) $ownerA->getAuthIdentifier();
        $actorIdB = (string) $ownerB->getAuthIdentifier();

        $responseA = $this->actingAs($ownerA)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);
        
        $promptA = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $revisionA = PromptPackageRevision::query()->where('prompt_package_id', $promptA->id)->firstOrFail();

        $resultPayload = [
            'prompt_package_id' => (string) $promptA->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $revisionA->input_digest,
            'knowledge_unit_id' => 'KU-SEC-10',
            'proposed_blocks' => [['proposal_id' => 'prop_1', 'type' => 'paragraph', 'body' => 'محتوى تجريبي من الذكاء الاصطناعي اليدوي.']],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['Manual AI output'],
            'confidence' => 'high',
        ];

        // Owner B tries to import a result for Owner A's prompt
        $created = app(\App\Modules\Platform\Packages\SafePackageService::class)->create(
            'manual-ai-result',
            1,
            $actorIdB, // Package is physically valid and owned by B
            [
                'prompt_package_id' => (string) $promptA->id,
                'prompt_revision' => 1,
                'input_digest' => (string) $revisionA->input_digest,
            ],
            ['result.json' => json_encode($resultPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ownerModule: 'MOD-AIB',
        );

        $blobStream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($created['blob_key']);
        $zipPath = tempnam(sys_get_temp_dir(), 'aib_pkg_').'.zip';
        file_put_contents($zipPath, stream_get_contents($blobStream));
        fclose($blobStream);

        $uploadedFile = new \Illuminate\Http\UploadedFile($zipPath, 'result.zip', 'application/zip', null, true);

        $packagesBefore = \App\Modules\Platform\Packages\Models\PortablePackage::query()->count();
        $resultsBefore = ImportedAiResult::query()->count();
        $decisionsBefore = \App\Modules\ManualAiBridge\Models\AiProposalDecision::query()->count();
        $draftsBefore = \App\Modules\Knowledge\Models\LessonRevision::query()->count();

        $promptBefore = [
            'status' => $promptA->status,
            'purpose' => $promptA->purpose,
            'knowledge_unit_id' => $promptA->knowledge_unit_id,
            'actor_id' => $promptA->actor_id,
        ];
        
        $revisionBefore = [
            'revision' => $revisionA->revision,
            'input_digest' => $revisionA->input_digest,
            'prompt_package_id' => $revisionA->prompt_package_id,
        ];
        
        $revisionsCountBefore = PromptPackageRevision::query()->where('prompt_package_id', $promptA->id)->count();

        // Act as Owner B
        $response = $this->actingAs($ownerB)->post('/system/ai-bridge/results/import', [
            'package' => $uploadedFile,
        ]);

        @unlink($zipPath);

        // It should fail or be rejected because ownerB doesn't own promptA
        $response->assertSessionHasErrors();
        
        $this->assertDatabaseMissing('imported_ai_results', [
            'actor_id' => $actorIdB,
            'prompt_package_revision_id' => $revisionA->id,
        ]);
        
        $this->assertDatabaseMissing('audit_records', [
            'actor_identifier' => $actorIdB,
            'action' => 'manual_ai.result.imported',
            'correlation_id' => (string) $promptA->id,
        ]);
        
        $promptA->refresh();
        $this->assertNotSame('result_imported', $promptA->status);
        $this->assertSame($promptBefore['status'], $promptA->status);
        $this->assertSame($promptBefore['purpose'], $promptA->purpose);
        $this->assertSame($promptBefore['knowledge_unit_id'], $promptA->knowledge_unit_id);
        $this->assertSame($promptBefore['actor_id'], $promptA->actor_id);
        
        $revisionA->refresh();
        $this->assertSame($revisionBefore['revision'], $revisionA->revision);
        $this->assertSame($revisionBefore['input_digest'], $revisionA->input_digest);
        $this->assertSame($revisionBefore['prompt_package_id'], $revisionA->prompt_package_id);
        
        $revisionsCountAfter = PromptPackageRevision::query()->where('prompt_package_id', $promptA->id)->count();
        $this->assertSame($revisionsCountBefore, $revisionsCountAfter, 'No new revisions were created for the prompt package.');
        
        $this->assertDatabaseCount('portable_packages', $packagesBefore);
        $this->assertDatabaseCount('imported_ai_results', $resultsBefore);
        $this->assertDatabaseCount('ai_proposal_decisions', $decisionsBefore);
        $this->assertDatabaseCount('lesson_revisions', $draftsBefore);
    }
    
    public function test_exact_retry_idempotency_after_overall_terminal_closure(): void
    {
        $this->seed();
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        // 1. Setup imported result with ONE proposal (thus any terminal decision closes the overall result)
        $this->actingAs($owner)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);
        
        $prompt = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $revision = PromptPackageRevision::query()->where('prompt_package_id', $prompt->id)->firstOrFail();

        $resultPayload = [
            'prompt_package_id' => (string) $prompt->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $revision->input_digest,
            'knowledge_unit_id' => 'KU-SEC-10',
            'proposed_blocks' => [
                ['proposal_id' => 'prop_1', 'type' => 'paragraph', 'body' => 'مقترح مسودة معتمد بعد مراجعة دقيقة.']
            ],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['human review required'],
            'confidence' => 'high',
        ];

        $created = app(\App\Modules\Platform\Packages\SafePackageService::class)->create(
            'manual-ai-result',
            1,
            $actorId,
            [
                'prompt_package_id' => (string) $prompt->id,
                'prompt_revision' => 1,
                'input_digest' => (string) $revision->input_digest,
            ],
            ['result.json' => json_encode($resultPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ownerModule: 'MOD-AIB',
        );

        $blobStream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($created['blob_key']);
        $bridge = app(ManualAiBridgeService::class);
        $zipPath = tempnam(sys_get_temp_dir(), 'aib_pkg_') . '.zip';
        file_put_contents($zipPath, stream_get_contents($blobStream));
        fclose($blobStream);
        
        $fp = fopen($zipPath, 'rb');
        $imported = $bridge->importResult($fp, $actorId);
        fclose($fp);
        @unlink($zipPath);

        // 2. Decide terminal accept -> closes result
        $res1 = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_1',
            'decision' => 'accept',
            'rationale' => 'Approved 1.',
        ]);
        $res1->assertRedirect();
        
        $imported->refresh();
        $this->assertSame('accepted', $imported->status);
        
        $decisionsCount = \App\Modules\ManualAiBridge\Models\AiProposalDecision::query()->where('imported_ai_result_id', $imported->id)->count();
        $draftsCount = \App\Modules\Knowledge\Models\LessonRevision::query()->count();
        $this->assertSame(1, $decisionsCount);
        $this->assertSame(1, $draftsCount);
        
        // 3. Exact Retry MUST be idempotent and not create a new row or throw
        $res2 = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_1',
            'decision' => 'accept',
            'rationale' => 'Approved 1.', // Exact match
        ]);
        $res2->assertRedirect();
        
        $this->assertSame($decisionsCount, \App\Modules\ManualAiBridge\Models\AiProposalDecision::query()->where('imported_ai_result_id', $imported->id)->count());
        $this->assertSame($draftsCount, \App\Modules\Knowledge\Models\LessonRevision::query()->count());
        
        // 4. Mismatched Retry MUST throw
        $res3 = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported->id}/decide", [
            'proposal_id' => 'prop_1',
            'decision' => 'accept',
            'rationale' => 'Different rationale now.', // Mismatch match
        ]);
        $res3->assertSessionHasErrors();
        
        // 5. Test another proposal logic -> if there were two, one was rejected, and we retry the reject exactly
        $resultPayload2 = $resultPayload;
        $resultPayload2['proposed_blocks'] = [
            ['proposal_id' => 'prop_A', 'type' => 'paragraph', 'body' => '1'],
            ['proposal_id' => 'prop_B', 'type' => 'paragraph', 'body' => '2']
        ];
        $created2 = app(\App\Modules\Platform\Packages\SafePackageService::class)->create('manual-ai-result', 1, $actorId, [
                'prompt_package_id' => (string) $prompt->id, 'prompt_revision' => 1, 'input_digest' => (string) $revision->input_digest,
            ], ['result.json' => json_encode($resultPayload2, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)], ownerModule: 'MOD-AIB');
        
        $blobStream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($created2['blob_key']);
        $zipPath = tempnam(sys_get_temp_dir(), 'aib_pkg_') . '.zip';
        file_put_contents($zipPath, stream_get_contents($blobStream));
        fclose($blobStream);
        
        $fp = fopen($zipPath, 'rb');
        $imported2 = $bridge->importResult($fp, $actorId);
        fclose($fp);
        @unlink($zipPath);

        $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported2->id}/decide", ['proposal_id' => 'prop_A', 'decision' => 'reject', 'rationale' => 'Rej A']);
        $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported2->id}/decide", ['proposal_id' => 'prop_B', 'decision' => 'reject', 'rationale' => 'Rej B']);
        
        $imported2->refresh();
        $this->assertSame('rejected', $imported2->status); // fully closed
        
        // Retry exact on A should succeed idempotently
        $resRejRetry = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported2->id}/decide", ['proposal_id' => 'prop_A', 'decision' => 'reject', 'rationale' => 'Rej A']);
        $resRejRetry->assertRedirect();
        
        // Mismatch should fail
        $resRejFail = $this->actingAs($owner)->post("/system/ai-bridge/results/{$imported2->id}/decide", ['proposal_id' => 'prop_A', 'decision' => 'accept', 'rationale' => 'Changed my mind']);
        $resRejFail->assertSessionHasErrors();
    }

    public function test_import_result_rejects_if_ai_claims_mismatch_verified_scope(): void
    {
        $this->seed();
        $owner = $this->owner();
        $actorId = (string) $owner->getAuthIdentifier();

        $response = $this->actingAs($owner)->post('/system/ai-bridge/prompts/export', [
            'purpose' => 'Correct KU-SEC-10',
            'knowledge_unit_id' => 'KU-SEC-10',
            'instruction' => 'Refine paragraph on access control.',
        ]);
        
        $prompt = PromptPackage::query()->where('purpose', 'Correct KU-SEC-10')->firstOrFail();
        $revision = PromptPackageRevision::query()->where('prompt_package_id', $prompt->id)->firstOrFail();

        $resultPayload = [
            'prompt_package_id' => (string) \Illuminate\Support\Str::uuid7(), // MALICIOUS CLAIM
            'prompt_revision' => 1,
            'input_digest' => (string) $revision->input_digest,
            'knowledge_unit_id' => 'KU-SEC-10',
            'proposed_blocks' => [['proposal_id' => 'prop_1', 'type' => 'paragraph', 'body' => 'محتوى تجريبي من الذكاء الاصطناعي اليدوي.']],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['Manual AI output'],
            'confidence' => 'high',
        ];

        $created = app(\App\Modules\Platform\Packages\SafePackageService::class)->create(
            'manual-ai-result',
            1,
            $actorId,
            [
                'prompt_package_id' => (string) $prompt->id,
                'prompt_revision' => 1,
                'input_digest' => (string) $revision->input_digest,
            ],
            ['result.json' => json_encode($resultPayload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ownerModule: 'MOD-AIB',
        );

        $blobStream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($created['blob_key']);
        $zipPath = tempnam(sys_get_temp_dir(), 'aib_pkg_').'.zip';
        file_put_contents($zipPath, stream_get_contents($blobStream));
        fclose($blobStream);

        $uploadedFile = new \Illuminate\Http\UploadedFile($zipPath, 'result.zip', 'application/zip', null, true);

        $response = $this->actingAs($owner)->post('/system/ai-bridge/results/import', [
            'package' => $uploadedFile,
        ]);

        @unlink($zipPath);

        $response->assertSessionHasErrors();
    }
}