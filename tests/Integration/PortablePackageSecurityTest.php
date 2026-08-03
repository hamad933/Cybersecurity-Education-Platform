<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Platform\Packages\SafePackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;
use ZipArchive;

final class PortablePackageSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_package_verifies_and_preserves_actor_binding(): void
    {
        $owner = app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());
        $created = app(SafePackageService::class)->create(
            'test-package',
            1,
            (string) $owner->id,
            ['purpose' => 'verification'],
            ['payload.json' => "{\"status\":\"PASS\"}\n"],
        );

        $stream = fopen(storage_path('app/private/'.$created['blob_key']), 'rb');
        $this->assertIsResource($stream);
        try {
            $verified = app(SafePackageService::class)->verifyStream($stream, ['test-package']);
        } finally {
            fclose($stream);
        }

        $this->assertSame((string) $owner->id, $verified->manifest['actor_id']);
        $this->assertSame("{\"status\":\"PASS\"}\n", $verified->files['payload.json']);
    }

    public function test_archive_with_traversal_member_is_rejected_before_extraction(): void
    {
        $temporary = tempnam(sys_get_temp_dir(), 'task010-malicious-');
        $this->assertNotFalse($temporary);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('../escape.txt', 'blocked');
        $zip->addFromString('manifest.json', '{}');
        $zip->close();

        $stream = fopen($temporary, 'rb');
        $this->assertIsResource($stream);
        try {
            $this->expectException(InvalidArgumentException::class);
            app(SafePackageService::class)->verifyStream($stream, ['test-package']);
        } finally {
            fclose($stream);
            @unlink($temporary);
        }
    }
}
