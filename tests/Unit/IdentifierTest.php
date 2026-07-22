<?php

namespace Tests\Unit;

use App\Modules\Platform\Identifiers\UsesUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IdentifierTest extends TestCase
{
    #[Test]
    public function uuid_v7_values_are_unique_orderable_and_serializable(): void
    {
        $first = (string) Str::uuid7(now()->subSecond());
        $second = (string) Str::uuid7(now());
        $this->assertNotSame($first, $second);
        $this->assertLessThan(0, strcmp($first, $second));
        $this->assertTrue(Str::isUuid($first, 7));
    }

    #[Test]
    public function entity_trait_generates_uuid_v7_primary_keys(): void
    {
        $model = new class extends Model
        {
            use UsesUuidV7;
        };
        $this->assertTrue(Str::isUuid($model->newUniqueId(), 7));
        $this->assertFalse($model->getIncrementing());
        $this->assertSame('string', $model->getKeyType());
    }
}
