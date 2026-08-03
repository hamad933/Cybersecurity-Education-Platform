<?php

namespace App\Modules\Simulator\Authorization;

use InvalidArgumentException;

final class WindowsAuthorizationDecisionEngine
{
    public const AUTHORITY_BASELINE = 'WIN11-24H2-26100-FILE-AUTHZ-V1';

    public const GENERIC_MAPPING = 'WINDOWS11_24H2_FILE_V1';

    private const GENERIC_READ = 0x80000000;

    private const GENERIC_WRITE = 0x40000000;

    private const GENERIC_EXECUTE = 0x20000000;

    private const GENERIC_ALL = 0x10000000;

    private const GENERIC_BITS = self::GENERIC_READ | self::GENERIC_WRITE | self::GENERIC_EXECUTE | self::GENERIC_ALL;

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function evaluate(array $input, array $context): array
    {
        $this->validateContext($context);
        $requested = $this->mask($input['requested_access_mask'] ?? null);
        $normalized = $this->canonicalize($input);
        $steps = [];
        $limitations = [
            'Educational subset for Windows 11 24H2 file-object DACL decisions.',
            'No real AccessCheck call, inheritance engine, privileges, integrity labels, conditional ACEs, claims, or central policies.',
        ];

        if (! $this->validSid($input['principal'] ?? null) || ! $this->validSid($input['token_user_sid'] ?? null)) {
            return $this->finish($context, $normalized, $steps, 'INSUFFICIENT_STATE', 'RULE-INPUT-PRINCIPAL', $requested ?? 0, null, $limitations);
        }
        if ($requested === null || $requested === 0) {
            return $this->finish($context, $normalized, $steps, 'INSUFFICIENT_STATE', 'RULE-INPUT-MASK', $requested ?? 0, null, $limitations);
        }
        if (($input['declared_privileges'] ?? []) !== []) {
            return $this->finish($context, $normalized, $steps, 'UNSUPPORTED_STATE', 'RULE-UNSUPPORTED-PRIVILEGE', $requested, null, $limitations);
        }
        if (($input['object_type'] ?? null) !== 'FILE') {
            return $this->finish($context, $normalized, $steps, 'UNSUPPORTED_STATE', 'RULE-UNSUPPORTED-OBJECT-TYPE', $requested, null, $limitations);
        }

        $descriptor = $input['security_descriptor'] ?? null;
        if (! is_array($descriptor) || ! $this->validSid($descriptor['owner'] ?? null) || ! array_key_exists('dacl', $descriptor)) {
            return $this->finish($context, $normalized, $steps, 'INSUFFICIENT_STATE', 'RULE-INPUT-DESCRIPTOR', $requested, null, $limitations);
        }

        $mapping = null;
        if (($requested & self::GENERIC_BITS) !== 0) {
            if (! isset($input['generic_mapping_id'])) {
                return $this->finish($context, $normalized, $steps, 'INSUFFICIENT_STATE', 'RULE-GENERIC-MAPPING-MISSING', $requested, null, $limitations);
            }
            if ($input['generic_mapping_id'] !== self::GENERIC_MAPPING) {
                return $this->finish($context, $normalized, $steps, 'UNSUPPORTED_STATE', 'RULE-GENERIC-MAPPING-UNSUPPORTED', $requested, null, $limitations);
            }
            $mapped = $this->mapFileGenericMask($requested);
            $mapping = ['mapping_id' => self::GENERIC_MAPPING, 'original_mask' => $this->hex($requested), 'mapped_mask' => $this->hex($mapped)];
            $requested = $mapped;
        }

        $groups = $this->normalizeGroups($input['token_groups'] ?? []);
        if ($groups === null) {
            return $this->finish($context, $normalized, $steps, 'INSUFFICIENT_STATE', 'RULE-INPUT-GROUPS', $requested, $mapping, $limitations);
        }
        if ($descriptor['dacl'] === null) {
            return $this->finish($context, $normalized, $steps, 'ALLOW', 'RULE-NULL-DACL-ALLOW', 0, $mapping, $limitations);
        }
        if (! is_array($descriptor['dacl']) || ! array_is_list($descriptor['dacl'])) {
            return $this->finish($context, $normalized, $steps, 'INSUFFICIENT_STATE', 'RULE-INPUT-DACL', $requested, $mapping, $limitations);
        }

        $remaining = $requested;
        foreach ($descriptor['dacl'] as $index => $ace) {
            $before = $remaining;
            if (! is_array($ace) || ! isset($ace['type'], $ace['trustee_sid']) || ! $this->validSid($ace['trustee_sid'])) {
                return $this->finish($context, $normalized, $steps, 'INSUFFICIENT_STATE', 'RULE-INPUT-ACE', $remaining, $mapping, $limitations);
            }
            if (! in_array($ace['type'], ['ACCESS_DENIED', 'ACCESS_ALLOWED'], true)) {
                $steps[] = $this->step($index, $ace, $before, 0, $before, false, 'unsupported_ace_type', 'none');

                return $this->finish($context, $normalized, $steps, 'UNSUPPORTED_STATE', 'RULE-UNSUPPORTED-ACE', $remaining, $mapping, $limitations);
            }
            $aceMask = $this->mask($ace['access_mask'] ?? null);
            if ($aceMask === null || ($aceMask & self::GENERIC_BITS) !== 0) {
                return $this->finish($context, $normalized, $steps, 'INSUFFICIENT_STATE', 'RULE-INPUT-ACE-MASK', $remaining, $mapping, $limitations);
            }
            if (($ace['inherit_only'] ?? false) === true || ($ace['applies_to_object'] ?? true) === false) {
                $steps[] = $this->step($index, $ace, $before, 0, $before, false, 'non_applicable_ace', 'none');

                continue;
            }

            $match = $this->trusteeMatch((string) $ace['trustee_sid'], (string) $input['token_user_sid'], $groups, (string) $ace['type']);
            if ($match === 'none' || $match === 'deny_only_not_for_allow') {
                $steps[] = $this->step($index, $ace, $before, 0, $before, true, $match, $match);

                continue;
            }

            $relevant = $aceMask & $remaining;
            if ($ace['type'] === 'ACCESS_DENIED' && $relevant !== 0) {
                $steps[] = $this->step($index, $ace, $before, $relevant, $before, true, 'decisive_deny', $match);

                return $this->finish($context, $normalized, $steps, 'DENY', 'RULE-ACE-DENY', $remaining, $mapping, $limitations);
            }
            if ($ace['type'] === 'ACCESS_ALLOWED' && $relevant !== 0) {
                $remaining &= ~$relevant;
                $steps[] = $this->step($index, $ace, $before, $relevant, $remaining, true, 'accumulated_allow', $match);
                if ($remaining === 0) {
                    return $this->finish($context, $normalized, $steps, 'ALLOW', 'RULE-ALLOW-COMPLETE', 0, $mapping, $limitations);
                }

                continue;
            }
            $steps[] = $this->step($index, $ace, $before, 0, $before, true, 'no_remaining_mask_intersection', $match);
        }

        return $this->finish($context, $normalized, $steps, 'DENY', 'RULE-REMAINING-MASK-DENY', $remaining, $mapping, $limitations);
    }

