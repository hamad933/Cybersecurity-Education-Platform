<?php

namespace App\Modules\Simulator\RunResult;

use InvalidArgumentException;

class DeterministicSimulationEngine
{
    public const OPERATION_GRAMMAR = 'v1';

    /**
     * @param  array{operation_key: string, verb: string, target: string, value: mixed}  $operation
     */
    public function assertOperation(array $operation): void
    {
        if (
            preg_match('/^[A-Za-z0-9._:-]{12,120}$/', $operation['operation_key']) !== 1
            || $operation['verb'] !== 'SET_CONTROL_STATE'
            || $operation['target'] !== 'IDENTITY_MFA'
            || ! is_bool($operation['value'])
        ) {
            throw new InvalidArgumentException('Unsupported or malformed internal simulation operation.');
        }
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array{operation_key: string, verb: string, target: string, value: mixed}  $operation
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public function applyOperationGrammar(array $state, array $operation): array
    {
        $this->assertOperation($operation);
        $simulatedState = is_array($state['simulated_state'] ?? null) ? $state['simulated_state'] : [];
        $identityPolicy = is_array($simulatedState['identity_policy'] ?? null) ? $simulatedState['identity_policy'] : [];
        $before = ($identityPolicy['mfa_required'] ?? false) === true;
        $after = $operation['value'];
        $identityPolicy['mfa_required'] = $after;
        $simulatedState['identity_policy'] = $identityPolicy;
        $state['simulated_state'] = $simulatedState;
        $causality = is_array($state['causality'] ?? null) ? $state['causality'] : [];
        $causality[] = [
            'operation_key' => $operation['operation_key'],
            'grammar_version' => self::OPERATION_GRAMMAR,
            'verb' => $operation['verb'],
            'target' => $operation['target'],
            'before' => $before,
            'after' => $after,
        ];
        $state['causality'] = $causality;
        $existingTelemetry = is_array($state['telemetry'] ?? null) ? $state['telemetry'] : [];
        $telemetry = [
            'state_changed' => $before !== $after,
            'mfa_required_before' => $before,
            'mfa_required_after' => $after,
            'operation_count' => (int) ($existingTelemetry['operation_count'] ?? 0) + 1,
            'state_change_count' => (int) ($existingTelemetry['state_change_count'] ?? 0) + ($before !== $after ? 1 : 0),
        ];
        $state['telemetry'] = $telemetry;

        return [$state, $telemetry];
    }
}
