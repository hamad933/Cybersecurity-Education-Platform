<?php

namespace Tests\Integration;

use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\Platform\Audit\AuditChainVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AuditIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_chain_detects_direct_record_tampering(): void
    {
        app(CreateOwner::class)->execute('Owner', 'owner@example.test', 'VeryStrong!Pass9', (string) Str::uuid7());

        $this->assertTrue(app(AuditChainVerifier::class)->verify()['valid']);

        DB::table('audit_records')->where('sequence_no', 1)->update([
            'outcome' => 'failure',
        ]);

        $result = app(AuditChainVerifier::class)->verify();
        $this->assertFalse($result['valid']);
        $this->assertSame(1, $result['first_invalid_sequence']);
    }
}
