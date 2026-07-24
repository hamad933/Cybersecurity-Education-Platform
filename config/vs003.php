<?php

return [
    'slice_id' => 'VS-003',
    'capability_id' => 'CAP-D09-01-02',
    'knowledge_unit_id' => 'KU-D09-002',
    'authority_baseline_id' => 'WINDOWS-AUTH-TELEMETRY-IR-2026-07-24-V1',
    'dataset_id' => 'VS003-WINDOWS-AUTH-TELEMETRY',
    'rule_set_id' => 'VS003-AUTH-ANOMALY-RULES',
    'behavior_version' => 'auth_anomaly_v1',
    'scenario_id' => 'VS003-AUTHENTICATION-ANOMALY-INVESTIGATION',
    'mastery_rule_id' => 'MASTER-KU-D09-002-V1',
    'practice_id' => 'MP-KU-D09-002-001',
    'case_ids' => [
        'VS3-BENIGN',
        'VS3-SUSPICIOUS',
        'VS3-INCIDENT',
        'VS3-INSUFFICIENT',
        'VS3-UNSUPPORTED',
    ],
    'outcomes' => [
        'BENIGN_EXPLAINED',
        'SUSPICIOUS',
        'INCIDENT_CONFIRMED',
        'INSUFFICIENT_TELEMETRY',
        'UNSUPPORTED_STATE',
    ],
    'telemetry_health_values' => ['HEALTHY', 'DEGRADED', 'UNSUPPORTED'],
    'alternative_hypotheses' => [
        'legitimate_user_error',
        'legitimate_success_after_failures',
        'telemetry_gap',
    ],
    'required_claim_ids' => [
        'VS3-AUTH-001',
        'VS3-AUTH-002',
        'VS3-AUTH-003',
        'VS3-AUTH-004',
    ],
    'failure_classes' => [
        'wrong_triage',
        'telemetry_quality_missed',
        'alternative_hypothesis_missed',
        'missing_provenance',
        'control_verification_missed',
    ],
];
