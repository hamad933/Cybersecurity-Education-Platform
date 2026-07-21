<?php

return [
    'profile' => env('APP_PROFILE', 'local'),
    'auth_bypass' => false,
    'blob_disk' => env('BLOB_DISK', 'local'),
    'blob_root' => env('BLOB_ROOT', 'blobs'),
    'audit_metadata_max_bytes' => 4096,
    'outbox_payload_max_bytes' => 16_384,
    'modules' => [
        'MOD-PLT' => ['name' => 'Platform Services', 'dependencies' => []],
        'MOD-IAM' => ['name' => 'Platform Identity and Access', 'dependencies' => ['MOD-PLT']],
        'MOD-SRC' => ['name' => 'Source Governance', 'dependencies' => ['MOD-PLT']],
        'MOD-KNO' => ['name' => 'Canonical Knowledge', 'dependencies' => ['MOD-SRC', 'MOD-PLT']],
        'MOD-CUR' => ['name' => 'Curriculum', 'dependencies' => ['MOD-KNO', 'MOD-PLT']],
        'MOD-ENT' => ['name' => 'Enterprise Catalog', 'dependencies' => ['MOD-PLT']],
        'MOD-SIM' => ['name' => 'Institutional Simulator', 'dependencies' => ['MOD-ENT', 'MOD-CUR', 'MOD-PLT']],
        'MOD-EVD' => ['name' => 'Evidence', 'dependencies' => ['MOD-PLT']],
        'MOD-LRN' => ['name' => 'Learning and Mastery', 'dependencies' => ['MOD-CUR', 'MOD-KNO', 'MOD-PLT']],
        'MOD-AIB' => ['name' => 'Manual AI Bridge', 'dependencies' => ['MOD-SRC', 'MOD-KNO', 'MOD-CUR', 'MOD-PLT']],
    ],
];
