<?php

namespace Tests\Feature;

use App\Application\Vs002\Vs002Lifecycle;
use App\Modules\Evidence\Models\FindingVerification;
use App\Modules\IdentityAccess\Actions\CreateOwner;
use App\Modules\IdentityAccess\Models\OwnerAccount;
use Database\Seeders\Vs002Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class Vs002CorrectionGateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function finding_occurrences_and_verification_are_actor_run_and_trace_bound(): void
    {
        $this->seed(Vs002Seeder::class);
        $lifecycle = app(Vs002Lifecycle::class);
        $firstActor = app(CreateOwner::class)->execute('First learner', 'first-vs2@example.test', 'ReviewReady!Pass9', (string) Str::uuid7());
        $secondActor = OwnerAccount::query()->create(['display_name' => 'Second learner', 'email' => 'second-vs2@example.test', 'password' => 'ReviewReady!Pass9', 'is_active' => false]);

        $first = $lifecycle->runCase('CASE-WEB-002', 9002, 'c8:first', $firstActor->id);
        $second = $lifecycle->runCase('CASE-WEB-002', 9002, 'c8:second', $secondActor->id);
        $firstFinding = $first['findings'][0];
        $secondFinding = $second['findings'][0];

        $this->assertNotSame($firstFinding['id'], $secondFinding['id']);
        $this->assertSame($firstFinding['id'], $first['trace']['finding_ids'][0]);
        $this->assertSame([$firstFinding['id']], $first['evidence']['finding_ids']);
        $this->assertSame($firstActor->id, $firstFinding['actor_id']);
        $this->assertSame($first['run']['id'], $firstFinding['scenario_run_id']);

        $policy = $lifecycle->remediate();
        $verified = $lifecycle->verify($firstFinding['id'], $first['run']['id'], $policy['id'], 'c8:verify', $firstActor->id);
        $verification = FindingVerification::query()->where('security_finding_id', $firstFinding['id'])->sole();

        $this->assertSame($firstActor->id, $verification->actor_id);
        $this->assertSame($first['run']['id'], $verification->vulnerable_run_id);
        $this->assertSame($verified['run']['id'], $verification->verification_run_id);
        $this->assertSame($policy['id'], $verification->remediation_policy_revision_id);
        $this->assertSame($first['run']['trace_digest'], $verification->vulnerable_trace_digest);
        $this->assertSame($verified['run']['trace_digest'], $verification->verification_trace_digest);
    }
}
