<?php

namespace Tests\Feature;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BackupRestoreCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_create_via_http(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        
        $response = $this->actingAs($owner, 'owner_web')->post('/system/backups');
        $response->assertRedirect();
        
        $this->assertDatabaseHas('backup_manifests', [
            'actor_id' => $owner->id,
            'status' => 'verified',
        ]);
        
        $this->assertDatabaseHas('audit_records', [
            'action' => 'backup.created',
            'outcome' => 'success',
        ]);
    }

    public function test_restore_stage_via_http_does_not_apply(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        
        // Generate a backup first to get a valid package file
        $backupResponse = $this->actingAs($owner, 'owner_web')->post('/system/backups');
        $manifest = \App\Modules\Platform\Backup\BackupManifest::first();
        $package = \App\Modules\Platform\Packages\PortablePackage::find($manifest->portable_package_id);
        
        // Find the actual file in storage
        $blob = \App\Modules\Platform\Blobs\BlobObject::find($package->blob_object_id);
        
        // We can't directly use storage_path because blob storage might be different.
        // Let's create an UploadedFile from the blob.
        $stream = app(\App\Modules\Platform\Blobs\BlobStore::class)->readStream($blob->storage_key);
        $tempPath = tempnam(sys_get_temp_dir(), 'restore');
        file_put_contents($tempPath, stream_get_contents($stream));
        fclose($stream);

        $file = new \Illuminate\Http\UploadedFile(
            $tempPath,
            'backup.pkg',
            'application/octet-stream',
            null,
            true
        );

        $response = $this->actingAs($owner, 'owner_web')->post('/system/backups/restores/stage', [
            'package' => $file,
        ]);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('restore_runs', [
            'actor_id' => $owner->id,
            'status' => 'staged',
        ]);
        
        $this->assertDatabaseMissing('restore_runs', [
            'status' => 'applying',
        ]);
        
        $this->assertDatabaseMissing('restore_runs', [
            'status' => 'activation_pending',
        ]);
        
        unlink($tempPath);
    }
}