    /** @param array<string, mixed> $context */
    private function validateContext(array $context): void
    {
        foreach (['rule_set_id', 'rule_set_revision', 'authority_baseline_id', 'scenario_revision_id', 'run_id', 'seed', 'source_claim_ids'] as $field) {
            if (! array_key_exists($field, $context)) {
                throw new InvalidArgumentException("Missing trace context: {$field}");
            }
        }
        if ($context['authority_baseline_id'] !== self::AUTHORITY_BASELINE) {
            throw new InvalidArgumentException('Unapproved authority baseline.');
        }
    }

    /** @param mixed $value */
    private function mask($value): ?int
    {
        if (is_int($value) && $value >= 0 && $value <= 0xFFFFFFFF) {
            return $value;
        }
        if (is_string($value) && preg_match('/^0x[0-9A-Fa-f]{1,8}$/', $value)) {
            return (int) hexdec(substr($value, 2));
        }

        return null;
    }

    /** @param mixed $sid */
    private function validSid($sid): bool
    {
        return is_string($sid) && preg_match('/^S-\d-(?:\d+-){1,14}\d+$/', $sid) === 1;
    }

    /**
     * @param  mixed  $groups
     * @return list<array{sid:string,enabled:bool,deny_only:bool}>|null
     */
    private function normalizeGroups($groups): ?array
    {
        if (! is_array($groups) || ! array_is_list($groups)) {
            return null;
        }
        $normalized = [];
        foreach ($groups as $group) {
            if (! is_array($group) || ! $this->validSid($group['sid'] ?? null) || ! is_bool($group['enabled'] ?? null) || ! is_bool($group['deny_only'] ?? null)) {
                return null;
            }
            $normalized[] = ['sid' => $group['sid'], 'enabled' => $group['enabled'], 'deny_only' => $group['deny_only']];
        }

        return $normalized;
    }

