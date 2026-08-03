<?php

namespace App\Modules\Simulator\Authorization;

final class WebAuthorizationDecisionEngine
{
    /**
     * @param  array<string,mixed>  $input
     * @param  array<string,mixed>  $context
     * @return array<string,mixed>
     */
    public function evaluate(array $input, array $context): array
    {
        $inputDigest = $this->digest($input);
        $requestId = 'req-'.substr($inputDigest, 0, 20);
        $correlationId = 'corr-'.substr($this->digest([$context['scenario_revision_id'], $context['seed'], $inputDigest]), 0, 20);
        $steps = [];
        $rules = [];
        $findingCandidates = [];
        $decision = 'DENY';
        $status = 403;
        $decisiveRule = 'WEB-RULE-DEFAULT-DENY';

        $resourceId = is_string($input['resource_id'] ?? null) ? $input['resource_id'] : '';
        $actorId = is_string($input['actor_id'] ?? null) ? $input['actor_id'] : '';
        $baselineFacts = is_array($context['baseline_facts'] ?? null) ? $context['baseline_facts'] : [];
        $ownerId = is_string($baselineFacts['resource_owner_id'] ?? null) ? $baselineFacts['resource_owner_id'] : '';
        $serverRole = is_string($baselineFacts['server_role'] ?? null) ? $baselineFacts['server_role'] : '';
        $sessionState = is_string($input['session_state'] ?? null) ? $input['session_state'] : 'missing';
        $method = is_string($input['method'] ?? null) ? strtoupper($input['method']) : '';
        $routeTemplate = is_string($input['route_template'] ?? null) ? $input['route_template'] : '';
        $action = is_string($input['requested_action'] ?? null) ? $input['requested_action'] : '';
        $policyMode = is_string($context['policy_mode'] ?? null) ? $context['policy_mode'] : 'unsupported';
        $clientRole = is_string($input['client_supplied_role'] ?? null) ? $input['client_supplied_role'] : null;
        $clientOwner = is_string($input['client_supplied_owner_id'] ?? null) ? $input['client_supplied_owner_id'] : null;

        $steps[] = ['boundary_id' => 'TB-WEB-001', 'boundary' => 'client_to_http', 'result' => 'normalized'];
        if ($resourceId === '' || preg_match('/^CF-[A-Z0-9-]{3,40}$/', $resourceId) !== 1) {
            $decision = 'INSUFFICIENT_STATE';
            $status = 422;
            $decisiveRule = 'WEB-RULE-RESOURCE-ID-REQUIRED';
            $steps[] = ['boundary_id' => 'TB-WEB-002', 'boundary' => 'route_input_normalization', 'result' => 'invalid_resource_id'];
        } else {
            $steps[] = ['boundary_id' => 'TB-WEB-002', 'boundary' => 'route_input_normalization', 'result' => 'valid'];
            if ($sessionState !== 'authenticated' || $actorId === '') {
                $decision = 'UNAUTHENTICATED';
                $status = 401;
                $decisiveRule = 'WEB-RULE-AUTHENTICATION-REQUIRED';
                $steps[] = ['boundary_id' => 'TB-WEB-003', 'boundary' => 'authentication_context', 'result' => 'unauthenticated'];
            } else {
                $steps[] = ['boundary_id' => 'TB-WEB-003', 'boundary' => 'authentication_context', 'result' => 'authenticated'];
                if ($baselineFacts === [] || ($baselineFacts['baseline_revision_id'] ?? null) !== ($context['enterprise_baseline_revision_id'] ?? null) || ! is_bool($baselineFacts['actor_exists'] ?? null) || ! is_bool($baselineFacts['resource_exists'] ?? null) || ! is_string($baselineFacts['approved_method'] ?? null) || ! is_string($baselineFacts['approved_action'] ?? null)) {
                    $decision = 'INSUFFICIENT_STATE';
                    $status = 422;
                    $decisiveRule = 'WEB-RULE-BASELINE-FACTS-REQUIRED';
                    $steps[] = ['boundary_id' => 'TB-WEB-004', 'boundary' => 'baseline_read_contract', 'result' => 'insufficient_state'];
                } elseif ($method !== ($context['contract_method'] ?? null) || $routeTemplate !== ($context['route_template'] ?? null) || $action !== ($context['contract_action'] ?? null) || $method !== $baselineFacts['approved_method'] || $action !== $baselineFacts['approved_action']) {
                    $decision = 'DENY';
                    $status = 405;
                    $decisiveRule = 'WEB-RULE-ENDPOINT-CONTRACT';
                    $steps[] = ['boundary_id' => 'TB-WEB-004', 'boundary' => 'endpoint_contract', 'result' => 'method_action_mismatch'];
                } elseif ($baselineFacts['actor_exists'] !== true || $baselineFacts['resource_exists'] !== true) {
                    $decision = 'NOT_FOUND';
                    $status = 404;
                    $decisiveRule = 'WEB-RULE-RESOURCE-NOT-FOUND';
                    $steps[] = ['boundary_id' => 'TB-WEB-004', 'boundary' => 'resource_lookup', 'result' => 'not_found'];
                } elseif (($context['rule_behavior_version'] ?? null) !== 'web_authorization_v1' || ! is_array($context['policy_rules'] ?? null) || (($context['policy_rules']['behavior_version'] ?? null) !== 'web_authorization_v1') || $policyMode === 'unsupported') {
                    $decision = 'UNSUPPORTED_STATE';
                    $status = 422;
                    $decisiveRule = 'WEB-RULE-UNSUPPORTED-POLICY';
                    $steps[] = ['boundary_id' => 'TB-WEB-005', 'boundary' => 'authorization_policy', 'result' => 'unsupported'];
                } else {
                    $steps[] = ['boundary_id' => 'TB-WEB-004', 'boundary' => 'resource_lookup', 'result' => 'found'];
                    $rules[] = ['rule_id' => 'WEB-RULE-CLIENT-CONTEXT-IGNORED', 'result' => ($clientRole !== null || $clientOwner !== null) ? 'ignored_untrusted_fields' : 'no_untrusted_fields'];
                    if ($policyMode === 'vulnerable') {
                        $decision = 'ALLOW';
                        $status = 200;
                        $decisiveRule = 'WEB-RULE-VULNERABLE-AUTHENTICATED-ALLOW';
                        $rules[] = ['rule_id' => $decisiveRule, 'result' => 'allowed_without_ownership_check'];
                        if ($actorId !== $ownerId && $serverRole !== 'admin') {
                            $findingCandidates[] = $this->finding(
                                'access_control',
                                $context,
                                $resourceId,
                                'object_ownership_check_missing',
                                ['actor_id' => $actorId, 'target_owner_id' => $ownerId, 'requested_action' => $action],
                            );
                        }
                    } elseif ($serverRole === 'admin') {
                        $decision = 'ALLOW';
                        $status = 200;
                        $decisiveRule = 'WEB-RULE-EXPLICIT-SERVER-ADMIN';
                        $rules[] = ['rule_id' => $decisiveRule, 'result' => 'allowed'];
                    } elseif ($actorId === $ownerId) {
                        $decision = 'ALLOW';
                        $status = 200;
                        $decisiveRule = 'WEB-RULE-OWNER-ALLOW';
                        $rules[] = ['rule_id' => $decisiveRule, 'result' => 'allowed'];
                    } else {
                        $decision = 'DENY';
                        $status = 403;
                        $decisiveRule = 'WEB-RULE-CROSS-OWNER-DENY';
                        $rules[] = ['rule_id' => $decisiveRule, 'result' => 'denied'];
                    }
                    $steps[] = ['boundary_id' => 'TB-WEB-005', 'boundary' => 'authorization_policy', 'result' => strtolower($decision)];
                }
            }
        }

        $requestedFields = is_array($input['serializer_requested_fields'] ?? null) ? $input['serializer_requested_fields'] : [];
        $allowedFields = is_array($context['allowed_response_fields'] ?? null) ? $context['allowed_response_fields'] : [];
        $safeRequested = array_values(array_filter($requestedFields, fn (mixed $field): bool => is_string($field)));
        $includedFields = array_values(array_intersect($safeRequested, $allowedFields));
        $excludedFields = array_values(array_diff($safeRequested, $allowedFields));
        if ($excludedFields !== []) {
            $findingCandidates[] = $this->finding(
                'serialization',
                $context,
                $resourceId,
                'non_approved_response_field_excluded',
                ['excluded_fields' => $excludedFields, 'response_shape_id' => $context['response_shape_id']],
            );
        }
        $steps[] = ['boundary_id' => 'TB-WEB-006', 'boundary' => 'response_serialization', 'result' => $excludedFields === [] ? 'approved_shape' : 'unapproved_fields_excluded'];
        $steps[] = ['boundary_id' => 'TB-WEB-007', 'boundary' => 'security_finding', 'result' => $findingCandidates === [] ? 'none' : 'bounded_finding'];

        $findingIds = array_map(fn (array $finding): string => $finding['finding_id'], $findingCandidates);
        $authorizationInputs = [
            'server_actor_id' => $actorId,
            'server_role' => $serverRole,
            'resource_owner_id' => $ownerId,
            'requested_action' => $action,
            'client_supplied_role_ignored' => $clientRole,
            'client_supplied_owner_ignored' => $clientOwner,
        ];
        $clientRequestContext = [
            'actor_id' => $actorId,
            'session_state' => $sessionState,
            'method' => $method,
            'route_template' => $routeTemplate,
            'resource_id' => $resourceId,
            'requested_action' => $action,
        ];
        $digestPayload = [
            'scenario_revision_id' => $context['scenario_revision_id'],
            'rule_set_revision_id' => $context['rule_set_revision_id'],
            'policy_revision_id' => $context['policy_revision_id'],
            'endpoint_contract_revision_id' => $context['endpoint_contract_revision_id'],
            'enterprise_baseline_revision_id' => $context['enterprise_baseline_revision_id'],
            'seed' => $context['seed'],
            'ordered_actions' => $context['ordered_actions'],
            'input_digest' => $inputDigest,
            'trust_boundary_steps' => $steps,
            'authorization_inputs' => $authorizationInputs,
            'client_request_context' => $clientRequestContext,
            'baseline_derived_facts' => $baselineFacts,
            'authorization_rule_steps' => $rules,
            'decisive_rule_id' => $decisiveRule,
            'decision' => $decision,
            'response_status' => $status,
            'included_fields' => $includedFields,
            'excluded_fields' => $excludedFields,
            'finding_aggregate_keys' => array_map(fn (array $finding): string => $finding['finding_key'], $findingCandidates),
            'remediation_revision_id' => $context['remediation_revision_id'] ?? null,
            'verification_of_run_id' => $context['verification_of_run_id'] ?? null,
        ];

        return [
            'scenario_revision_id' => $context['scenario_revision_id'],
            'rule_set_revision_id' => $context['rule_set_revision_id'],
            'policy_revision_id' => $context['policy_revision_id'],
            'endpoint_contract_revision_id' => $context['endpoint_contract_revision_id'],
            'enterprise_baseline_revision_id' => $context['enterprise_baseline_revision_id'],
            'run_id' => $context['run_id'],
            'seed' => $context['seed'],
            'request_id' => $requestId,
            'correlation_id' => $correlationId,
            'actor_id' => $actorId,
            'session_state' => $sessionState,
            'method' => $method,
            'route_template' => $routeTemplate,
            'path_parameters' => ['caseFileId' => $resourceId],
            'query_parameters' => ['role' => $clientRole],
            'bounded_body_fields' => ['owner_id' => $clientOwner],
            'origin_context' => $input['origin_context'] ?? 'synthetic-same-origin',
            'target_resource_id' => $resourceId,
            'target_owner_id' => $ownerId,
            'requested_action' => $action,
            'trust_boundary_steps' => $steps,
            'authentication_result' => $sessionState === 'authenticated' && $actorId !== '' ? 'AUTHENTICATED' : 'UNAUTHENTICATED',
            'resource_lookup_result' => ($baselineFacts['resource_exists'] ?? false) === true ? 'FOUND' : 'NOT_FOUND',
            'authorization_inputs' => $authorizationInputs,
            'client_request_context' => $clientRequestContext,
            'baseline_derived_facts' => $baselineFacts,
            'authorization_rule_steps' => $rules,
            'decisive_rule_id' => $decisiveRule,
            'decision' => $decision,
            'response_status' => $status,
            'response_shape_id' => $context['response_shape_id'],
            'redaction_result' => ['included_fields' => $includedFields, 'excluded_fields' => $excludedFields, 'secrets_stored' => false],
            'finding_ids' => $findingIds,
            'finding_candidates' => $findingCandidates,
            'remediation_revision_id_if_any' => $context['remediation_revision_id'] ?? null,
            'verification_of_run_id_if_any' => $context['verification_of_run_id'] ?? null,
            'input_digest' => $inputDigest,
            'trace_digest' => $this->digest($digestPayload),
            'source_claim_ids' => $context['source_claim_ids'],
            'limitations' => ['synthetic_local_only', 'no_live_http_request', 'no_browser_policy_claim', 'no_public_target', 'no_scanner'],
            'evidence_origin' => 'SIMULATED',
        ];
    }

    /**
     * @param  array<string,mixed>  $context
     * @param  array<string,mixed>  $safeDetails
     * @return array<string,mixed>
     */
    private function finding(string $category, array $context, string $resourceId, string $missingCheck, array $safeDetails): array
    {
        $key = $this->digest([$category, $context['scenario_revision_id'], $context['policy_revision_id'], $context['case_id'], $resourceId, $missingCheck]);
        $occurrenceKey = $this->digest([$key, $context['run_id'], $safeDetails['actor_id'] ?? null]);
        $findingId = substr($occurrenceKey, 0, 8).'-'.substr($occurrenceKey, 8, 4).'-7'.substr($occurrenceKey, 13, 3).'-a'.substr($occurrenceKey, 17, 3).'-'.substr($occurrenceKey, 20, 12);

        return [
            'finding_id' => $findingId,
            'finding_key' => $key,
            'occurrence_key' => $occurrenceKey,
            'category' => $category,
            'decisive_missing_check' => $missingCheck,
            'safe_details' => $safeDetails,
        ];
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }
}
