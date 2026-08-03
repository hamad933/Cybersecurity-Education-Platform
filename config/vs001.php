<?php

return [
    'slice_id' => 'VS-001',
    'capability_id' => 'CAP-D03-03-01',
    'knowledge_unit_id' => 'KU-AD-02',
    'authority_baseline_id' => 'WIN11-24H2-26100-FILE-AUTHZ-V1',
    'rule_set_id' => 'WIN-FILE-DACL-SUBSET',
    'generic_mapping_id' => 'WINDOWS11_24H2_FILE_V1',
    'scenario_id' => 'VS001-WINDOWS-FILE-AUTHZ',
    'mastery_rule_id' => 'MASTER-KU-AD-02-V1',
    'required_claim_ids' => [
        'WIN-AUTH-002',
        'WIN-AUTH-003',
        'WIN-AUTH-004',
        'WIN-AUTH-005',
        'WIN-AUTH-006',
        'WIN-AUTH-007',
    ],
    'failure_classes' => [
        'incorrect_decision',
        'missed_decisive_ace',
        'requested_mask_error',
        'unsupported_state_guess',
        'missing_provenance',
        'failed_retention',
        'rationale_missing',
        'replay_mismatch',
        'wrong_group_attribute',
    ],
];
