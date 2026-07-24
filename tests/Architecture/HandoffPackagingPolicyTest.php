<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use Task007\Packaging\HandoffPathPolicy;
use Tests\TestCase;

require_once __DIR__.'/../../scripts/Support/HandoffPathPolicy.php';

class HandoffPackagingPolicyTest extends TestCase
{
    #[DataProvider('prohibitedPaths')]
    public function test_runtime_generated_secret_and_historical_zip_paths_are_rejected(string $path): void
    {
        $this->assertTrue(HandoffPathPolicy::isProhibited($path));
    }

    #[DataProvider('safePaths')]
    public function test_source_and_safe_placeholders_are_allowed(string $path): void
    {
        $this->assertFalse(HandoffPathPolicy::isProhibited($path));
    }

    /** @return iterable<string, array{string}> */
    public static function prohibitedPaths(): iterable
    {
        yield 'environment' => ['product-repo/.env'];
        yield 'git metadata' => ['product-repo/.git/config'];
        yield 'vendor' => ['product-repo/vendor/autoload.php'];
        yield 'node modules' => ['product-repo/node_modules/vite/package.json'];
        yield 'frontend build' => ['product-repo/public/build/manifest.json'];
        yield 'bootstrap package cache' => ['product-repo/bootstrap/cache/packages.php'];
        yield 'private blob' => ['product-repo/storage/app/private/blob.bin'];
        yield 'session' => ['product-repo/storage/framework/sessions/session-id'];
        yield 'log' => ['product-repo/storage/logs/laravel.log'];
        yield 'database volume' => ['database-volumes/PG_VERSION'];
        yield 'browser profile' => ['browser-profile/Default/Cookies'];
        yield 'historical zip' => ['product-repo/review-packets/TASK_006_REVIEW_HANDOFF.zip'];
        yield 'traversal' => ['product-repo/../outside.txt'];
    }

    /** @return iterable<string, array{string}> */
    public static function safePaths(): iterable
    {
        yield 'application source' => ['product-repo/app/Modules/Platform/Health/FoundationHealth.php'];
        yield 'environment template' => ['product-repo/.env.example'];
        yield 'bootstrap placeholder' => ['product-repo/bootstrap/cache/.gitignore'];
        yield 'storage placeholder' => ['product-repo/storage/logs/.gitignore'];
        yield 'current review evidence' => ['product-repo/review-packets/vs001-007/FOUNDATION_CORRECTION_GATE.md'];
    }
}
