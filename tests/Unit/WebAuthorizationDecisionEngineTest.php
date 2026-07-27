<?php

namespace Tests\Unit;

use App\Modules\Simulator\Authorization\WebAuthorizationDecisionEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class WebAuthorizationDecisionEngineTest extends TestCase
{
    /** @param array<string,mixed> $changes */
    #[DataProvider('cases')]
    #[Test]
    public function all_twelve_request_cases_are_deterministic_and_bounded(string $caseId, string $mode, array $changes, string $decision, int $status, ?string $findingCategory): void
    {
        $input = array_replace($this->baseInput(), $changes);
        $context = $this->context($caseId, $mode, $input);
        $first = (new WebAuthorizationDecisionEngine)->evaluate($input, $context);
        $second = (new WebAuthorizationDecisionEngine)->evaluate($input, $context);

        $this->assertSame($decision, $first['decision']);
        $this->assertSame($status, $first['response_status']);
        $this->assertSame($first['trace_digest'], $second['trace_digest']);
        $this->assertSame('SIMULATED', $first['evidence_origin']);
        $this->assertFalse($first['redaction_result']['secrets_stored']);
        $this->assertNotEmpty($first['trust_boundary_steps']);
        $this->assertSame('security_finding', $first['trust_boundary_steps'][array_key_last($first['trust_boundary_steps'])]['boundary']);
        $categories = array_column($first['finding_candidates'], 'category');
        $findingCategory === null ? $this->assertSame([], $categories) : $this->assertContains($findingCategory, $categories);
        $serialized = json_encode($first, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('password=', $serialized);
        $this->assertStringNotContainsString('Bearer ', $serialized);
    }

    #[Test]
    public function unsupported_pinned_behavior_version_fails_closed(): void
    {
        $input = $this->baseInput();
        $context = $this->context('CASE-WEB-UNSUPPORTED-VERSION', 'secure', $input);
        $context['rule_behavior_version'] = 'web_authorization_v2';

        $trace = (new WebAuthorizationDecisionEngine)->evaluate($input, $context);

        $this->assertSame('UNSUPPORTED_STATE', $trace['decision']);
        $this->assertSame('WEB-RULE-UNSUPPORTED-POLICY', $trace['decisive_rule_id']);
    }

    /** @return iterable<string,array{string,string,array<string,mixed>,string,int,?string}> */
    public static function cases(): iterable
    {
        yield 'CASE-WEB-001 owner vulnerable allow' => ['CASE-WEB-001', 'vulnerable', [], 'ALLOW', 200, null];
        yield 'CASE-WEB-002 cross owner vulnerable finding' => ['CASE-WEB-002', 'vulnerable', ['actor_id' => 'SIM-BOB'], 'ALLOW', 200, 'access_control'];
        yield 'CASE-WEB-003 cross owner fixed deny' => ['CASE-WEB-003', 'secure', ['actor_id' => 'SIM-BOB'], 'DENY', 403, null];
        yield 'CASE-WEB-004 unauthenticated' => ['CASE-WEB-004', 'vulnerable', ['actor_id' => '', 'session_state' => 'unauthenticated'], 'UNAUTHENTICATED', 401, null];
        yield 'CASE-WEB-005 server admin' => ['CASE-WEB-005', 'secure', ['actor_id' => 'SIM-ADMIN', 'server_role' => 'admin'], 'ALLOW', 200, null];
        yield 'CASE-WEB-006 client role ignored' => ['CASE-WEB-006', 'secure', ['actor_id' => 'SIM-BOB', 'client_supplied_role' => 'admin', 'client_supplied_owner_id' => 'SIM-BOB'], 'DENY', 403, null];
        yield 'CASE-WEB-007 contract mismatch' => ['CASE-WEB-007', 'secure', ['method' => 'POST', 'requested_action' => 'case_file.update'], 'DENY', 405, null];
        yield 'CASE-WEB-008 not found' => ['CASE-WEB-008', 'vulnerable', ['resource_id' => 'CF-MISSING-404', 'resource_exists' => false], 'NOT_FOUND', 404, null];
        yield 'CASE-WEB-009 invalid resource id' => ['CASE-WEB-009', 'vulnerable', ['resource_id' => '../'], 'INSUFFICIENT_STATE', 422, null];
        yield 'CASE-WEB-010 unsupported policy' => ['CASE-WEB-010', 'unsupported', [], 'UNSUPPORTED_STATE', 422, null];
        yield 'CASE-WEB-011 serializer excludes fields' => ['CASE-WEB-011', 'vulnerable', ['serializer_requested_fields' => ['id', 'title', 'status', 'owner_display', 'internal_notes', 'session_token']], 'ALLOW', 200, 'serialization'];
        yield 'CASE-WEB-012 replay source case' => ['CASE-WEB-012', 'vulnerable', ['actor_id' => 'SIM-BOB'], 'ALLOW', 200, 'access_control'];
    }

    /** @return array<string,mixed> */
    private function baseInput(): array
    {
        return ['actor_id' => 'SIM-ALICE', 'session_state' => 'authenticated', 'server_role' => 'user', 'method' => 'GET', 'route_template' => '/api/case-files/{caseFileId}', 'resource_id' => 'CF-ALICE-001', 'resource_exists' => true, 'target_owner_id' => 'SIM-ALICE', 'requested_action' => 'case_file.read', 'client_supplied_role' => null, 'client_supplied_owner_id' => null, 'serializer_requested_fields' => ['id', 'title', 'status', 'owner_display'], 'origin_context' => 'synthetic-same-origin'];
    }

    /** @return array<string,mixed> */
    private function context(string $caseId, string $mode, array $input): array
    {
        $actorId = $input['actor_id'];
        $resourceId = $input['resource_id'];
        $isKnownResource = $resourceId === 'CF-ALICE-001';
        $isKnownActor = in_array($actorId, ['SIM-ALICE', 'SIM-BOB', 'SIM-ADMIN'], true);

        return ['scenario_revision_id' => 'scenario-rev-1', 'rule_set_revision_id' => 'rules-rev-1', 'policy_revision_id' => "policy-{$mode}", 'endpoint_contract_revision_id' => 'contract-rev-1', 'enterprise_baseline_revision_id' => 'enterprise-rev-1', 'run_id' => 'run-1', 'seed' => 8002, 'case_id' => $caseId, 'ordered_actions' => ['normalize_http_boundary', 'authenticate_server_context', 'lookup_resource', 'authorize_subject_action_resource', 'serialize_approved_shape', 'emit_bounded_finding'], 'policy_mode' => $mode, 'policy_rules' => ['behavior_version' => $mode === 'unsupported' ? 'unsupported' : 'web_authorization_v1'], 'rule_behavior_version' => 'web_authorization_v1', 'baseline_facts' => ['baseline_revision_id' => 'enterprise-rev-1', 'actor_exists' => $isKnownActor, 'actor_id' => $actorId, 'server_role' => $actorId === 'SIM-ADMIN' ? 'admin' : 'user', 'resource_exists' => $isKnownResource, 'resource_id' => $resourceId, 'resource_owner_id' => $isKnownResource ? 'SIM-ALICE' : null, 'approved_method' => 'GET', 'approved_action' => 'case_file.read'], 'contract_method' => 'GET', 'route_template' => '/api/case-files/{caseFileId}', 'contract_action' => 'case_file.read', 'response_shape_id' => 'CASEFILE-SAFE-V1', 'allowed_response_fields' => ['id', 'title', 'status', 'owner_display'], 'source_claim_ids' => ['WEB-AUTH-002', 'WEB-AUTH-003'], 'remediation_revision_id' => null, 'verification_of_run_id' => null];
    }
}
