<?php

namespace Tests\Unit;

use App\Modules\Simulator\Authorization\WindowsAuthorizationDecisionEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WindowsAuthorizationDecisionEngineTest extends TestCase
{
    /** @param array<string, mixed> $input */
    #[DataProvider('decisionCases')]
    public function test_bounded_authorization_cases(array $input, string $outcome, string $rule): void
    {
        $trace = (new WindowsAuthorizationDecisionEngine)->evaluate($input, self::context());

        $this->assertSame($outcome, $trace['final_outcome']);
        $this->assertSame($rule, $trace['decisive_rule_id']);
        $this->assertSame('SIMULATED', $trace['evidence_origin']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $trace['output_digest']);
    }

    public function test_same_input_revision_seed_and_actions_are_digest_deterministic(): void
    {
        $engine = new WindowsAuthorizationDecisionEngine;
        $first = $engine->evaluate(self::baseInput(), self::context());
        $second = $engine->evaluate(self::baseInput(), self::context());

        $this->assertSame($first, $second);
        $this->assertSame($first['output_digest'], $second['output_digest']);
    }

    public function test_ordered_trace_records_non_applicable_and_mask_accumulation_steps(): void
    {
        $input = self::baseInput();
        $input['requested_access_mask'] = '0x00000003';
        $input['security_descriptor']['dacl'] = [
            ['type' => 'ACCESS_DENIED', 'trustee_sid' => $input['token_user_sid'], 'access_mask' => '0x00000001', 'inherit_only' => true],
            ['type' => 'ACCESS_ALLOWED', 'trustee_sid' => $input['token_user_sid'], 'access_mask' => '0x00000001'],
            ['type' => 'ACCESS_ALLOWED', 'trustee_sid' => $input['token_user_sid'], 'access_mask' => '0x00000002'],
        ];

        $trace = (new WindowsAuthorizationDecisionEngine)->evaluate($input, self::context());

        $this->assertSame('non_applicable_ace', $trace['ordered_ace_steps'][0]['reason']);
        $this->assertSame('0x00000002', $trace['ordered_ace_steps'][1]['mask_after']);
        $this->assertSame('0x00000000', $trace['ordered_ace_steps'][2]['mask_after']);
    }

    /** @return iterable<string, array{array<string, mixed>, string, string}> */
    public static function decisionCases(): iterable
    {
        yield 'user explicit allow' => [self::baseInput(), 'ALLOW', 'RULE-ALLOW-COMPLETE'];

        $group = self::baseInput();
        $group['security_descriptor']['dacl'][0]['trustee_sid'] = 'S-1-5-32-544';
        yield 'enabled group allow' => [$group, 'ALLOW', 'RULE-ALLOW-COMPLETE'];

        $deny = self::baseInput();
        $deny['security_descriptor']['dacl'] = [
            ['type' => 'ACCESS_DENIED', 'trustee_sid' => $deny['token_user_sid'], 'access_mask' => '0x00000001'],
            ['type' => 'ACCESS_ALLOWED', 'trustee_sid' => $deny['token_user_sid'], 'access_mask' => '0x00000001'],
        ];
        yield 'deny before later allow' => [$deny, 'DENY', 'RULE-ACE-DENY'];

        $cumulative = self::baseInput();
        $cumulative['requested_access_mask'] = '0x00000003';
        $cumulative['security_descriptor']['dacl'] = [
            ['type' => 'ACCESS_ALLOWED', 'trustee_sid' => $cumulative['token_user_sid'], 'access_mask' => '0x00000001'],
            ['type' => 'ACCESS_ALLOWED', 'trustee_sid' => $cumulative['token_user_sid'], 'access_mask' => '0x00000002'],
        ];
        yield 'cumulative allows' => [$cumulative, 'ALLOW', 'RULE-ALLOW-COMPLETE'];

        $partial = $cumulative;
        array_pop($partial['security_descriptor']['dacl']);
        yield 'remaining mask denies' => [$partial, 'DENY', 'RULE-REMAINING-MASK-DENY'];

        $missingPrincipal = self::baseInput();
        $missingPrincipal['principal'] = '';
        yield 'missing principal' => [$missingPrincipal, 'INSUFFICIENT_STATE', 'RULE-INPUT-PRINCIPAL'];

        $missingDescriptor = self::baseInput();
        unset($missingDescriptor['security_descriptor']);
        yield 'missing descriptor' => [$missingDescriptor, 'INSUFFICIENT_STATE', 'RULE-INPUT-DESCRIPTOR'];

        $missingMapping = self::baseInput();
        $missingMapping['requested_access_mask'] = '0x80000000';
        yield 'missing generic mapping' => [$missingMapping, 'INSUFFICIENT_STATE', 'RULE-GENERIC-MAPPING-MISSING'];

        $mapped = self::baseInput();
        $mapped['requested_access_mask'] = '0x80000000';
        $mapped['generic_mapping_id'] = WindowsAuthorizationDecisionEngine::GENERIC_MAPPING;
        $mapped['security_descriptor']['dacl'][0]['access_mask'] = '0x00120089';
        yield 'approved file generic mapping' => [$mapped, 'ALLOW', 'RULE-ALLOW-COMPLETE'];

        $unsupportedAce = self::baseInput();
        $unsupportedAce['security_descriptor']['dacl'][0]['type'] = 'ACCESS_ALLOWED_CALLBACK';
        yield 'unsupported ACE' => [$unsupportedAce, 'UNSUPPORTED_STATE', 'RULE-UNSUPPORTED-ACE'];

        $privilege = self::baseInput();
        $privilege['declared_privileges'] = ['SeBackupPrivilege'];
        yield 'privilege-dependent case' => [$privilege, 'UNSUPPORTED_STATE', 'RULE-UNSUPPORTED-PRIVILEGE'];

        $nonApplicable = self::baseInput();
        $nonApplicable['security_descriptor']['dacl'] = [
            ['type' => 'ACCESS_DENIED', 'trustee_sid' => $nonApplicable['token_user_sid'], 'access_mask' => '0x00000001', 'applies_to_object' => false],
            ['type' => 'ACCESS_ALLOWED', 'trustee_sid' => $nonApplicable['token_user_sid'], 'access_mask' => '0x00000001'],
        ];
        yield 'non-applicable ACE is skipped' => [$nonApplicable, 'ALLOW', 'RULE-ALLOW-COMPLETE'];

        $denyOnly = self::baseInput();
        $denyOnly['token_groups'][0] = ['sid' => 'S-1-5-32-544', 'enabled' => false, 'deny_only' => true];
        $denyOnly['security_descriptor']['dacl'][0]['trustee_sid'] = 'S-1-5-32-544';
        yield 'deny-only group cannot grant allow' => [$denyOnly, 'DENY', 'RULE-REMAINING-MASK-DENY'];

        $malformed = self::baseInput();
        $malformed['token_user_sid'] = 'not-a-sid';
        yield 'malformed SID' => [$malformed, 'INSUFFICIENT_STATE', 'RULE-INPUT-PRINCIPAL'];
    }

    /** @return array<string, mixed> */
    private static function baseInput(): array
    {
        return [
            'principal' => 'S-1-5-21-1000',
            'token_user_sid' => 'S-1-5-21-1000',
            'token_groups' => [['sid' => 'S-1-5-32-544', 'enabled' => true, 'deny_only' => false]],
            'declared_privileges' => [],
            'target_object' => 'FILE-TRAINING-001',
            'object_type' => 'FILE',
            'security_descriptor' => [
                'owner' => 'S-1-5-21-1000',
                'dacl' => [['type' => 'ACCESS_ALLOWED', 'trustee_sid' => 'S-1-5-21-1000', 'access_mask' => '0x00000001']],
            ],
            'requested_access_mask' => '0x00000001',
        ];
    }

    /** @return array<string, mixed> */
    private static function context(): array
    {
        return [
            'rule_set_id' => 'WIN-FILE-DACL-SUBSET',
            'rule_set_revision' => 1,
            'authority_baseline_id' => WindowsAuthorizationDecisionEngine::AUTHORITY_BASELINE,
            'scenario_revision_id' => '019f876b-c70b-7fc3-a76b-fce68f1ac381',
            'run_id' => '019f876b-c70b-7fc3-a76b-fce68f1ac382',
            'seed' => 7001,
            'source_claim_ids' => ['WIN-AUTH-002', 'WIN-AUTH-003', 'WIN-AUTH-004', 'WIN-AUTH-005'],
        ];
    }
}
