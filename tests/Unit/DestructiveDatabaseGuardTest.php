<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\DestructiveDatabaseGuard;
use Tests\TestCase;

class DestructiveDatabaseGuardTest extends TestCase
{
    public function test_accepts_an_explicitly_allowed_postgresql_test_database(): void
    {
        $guard = new DestructiveDatabaseGuard('testing', 'pgsql', 'cyber_platform_test', '127.0.0.1', ['pgsql'], ['127.0.0.1']);

        $guard->assertSafe();
        $this->addToAssertionCount(1);
    }

    /** @param array{0:string,1:string,2:string,3:string,4:list<string>,5:list<string>} $arguments */
    #[DataProvider('unsafeContexts')]
    public function test_rejects_every_unsafe_context(array $arguments): void
    {
        $this->expectException(RuntimeException::class);
        (new DestructiveDatabaseGuard(...$arguments))->assertSafe();
    }

    /** @return iterable<string, array{0:array{0:string,1:string,2:string,3:string,4:list<string>,5:list<string>}}> */
    public static function unsafeContexts(): iterable
    {
        yield 'wrong environment' => [['local', 'pgsql', 'cyber_platform_test', '127.0.0.1', ['pgsql'], ['127.0.0.1']]];
        yield 'wrong connection' => [['testing', 'mysql', 'cyber_platform_test', '127.0.0.1', ['pgsql'], ['127.0.0.1']]];
        yield 'unsafe database name' => [['testing', 'pgsql', 'cyber_platform', '127.0.0.1', ['pgsql'], ['127.0.0.1']]];
        yield 'unapproved host' => [['testing', 'pgsql', 'cyber_platform_test', 'db.example.test', ['pgsql'], ['127.0.0.1']]];
    }
}
