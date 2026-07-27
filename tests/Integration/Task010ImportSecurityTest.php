<?php

namespace Tests\Integration;

use App\Modules\Evidence\Application\ExternalEvidenceImportService;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Platform\Audit\AuditChainVerifier;
use App\Modules\Platform\Packages\SafePackageService;
use App\Modules\SourceGovernance\Application\SafeSourceImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

final class Task010ImportSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_safe_source_import_accepts_utf8_text_and_records_actor_bound_audit(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $file = UploadedFile::fake()->createWithContent('authority.md', "# Authority\nReviewed content.\n");
        $import = app(SafeSourceImportService::class)->import($file, (string) $owner->id);

        $this->assertSame('accepted', $import->status);
        $this->assertDatabaseHas('source_records', ['id' => $import->source_record_id, 'review_status' => 'unreviewed']);
        $this->assertDatabaseHas('audit_records', ['action' => 'source.import.accepted', 'actor_identifier' => (string) $owner->id]);
        $this->assertTrue(app(AuditChainVerifier::class)->verify()['valid']);
    }

    public function test_external_evidence_is_actor_bound_and_simulated_origin_is_rejected(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $actorId = (string) $owner->id;
        $validPayload = [
            'origin' => 'REAL_LAB',
            'capability_id' => 'CAP-D09-01-02',
            'knowledge_unit_id' => 'KU-D09-002',
            'claims' => [['statement' => 'Bounded evidence claim']],
            'limitations' => ['Local lab only'],
        ];
        $valid = app(SafePackageService::class)->create(
            'external-evidence',
            1,
            $actorId,
            ['source' => 'task010-test'],
            ['evidence.json' => json_encode($validPayload, JSON_THROW_ON_ERROR)],
            ownerModule: 'MOD-EVD',
        );
        $stream = fopen(storage_path('app/private/'.$valid['blob_key']), 'rb');
        $this->assertIsResource($stream);
        try {
            $record = app(ExternalEvidenceImportService::class)->import($stream, $actorId);
        } finally {
            fclose($stream);
        }
        $this->assertSame('pending_review', $record->status);
        $this->assertSame('REAL_LAB', $record->origin);
        $this->assertSame($actorId, (string) $record->actor_id);

        $invalidPayload = [...$validPayload, 'origin' => 'SIMULATED'];
        $invalid = app(SafePackageService::class)->create(
            'external-evidence',
            1,
            $actorId,
            ['source' => 'task010-test'],
            ['evidence.json' => json_encode($invalidPayload, JSON_THROW_ON_ERROR)],
            ownerModule: 'MOD-EVD',
        );
        $invalidStream = fopen(storage_path('app/private/'.$invalid['blob_key']), 'rb');
        $this->assertIsResource($invalidStream);
        try {
            $this->expectException(InvalidArgumentException::class);
            app(ExternalEvidenceImportService::class)->import($invalidStream, $actorId);
        } finally {
            fclose($invalidStream);
        }
    }

    public function test_source_import_rejects_executable_extensions_before_custody(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $this->expectException(InvalidArgumentException::class);
        app(SafeSourceImportService::class)->import(UploadedFile::fake()->createWithContent('payload.php', '<?php echo 1;'), (string) $owner->id);
    }
}
