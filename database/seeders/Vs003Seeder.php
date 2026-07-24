<?php

namespace Database\Seeders;

use App\Modules\Curriculum\Models\CurriculumPlacement;
use App\Modules\Enterprise\Models\EnterpriseBaselineRevision;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Learning\Models\MasteryRuleRevision;
use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Simulator\Models\ScenarioRevision;
use App\Modules\Simulator\Models\SimulatorRuleRevision;
use App\Modules\SourceGovernance\Models\SourceClaim;
use App\Modules\SourceGovernance\Models\SourceRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Vs003Seeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'VS3-AUTH-001',
                'Advanced Audit Policy Configuration settings',
                'https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/plan/security-best-practices/advanced-audit-policy-configuration',
                'Audit Logon: 4624 successful; 4625 failed',
            ],
            [
                'VS3-AUTH-002',
                '4624(S) An account was successfully logged on',
                'https://learn.microsoft.com/en-us/previous-versions/windows/it-pro/windows-10/security/threat-protection/auditing/event-4624',
                'Logon Type and Network Information',
            ],
            [
                'VS3-AUTH-003',
                'NIST SP 800-61 Rev. 3',
                'https://csrc.nist.gov/pubs/sp/800/61/r3/final',
                'April 2025',
            ],
            [
                'VS3-AUTH-004',
                'NIST Cybersecurity Framework 2.0',
                'https://nvlpubs.nist.gov/nistpubs/SpecialPublications/NIST.SP.1300.pdf',
                'Respond Function',
            ],
        ] as [$claimId, $title, $url, $segment]) {
            $source = SourceRecord::query()->firstOrCreate(
                ['exact_url' => $url],
                [
                    'authority_class' => 'Technical Authority',
                    'title' => $title,
                    'sha256' => $this->digest([$url, $segment]),
                    'review_status' => 'approved',
                    'metadata' => [
                        'reviewed_on' => '2026-07-24',
                        'segment' => $segment,
                    ],
                ],
            );
            SourceClaim::query()->firstOrCreate(
                ['claim_id' => $claimId],
                [
                    'source_record_id' => $source->id,
                    'segment_ref' => $segment,
                    'supported_scope' => 'VS-003 bounded synthetic authentication telemetry and triage.',
                    'excluded_semantics' => 'No production telemetry, live incident response, legal conclusion, or automated containment.',
                    'assessment' => 'supported',
                ],
            );
        }

        KnowledgeUnit::query()->firstOrCreate(
            ['id' => config('vs003.knowledge_unit_id')],
            [
                'title_ar' => 'فرز شذوذات المصادقة باستخدام الشدة والنطاق والدليل',
                'title_en' => 'Triage authentication anomalies using severity, scope, and evidence',
            ],
        );
        $blocks = [
            ['type' => 'heading', 'body' => 'تحقيق شذوذات المصادقة (SIMULATED)'],
            ['type' => 'paragraph', 'body' => 'افصل جودة القياس عن صحة التنبيه، ودوّن الفرضية البديلة والبيانات الناقصة قبل إعلان حادث.'],
            ['type' => 'code', 'body' => '4625 failed logon; 4624 successful logon; timestamps are UTC; missing fields remain missing.'],
            ['type' => 'callout', 'body' => 'الاحتواء المقترح غير تنفيذي ويتطلب موافقة صريحة.'],
        ];
        LessonRevision::query()->firstOrCreate(
            ['knowledge_unit_id' => config('vs003.knowledge_unit_id'), 'revision' => 1],
            [
                'state' => 'published',
                'lock_version' => 1,
                'blocks' => $blocks,
                'citations' => config('vs003.required_claim_ids'),
                'authority_baseline_id' => config('vs003.authority_baseline_id'),
                'content_digest' => $this->digest($blocks),
                'review_decision' => 'APPROVED',
                'published_at' => now(),
            ],
        );
        CurriculumPlacement::query()->firstOrCreate(
            [
                'capability_id' => config('vs003.capability_id'),
                'knowledge_unit_id' => config('vs003.knowledge_unit_id'),
                'revision' => 1,
            ],
            [
                'lifecycle' => [
                    'reviewed_authority',
                    'published_lesson',
                    'structured_micro_practice',
                    'guided_simulator_lab',
                    'simulated_evidence',
                    'mastery',
                    'failure_based_review',
                ],
            ],
        );

        $baselinePayload = [
            'workspace' => 'Synthetic Windows authentication estate',
            'identities' => [
                ['id' => 'SIM-ANALYST', 'role' => 'triage_analyst'],
                ['id' => 'SIM-ADMIN', 'role' => 'privileged_account'],
            ],
            'devices' => [
                ['id' => 'WS-07', 'approved_path' => 'CORP-VPN'],
                ['id' => 'DC-01', 'approved_path' => 'DATACENTER'],
            ],
        ];
        $baseline = EnterpriseBaselineRevision::query()->firstOrCreate(
            ['baseline_id' => 'ENT-BASELINE-VS003', 'revision' => 1],
            [
                'state' => 'published',
                'snapshot' => $baselinePayload,
                'snapshot_digest' => $this->digest($baselinePayload),
                'published_at' => now(),
            ],
        );
        $rules = [
            'behavior_version' => config('vs003.behavior_version'),
            'supported_event_ids' => [4624, 4625],
            'clock' => 'UTC',
            'late_tolerance_seconds' => 120,
            'duplicate_policy' => 'retain_raw_exclude_from_detection_counts',
            'no_rule_language' => true,
        ];
        $rule = SimulatorRuleRevision::query()->firstOrCreate(
            ['rule_set_id' => config('vs003.rule_set_id'), 'revision' => 1],
            [
                'authority_baseline_id' => config('vs003.authority_baseline_id'),
                'state' => 'approved',
                'rules' => $rules,
                'digest' => $this->digest($rules),
                'approved_at' => now(),
            ],
        );

        $events = [
            [
                'id' => 'EVT-001',
                'event_id' => 4625,
                'occurred_at' => '2026-07-24T08:00:00Z',
                'computer' => 'WS-07',
                'account_sid' => 'S-1-5-21-SIM-ADMIN',
                'logon_type' => 3,
                'source_address' => '10.20.30.40',
            ],
            [
                'id' => 'EVT-002',
                'event_id' => 4625,
                'occurred_at' => '2026-07-24T08:00:30Z',
                'computer' => 'WS-07',
                'account_sid' => 'S-1-5-21-SIM-ADMIN',
                'logon_type' => 3,
                'source_address' => '10.20.30.40',
            ],
            [
                'id' => 'EVT-003',
                'event_id' => 4624,
                'occurred_at' => '2026-07-24T08:01:00Z',
                'computer' => 'WS-07',
                'account_sid' => 'S-1-5-21-SIM-ADMIN',
                'logon_type' => 3,
                'source_address' => '10.20.30.40',
            ],
            [
                'id' => 'EVT-002-DUP',
                'duplicate_of' => 'EVT-002',
                'event_id' => 4625,
                'occurred_at' => '2026-07-24T08:00:30Z',
                'computer' => 'WS-07',
                'account_sid' => 'S-1-5-21-SIM-ADMIN',
                'logon_type' => 3,
                'source_address' => '10.20.30.40',
            ],
            [
                'id' => 'EVT-004-LATE',
                'event_id' => 4625,
                'occurred_at' => '2026-07-24T07:50:00Z',
                'computer' => 'WS-07',
                'account_sid' => 'S-1-5-21-SIM-ADMIN',
                'logon_type' => 3,
                'source_address' => '10.20.30.40',
                'late' => true,
            ],
            [
                'id' => 'EVT-005-MISSING',
                'event_id' => 4625,
                'occurred_at' => '2026-07-24T08:02:00Z',
                'computer' => 'WS-07',
                'account_sid' => null,
                'logon_type' => 3,
                'source_address' => null,
            ],
            [
                'id' => 'EVT-006-CONTRADICT',
                'event_id' => 4624,
                'occurred_at' => '2026-07-24T08:03:00Z',
                'computer' => 'WS-07',
                'account_sid' => 'S-1-5-21-SIM-ADMIN',
                'logon_type' => 3,
                'source_address' => '10.20.30.40',
                'contradicts' => 'EVT-002',
            ],
            [
                'id' => 'EVT-007-UNSUPPORTED',
                'event_id' => 9999,
                'occurred_at' => '2026-07-24T08:04:00Z',
                'computer' => 'WS-07',
            ],
        ];
        $datasetId = (string) Str::uuid7();
        DB::table('vs003_telemetry_dataset_revisions')->insertOrIgnore([
            'id' => $datasetId,
            'dataset_id' => config('vs003.dataset_id'),
            'revision' => 1,
            'state' => 'published',
            'timezone' => 'UTC',
            'events' => json_encode($events, JSON_THROW_ON_ERROR),
            'digest' => $this->digest($events),
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $datasetRevisionId = (string) DB::table('vs003_telemetry_dataset_revisions')
            ->where('dataset_id', config('vs003.dataset_id'))
            ->where('revision', 1)
            ->value('id');
        $cases = [
            [
                'case_id' => 'VS3-BENIGN',
                'expected' => 'BENIGN_EXPLAINED',
                'event_ids' => ['EVT-001', 'EVT-002', 'EVT-002-DUP', 'EVT-003'],
                'expected_quality' => ['duplicate_count' => 1],
            ],
            [
                'case_id' => 'VS3-SUSPICIOUS',
                'expected' => 'SUSPICIOUS',
                'event_ids' => ['EVT-001', 'EVT-002'],
                'expected_quality' => ['duplicate_count' => 0],
            ],
            [
                'case_id' => 'VS3-INCIDENT',
                'expected' => 'INCIDENT_CONFIRMED',
                'event_ids' => ['EVT-001', 'EVT-002', 'EVT-004-LATE'],
                'expected_quality' => ['late_count' => 1],
            ],
            [
                'case_id' => 'VS3-INSUFFICIENT',
                'expected' => 'INSUFFICIENT_TELEMETRY',
                'event_ids' => ['EVT-005-MISSING', 'EVT-006-CONTRADICT'],
                'expected_quality' => ['missing_count' => 1, 'contradictory_count' => 1],
            ],
            [
                'case_id' => 'VS3-UNSUPPORTED',
                'expected' => 'UNSUPPORTED_STATE',
                'event_ids' => ['EVT-007-UNSUPPORTED'],
                'expected_quality' => ['unsupported_count' => 1],
            ],
        ];
        $scenario = ScenarioRevision::query()->firstOrCreate(
            ['scenario_id' => config('vs003.scenario_id'), 'revision' => 1],
            [
                'state' => 'published',
                'rule_set_revision_id' => $rule->id,
                'enterprise_baseline_revision_id' => $baseline->id,
                'cases' => $cases,
                'digest' => $this->digest($cases),
                'published_at' => now(),
            ],
        );
        foreach ($cases as $case) {
            DB::table('vs003_investigation_cases')->insertOrIgnore([
                'id' => (string) Str::uuid7(),
                'scenario_revision_id' => $scenario->id,
                'dataset_revision_id' => $datasetRevisionId,
                'case_id' => $case['case_id'],
                'expected_outcome' => $case['expected'],
                'definition' => json_encode($case, JSON_THROW_ON_ERROR),
                'digest' => $this->digest($case),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $practice = [
            'case_id' => 'VS3-SUSPICIOUS',
            'answer_key' => [
                'outcome' => 'SUSPICIOUS',
                'telemetry_health' => 'HEALTHY',
                'alternative_hypothesis' => 'legitimate_user_error',
            ],
            'rationale_concepts' => [
                ['evidence', 'دليل'],
                ['alternative', 'بديل'],
                ['telemetry_quality', 'جودة القياس'],
            ],
        ];
        MicroPractice::query()->firstOrCreate(
            ['practice_id' => config('vs003.practice_id'), 'revision' => 1],
            [
                'capability_id' => config('vs003.capability_id'),
                'knowledge_unit_id' => config('vs003.knowledge_unit_id'),
                'definition' => $practice,
                'digest' => $this->digest($practice),
            ],
        );
        $masteryRequirements = [
            'practice' => true,
            'telemetry_health' => true,
            'alternative_hypothesis' => true,
            'triage' => true,
            'custody' => true,
            'containment' => true,
            'verification' => true,
            'provenance' => true,
            'same_actor' => true,
        ];
        MasteryRuleRevision::query()->firstOrCreate(
            ['rule_id' => config('vs003.mastery_rule_id'), 'revision' => 1],
            [
                'requirements' => $masteryRequirements,
                'digest' => $this->digest($masteryRequirements),
                'state' => 'approved',
            ],
        );
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode(
            $this->canonical($value),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    private function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonical($item), $value);
        }
        ksort($value);

        return array_map(fn (mixed $item): mixed => $this->canonical($item), $value);
    }
}
