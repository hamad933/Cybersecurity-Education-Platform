<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\ManualAiBridge\Application\ManualAiBridgeService;
use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\Platform\Packages\SafePackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

final class ManualAiBridgeCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_raw_json_result_import_validates_provenance_and_creates_draft(): void
    {
        $this->seed();
        $owner = app(CreateOwner::class)->execute('Owner', 'owner-corr@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $bridge = app(ManualAiBridgeService::class);
        $export = $bridge->exportPrompt((string) $owner->id, 'Draft lesson improvement', ['knowledge_unit_id' => 'KU-AD-02'], ['instruction' => 'Refine section 1.']);

        $result = [
            'prompt_package_id' => (string) $export['prompt']->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $export['revision']->input_digest,
            'knowledge_unit_id' => 'KU-AD-02',
            'proposed_blocks' => [['type' => 'paragraph', 'body' => 'مقترح مسودة محكم يخضع للتدقيق البشري.']],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['manual test result'],
            'confidence' => 'high',
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
        $this->assertDatabaseHas('imported_ai_results', [
            'id' => $import->id,
            'actor_id' => (string) $owner->id,
            'status' => 'pending_review',
        ]);
        $this->assertDatabaseHas('portable_packages', [
            'id' => $import->portable_package_id,
            'package_type' => 'manual-ai-result',
            'owner_module' => 'MOD-AIB',
        ]);

        $decision = $bridge->decide((string) $import->id, (string) $owner->id, 'ACCEPT_AS_DRAFT', 'Human reviewed and approved as a draft.');
        $this->assertSame('ACCEPT_AS_DRAFT', $decision->decision);
        $this->assertNotNull($decision->lesson_revision_id);
        $this->assertDatabaseHas('lesson_revisions', ['id' => $decision->lesson_revision_id, 'state' => 'draft']);
        $this->assertDatabaseMissing('lesson_revisions', ['id' => $decision->lesson_revision_id, 'state' => 'published']);

        $import->refresh();
        $this->assertSame('accepted', $import->status);
    }

    public function test_safepackage_zip_result_import_is_supported_and_validated(): void
    {
        $this->seed();
        $owner = app(CreateOwner::class)->execute('Owner 2', 'owner-zip@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $bridge = app(ManualAiBridgeService::class);
        $export = $bridge->exportPrompt((string) $owner->id, 'Draft lesson for ZIP package', ['knowledge_unit_id' => 'KU-AD-02'], ['instruction' => 'Refine section 2.']);

        $result = [
            'knowledge_unit_id' => 'KU-AD-02',
            'proposed_blocks' => [['type' => 'paragraph', 'body' => 'محتوى حزمة آمنة.']],
            'citation_claim_ids' => ['WIN-AUTH-002'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['zip package test'],
            'confidence' => 'high',
        ];

        $created = app(SafePackageService::class)->create(
            'manual-ai-result',
            1,
            (string) $owner->id,
            [
                'prompt_package_id' => (string) $export['prompt']->id,
                'prompt_revision' => 1,
                'input_digest' => (string) $export['revision']->input_digest,
            ],
            ['result.json' => json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
            ownerModule: 'MOD-AIB',
        );

        $stream = app(BlobStore::class)->readStream($created['blob_key']);
        try {
            $import = $bridge->importResult($stream, (string) $owner->id);
        } finally {
            fclose($stream);
        }

        $this->assertSame('pending_review', $import->status);

        $decision = $bridge->decide((string) $import->id, (string) $owner->id, 'REJECT', 'Rejected by reviewer due to quality bounds.');
        $this->assertSame('REJECT', $decision->decision);
        $this->assertNull($decision->lesson_revision_id);

        $import->refresh();
        $this->assertSame('rejected', $import->status);
    }

    public function test_tampered_input_digest_in_json_is_rejected(): void
    {
        $this->seed();
        $owner = app(CreateOwner::class)->execute('Owner 3', 'owner-tamper@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $bridge = app(ManualAiBridgeService::class);
        $export = $bridge->exportPrompt((string) $owner->id, 'Draft prompt for tamper test', ['knowledge_unit_id' => 'KU-AD-02'], ['instruction' => 'Test tamper.']);

        $result = [
            'prompt_package_id' => (string) $export['prompt']->id,
            'prompt_revision' => 1,
            'input_digest' => str_repeat('e', 64),
            'knowledge_unit_id' => 'KU-AD-02',
            'proposed_blocks' => [['type' => 'paragraph', 'body' => 'Content.']],
            'citation_claim_ids' => ['WIN-AUTH-003'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['tamper test'],
            'confidence' => 'low',
        ];

        $json = json_encode($result, JSON_THROW_ON_ERROR);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('AI result input digest does not match the exported prompt.');

        try {
            $bridge->importResult($stream, (string) $owner->id);
        } finally {
            fclose($stream);
        }
    }

    public function test_decisions_are_immutable_and_cannot_be_decided_twice(): void
    {
        $this->seed();
        $owner = app(CreateOwner::class)->execute('Owner 4', 'owner-imm@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $bridge = app(ManualAiBridgeService::class);
        $export = $bridge->exportPrompt((string) $owner->id, 'Draft prompt for immutable test', ['knowledge_unit_id' => 'KU-AD-02'], ['instruction' => 'Test immutability.']);

        $result = [
            'prompt_package_id' => (string) $export['prompt']->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $export['revision']->input_digest,
            'knowledge_unit_id' => 'KU-AD-02',
            'proposed_blocks' => [['type' => 'paragraph', 'body' => 'Content.']],
            'citation_claim_ids' => ['WIN-AUTH-004'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['immutability test'],
            'confidence' => 'high',
        ];

        $json = json_encode($result, JSON_THROW_ON_ERROR);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        try {
            $import = $bridge->importResult($stream, (string) $owner->id);
        } finally {
            fclose($stream);
        }

        $bridge->decide((string) $import->id, (string) $owner->id, 'ACCEPT_AS_DRAFT', 'First decision.');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('AI result already has a final decision.');

        $bridge->decide((string) $import->id, (string) $owner->id, 'REJECT', 'Second decision attempt.');
    }

    public function test_import_result_is_idempotent_for_identical_payload(): void
    {
        $this->seed();
        $owner = app(CreateOwner::class)->execute('Owner 5', 'owner-idem@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $bridge = app(ManualAiBridgeService::class);
        $export = $bridge->exportPrompt((string) $owner->id, 'Draft prompt for idempotency test', ['knowledge_unit_id' => 'KU-AD-02'], ['instruction' => 'Test idempotency.']);

        $result = [
            'prompt_package_id' => (string) $export['prompt']->id,
            'prompt_revision' => 1,
            'input_digest' => (string) $export['revision']->input_digest,
            'knowledge_unit_id' => 'KU-AD-02',
            'proposed_blocks' => [['type' => 'paragraph', 'body' => 'Content.']],
            'citation_claim_ids' => ['WIN-AUTH-004'],
            'derived_from_revision_id' => null,
            'authority_baseline_id' => config('vs001.authority_baseline_id'),
            'limitations' => ['idempotency test'],
            'confidence' => 'high',
        ];

        $json = json_encode($result, JSON_THROW_ON_ERROR);

        $stream1 = fopen('php://memory', 'r+');
        fwrite($stream1, $json);
        rewind($stream1);
        $import1 = $bridge->importResult($stream1, (string) $owner->id);
        fclose($stream1);

        $stream2 = fopen('php://memory', 'r+');
        fwrite($stream2, $json);
        rewind($stream2);
        $import2 = $bridge->importResult($stream2, (string) $owner->id);
        fclose($stream2);

        $this->assertSame((string) $import1->id, (string) $import2->id);
    }
}
