<?php

namespace Tests\Unit;

use App\Modules\Platform\Packages\PackageLimits;
use App\Modules\Platform\Packages\PackagePathGuard;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PackagePathGuardTest extends TestCase
{
    #[DataProvider('unsafePaths')]
    public function test_rejects_unsafe_package_paths(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new PackagePathGuard)->normalize($path, new PackageLimits);
    }

    /** @return list<array{string}> */
    public static function unsafePaths(): array
    {
        return [['../secret'], ['/absolute'], ['C:/windows'], ['a\\b'], ['a//b'], ['a/./b'], ["a/\0b"]];
    }

    public function test_accepts_a_bounded_portable_relative_path(): void
    {
        $this->assertSame('evidence/result.json', (new PackagePathGuard)->normalize('evidence/result.json', new PackageLimits));
    }
}