    /** @param list<array{sid:string,enabled:bool,deny_only:bool}> $groups */
    private function trusteeMatch(string $trustee, string $userSid, array $groups, string $aceType): string
    {
        if ($trustee === $userSid) {
            return 'token_user_sid';
        }
        foreach ($groups as $group) {
            if ($group['sid'] !== $trustee || (! $group['enabled'] && ! $group['deny_only'])) {
                continue;
            }
            if ($group['deny_only'] && $aceType === 'ACCESS_ALLOWED') {
                return 'deny_only_not_for_allow';
            }

            return $group['deny_only'] ? 'deny_only_group' : 'enabled_group';
        }

        return 'none';
    }

    private function mapFileGenericMask(int $mask): int
    {
        $mapped = $mask & ~self::GENERIC_BITS;
        if (($mask & self::GENERIC_READ) !== 0) {
            $mapped |= 0x00120089;
        }
        if (($mask & self::GENERIC_WRITE) !== 0) {
            $mapped |= 0x00120116;
        }
        if (($mask & self::GENERIC_EXECUTE) !== 0) {
            $mapped |= 0x001200A0;
        }
        if (($mask & self::GENERIC_ALL) !== 0) {
            $mapped |= 0x001F01FF;
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $ace
     * @return array<string, mixed>
     */
    private function step(int $index, array $ace, int $before, int $effect, int $after, bool $applicable, string $reason, string $trusteeMatch): array
    {
        return [
            'index' => $index,
            'step_id' => 'ace-step-'.($index + 1),
            'ace_id' => $ace['ace_id'] ?? 'ace-'.($index + 1),
            'evaluated' => $applicable,
            'reason' => $reason,
            'trustee_match' => $trusteeMatch,
            'type' => $ace['type'] ?? 'UNKNOWN',
            'trustee_sid' => $ace['trustee_sid'] ?? null,
            'relevant_mask' => $this->hex($effect),
            'mask_before' => $this->hex($before),
            'mask_effect' => $this->hex($effect),
            'mask_after' => $this->hex($after),
            'ace_applicability' => $applicable ? 'applicable' : 'skipped',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $normalized
     * @param  list<array<string, mixed>>  $steps
     * @param  array<string, mixed>|null  $mapping
     * @param  list<string>  $limitations
     * @return array<string, mixed>
     */
    private function finish(array $context, array $normalized, array $steps, string $outcome, string $rule, int $remaining, ?array $mapping, array $limitations): array
    {
        $trace = [
            'rule_set_id' => $context['rule_set_id'],
            'rule_set_revision' => $context['rule_set_revision'],
            'authority_baseline_id' => $context['authority_baseline_id'],
            'scenario_revision_id' => $context['scenario_revision_id'],
            'run_id' => $context['run_id'],
            'seed' => $context['seed'],
            'ordered_actions' => $context['ordered_actions'] ?? [],
            'normalized_input_digest' => $this->digest($normalized),
            'principal' => $normalized['principal'] ?? null,
            'token_user_sid' => $normalized['token_user_sid'] ?? null,
            'token_group_sids_and_attributes' => $normalized['token_groups'] ?? [],
            'declared_privileges' => $normalized['declared_privileges'] ?? [],
            'target_object' => $normalized['target_object'] ?? null,
            'object_type' => $normalized['object_type'] ?? null,
            'owner' => $normalized['security_descriptor']['owner'] ?? null,
            'requested_access_mask' => $normalized['requested_access_mask'] ?? null,
            'generic_mapping_result' => $mapping,
            'ordered_ace_steps' => $steps,
            'decisive_rule_id' => $rule,
            'final_outcome' => $outcome,
            'remaining_unresolved_mask' => $this->hex($remaining),
            'limitations' => $limitations,
            'source_claim_ids' => $context['source_claim_ids'],
            'evidence_origin' => 'SIMULATED',
        ];
        $digestPayload = $trace;
        unset($digestPayload['run_id']);
        $trace['output_digest'] = $this->digest($digestPayload);

        return $trace;
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn ($item) => $this->canonicalize($item), $value);
    }

    /** @param mixed $value */
    private function digest($value): string
    {
        return hash('sha256', json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function hex(int $mask): string
    {
        return sprintf('0x%08X', $mask & 0xFFFFFFFF);
    }
}
