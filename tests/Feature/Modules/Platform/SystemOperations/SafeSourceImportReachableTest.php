<?php

namespace Tests\Feature\Modules\Platform\SystemOperations;

use App\Modules\SourceGovernance\Application\SafeSourceImportService;
use App\Modules\Platform\Blobs\BlobStore;
use App\Modules\Platform\Audit\AuditWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SafeSourceImportReachableTest extends TestCase
{
    use RefreshDatabase;

    public function test_safe_source_import_is_fully_reachable_production_path(): void
    {
        $actorId = '018f0a00-0000-7000-8000-000000000000';
        DB::table('owner_accounts')->insertOrIgnore([
            'id' => $actorId,
            'display_name' => 'System',
            'email' => 'system@example.com',
            'password' => 'secret',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $service = new SafeSourceImportService(
            app(BlobStore::class),
            app(AuditWriter::class)
        );
        
        // Use a valid extension matched by SafeSourceImportService logic. 'txt' works and validates.
        $file = UploadedFile::fake()->createWithContent('test-source.txt', 'Valid source content bytes for verification.');
        
        $import = $service->import($file, $actorId);
        
        $this->assertEquals('accepted', $import->status);
        $this->assertNotNull($import->blob_object_id);
        $this->assertNotNull($import->source_record_id);
        $this->assertEquals('txt', $import->extension);
        $this->assertEquals('text/plain', $import->detected_media_type);
    }
}
