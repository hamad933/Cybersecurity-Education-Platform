<?php

namespace Database\Seeders;

use App\Modules\Curriculum\Models\CurriculumPlacement;
use App\Modules\Enterprise\Models\EnterpriseBaselineRevision;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Learning\Models\MasteryRuleRevision;
use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Simulator\Authorization\WindowsAuthorizationDecisionEngine;
use App\Modules\Simulator\Models\ScenarioRevision;
use App\Modules\Simulator\Models\SimulatorRuleRevision;
use App\Modules\SourceGovernance\Models\SourceClaim;
use App\Modules\SourceGovernance\Models\SourceRecord;
use Illuminate\Database\Seeder;

class Vs001Seeder extends Seeder
{
    public function run(): void
    {
        $this->seedSources();
        KnowledgeUnit::query()->firstOrCreate(['id' => config('vs001.knowledge_unit_id')], [
            'title_ar' => 'تحليل قرارات صلاحيات ملفات Windows',
            'title_en' => 'Analyze Windows file authorization decisions',
        ]);

        $blocks = $this->lessonBlocks();
        $citations = config('vs001.required_claim_ids');
        LessonRevision::query()->firstOrCreate(
            ['knowledge_unit_id' => config('vs001.knowledge_unit_id'), 'revision' => 1],
            [
                'state' => 'published',
                'lock_version' => 4,
                'blocks' => $blocks,
                'citations' => $citations,
                'authority_baseline_id' => WindowsAuthorizationDecisionEngine::AUTHORITY_BASELINE,
                'content_digest' => $this->digest(['blocks' => $blocks, 'citations' => $citations]),
                'review_decision' => 'APPROVED',
                'published_at' => now(),
            ],
        );
        CurriculumPlacement::query()->firstOrCreate(
            ['capability_id' => config('vs001.capability_id'), 'knowledge_unit_id' => config('vs001.knowledge_unit_id'), 'revision' => 1],
            ['lifecycle' => ['reviewed_source', 'published_lesson', 'micro_practice', 'guided_simulator_lab', 'simulated_evidence', 'evidence_decision', 'mastery', 'failure_based_review']],
        );

        $snapshot = [
            'target' => 'Windows 11 24H2 x64 file securable object',
            'build_family' => '26100',
            'principal' => ['name' => 'Analyst-A', 'token_user_sid' => 'S-1-5-21-1000'],
            'groups' => [['sid' => 'S-1-5-32-545', 'enabled' => true, 'deny_only' => false]],
            'object' => ['path' => 'C:\\Institution\\Cases\\case.txt', 'type' => 'FILE'],
        ];
        $baseline = EnterpriseBaselineRevision::query()->firstOrCreate(
            ['baseline_id' => 'ENT-BASELINE-VS001', 'revision' => 1],
            ['state' => 'published', 'snapshot' => $snapshot, 'snapshot_digest' => $this->digest($snapshot), 'published_at' => now()],
        );

        $rulesBody = [
            'scope' => 'explicit ordered DACL subset for file objects',
            'rules' => ['RULE-NULL-DACL-ALLOW', 'RULE-ACE-DENY', 'RULE-ALLOW-COMPLETE', 'RULE-REMAINING-MASK-DENY'],
            'generic_mapping' => WindowsAuthorizationDecisionEngine::GENERIC_MAPPING,
            'unsupported' => ['privileges', 'conditional_aces', 'object_aces', 'integrity_labels', 'inheritance_expansion'],
        ];
        $rules = SimulatorRuleRevision::query()->firstOrCreate(
            ['rule_set_id' => config('vs001.rule_set_id'), 'revision' => 1],
            ['authority_baseline_id' => WindowsAuthorizationDecisionEngine::AUTHORITY_BASELINE, 'state' => 'approved', 'rules' => $rulesBody, 'digest' => $this->digest($rulesBody), 'approved_at' => now()],
        );
        $cases = $this->scenarioCases();
        ScenarioRevision::query()->firstOrCreate(
            ['scenario_id' => config('vs001.scenario_id'), 'revision' => 1],
            ['state' => 'published', 'rule_set_revision_id' => $rules->id, 'enterprise_baseline_revision_id' => $baseline->id, 'cases' => $cases, 'digest' => $this->digest($cases), 'published_at' => now()],
        );

        $practice = [
            'prompt_ar' => 'حلّل النتيجة والخطوة وACE الحاسمة وتأثير القناع باستخدام إجابة منظمة.',
            'case_id' => 'CASE-003-DENY-BEFORE-ALLOW',
            'choices' => ['ALLOW', 'DENY', 'INSUFFICIENT_STATE', 'UNSUPPORTED_STATE'],
            'requires_rationale' => true,
            'bounded_to_one_decision' => true,
            'answer_key_version' => 2,
            'answer_key' => [
                'selected_outcome' => 'DENY',
                'decisive_step_id' => 'ace-step-1',
                'decisive_ace_id' => 'ACE-DENY-001',
                'relevant_requested_mask' => '0x00000001',
                'remaining_mask' => '0x00000001',
                'rationale_concepts' => [
                    ['رفض صريح', 'explicit deny'],
                    ['يسبق', 'الترتيب', 'ordered before'],
                ],
            ],
        ];
        MicroPractice::query()->firstOrCreate(
            ['practice_id' => 'MP-KU-AD-02-001', 'revision' => 1],
            ['capability_id' => config('vs001.capability_id'), 'knowledge_unit_id' => config('vs001.knowledge_unit_id'), 'definition' => $practice, 'digest' => $this->digest($practice)],
        );
        $mastery = [
            'accepted_positive' => 1,
            'accepted_negative' => 1,
            'accepted_unsupported_handling' => 1,
            'matching_replay' => true,
            'provenance_required' => true,
            'provisional' => true,
        ];
        MasteryRuleRevision::query()->firstOrCreate(
            ['rule_id' => config('vs001.mastery_rule_id'), 'revision' => 1],
            ['requirements' => $mastery, 'digest' => $this->digest($mastery), 'state' => 'approved'],
        );
    }

    private function seedSources(): void
    {
        $sources = [
            ['claim' => 'WIN-AUTH-002', 'title' => 'Windows 11 release information', 'url' => 'https://learn.microsoft.com/en-us/windows/release-health/windows11-release-information', 'segment' => 'Windows 11 current versions / Version 24H2 / OS build 26100', 'scope' => 'Target release and build-family baseline.', 'excluded' => 'Does not define access-check semantics.'],
            ['claim' => 'WIN-AUTH-003', 'title' => 'AccessCheck function', 'url' => 'https://learn.microsoft.com/en-us/windows/win32/api/securitybaseapi/nf-securitybaseapi-accesscheck', 'segment' => 'Parameters and Remarks', 'scope' => 'Descriptor/token relationship and input preconditions.', 'excluded' => 'No real AccessCheck invocation in VS-001.'],
            ['claim' => 'WIN-AUTH-004', 'title' => 'DACLs and ACEs', 'url' => 'https://learn.microsoft.com/en-us/windows/win32/secauthz/dacls-and-aces', 'segment' => 'ACE order, deny and allow behavior', 'scope' => 'Ordered explicit deny/allow and cumulative allow.', 'excluded' => 'Inheritance and conditional ACE expansion.'],
            ['claim' => 'WIN-AUTH-005', 'title' => 'SID attributes in an access token', 'url' => 'https://learn.microsoft.com/en-us/windows/win32/secauthz/sid-attributes-in-an-access-token', 'segment' => 'SE_GROUP_ENABLED and SE_GROUP_USE_FOR_DENY_ONLY', 'scope' => 'Enabled and deny-only token groups.', 'excluded' => 'Restricted tokens and claims.'],
            ['claim' => 'WIN-AUTH-006', 'title' => 'MapGenericMask function', 'url' => 'https://learn.microsoft.com/en-us/windows/win32/api/securitybaseapi/nf-securitybaseapi-mapgenericmask', 'segment' => 'Mapping generic access rights', 'scope' => 'Declared file-object generic mapping.', 'excluded' => 'Other object-type mappings.'],
            ['claim' => 'WIN-AUTH-007', 'title' => 'File security and access rights', 'url' => 'https://learn.microsoft.com/en-us/windows/win32/fileio/file-security-and-access-rights', 'segment' => 'Generic access rights mapping for files', 'scope' => 'File generic read/write/execute/all masks.', 'excluded' => 'Share permissions and remote systems.'],
        ];
        foreach ($sources as $entry) {
            $snapshot = ['url' => $entry['url'], 'segment' => $entry['segment'], 'scope' => $entry['scope'], 'reviewed_on' => '2026-07-22'];
            $source = SourceRecord::query()->firstOrCreate(
                ['exact_url' => $entry['url']],
                ['authority_class' => 'Technical Authority', 'title' => $entry['title'], 'sha256' => $this->digest($snapshot), 'review_status' => 'approved', 'metadata' => $snapshot],
            );
            SourceClaim::query()->firstOrCreate(
                ['claim_id' => $entry['claim']],
                ['source_record_id' => $source->id, 'segment_ref' => $entry['segment'], 'supported_scope' => $entry['scope'], 'excluded_semantics' => $entry['excluded'], 'assessment' => 'supported'],
            );
        }

        $internalPath = 'source-vault/manifests/semantic-refined/VS001_SOURCE_SELECTION_REFINED.tsv';
        $internal = SourceRecord::query()->firstOrCreate(
            ['relative_path' => $internalPath],
            [
                'authority_class' => 'Internal Reviewed Support',
                'title' => 'VS-001 reviewed internal source selection',
                'sha256' => 'eec190c37820c569a531409b2e67c66ed1e4739bacb6e2c9cf50083c5aa3776f',
                'review_status' => 'reviewed',
                'metadata' => ['source_id' => 'src-6c8ffdd9ff8911fb23f9ff97', 'selection_status' => 'REFINED_SOURCE_TRACE_NOT_APPROVED_BASELINE', 'reviewed_on' => '2026-07-22'],
            ],
        );
        SourceClaim::query()->firstOrCreate(
            ['claim_id' => 'VS001-SRC-003R-02'],
            [
                'source_record_id' => $internal->id,
                'segment_ref' => 'row:3; semantic_segment:SE-003-041',
                'supported_scope' => 'Reviewed internal topic scope for the token user SID and source selection only.',
                'excluded_semantics' => 'Not approved as Microsoft technical authority and never promoted automatically.',
                'assessment' => 'partial',
            ],
        );
    }

    /** @return list<array<string, string>> */
    private function lessonBlocks(): array
    {
        return [
            ['type' => 'heading', 'body' => 'كيف يُحلَّل قرار الوصول إلى ملف في Windows؟'],
            ['type' => 'paragraph', 'body' => 'يستخدم هذا الدرس نموذجًا تعليميًا محدودًا ومحدد الإصدار لتحليل DACL مرتبة. لا يستدعي النظام Windows ولا AccessCheck الحقيقي.'],
            ['type' => 'rules', 'body' => 'تحقق من SID المستخدم والمجموعات وسماتها، ثم حوّل الحقوق العامة فقط باستخدام خريطة FILE المعتمدة، وافحص ACEs بالترتيب. الرفض الحاسم يوقف القرار، والسماحات تتراكم حتى يصبح القناع المتبقي صفرًا.'],
            ['type' => 'callout', 'body' => 'النتائج الممكنة: ALLOW أو DENY أو INSUFFICIENT_STATE أو UNSUPPORTED_STATE. عند نقص المدخلات أو خروج الحالة عن النطاق لا تخمّن.'],
            ['type' => 'boundaries', 'body' => 'خارج النطاق: الامتيازات، ACEs الشرطية والكائنية، وراثة الصلاحيات، مستويات التكامل، المطالبات، والسياسات المركزية. جميع الأدلة الناتجة SIMULATED فقط.'],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function scenarioCases(): array
    {
        $base = [
            'principal' => 'S-1-5-21-1000',
            'token_user_sid' => 'S-1-5-21-1000',
            'token_groups' => [['sid' => 'S-1-5-32-545', 'enabled' => true, 'deny_only' => false]],
            'declared_privileges' => [],
            'target_object' => 'C:\\Institution\\Cases\\case.txt',
            'object_type' => 'FILE',
            'requested_access_mask' => '0x00000001',
            'security_descriptor' => ['owner' => 'S-1-5-21-500', 'dacl' => []],
        ];
        $case = fn (string $id, string $title, array $changes, string $expected): array => ['case_id' => $id, 'title_ar' => $title, 'expected' => $expected, 'input' => array_replace_recursive($base, $changes)];

        return [
            $case('CASE-001-EXPLICIT-ALLOW', 'سماح صريح للمستخدم', ['security_descriptor' => ['dacl' => [['type' => 'ACCESS_ALLOWED', 'trustee_sid' => 'S-1-5-21-1000', 'access_mask' => '0x00000001']]]], 'ALLOW'),
            $case('CASE-002-GROUP-ALLOW', 'سماح عبر مجموعة مفعّلة', ['security_descriptor' => ['dacl' => [['type' => 'ACCESS_ALLOWED', 'trustee_sid' => 'S-1-5-32-545', 'access_mask' => '0x00000001']]]], 'ALLOW'),
            $case('CASE-003-DENY-BEFORE-ALLOW', 'رفض يسبق سماحًا لاحقًا', ['security_descriptor' => ['dacl' => [['ace_id' => 'ACE-DENY-001', 'type' => 'ACCESS_DENIED', 'trustee_sid' => 'S-1-5-21-1000', 'access_mask' => '0x00000001'], ['ace_id' => 'ACE-ALLOW-002', 'type' => 'ACCESS_ALLOWED', 'trustee_sid' => 'S-1-5-21-1000', 'access_mask' => '0x00000001']]]], 'DENY'),
            $case('CASE-004-CUMULATIVE-ALLOW', 'تراكم سماحين', ['requested_access_mask' => '0x00000003', 'security_descriptor' => ['dacl' => [['type' => 'ACCESS_ALLOWED', 'trustee_sid' => 'S-1-5-21-1000', 'access_mask' => '0x00000001'], ['type' => 'ACCESS_ALLOWED', 'trustee_sid' => 'S-1-5-32-545', 'access_mask' => '0x00000002']]]], 'ALLOW'),
            $case('CASE-005-PARTIAL-MASK', 'يبقى جزء من القناع', ['requested_access_mask' => '0x00000003', 'security_descriptor' => ['dacl' => [['type' => 'ACCESS_ALLOWED', 'trustee_sid' => 'S-1-5-21-1000', 'access_mask' => '0x00000001']]]], 'DENY'),
            $case('CASE-006-NON-APPLICABLE', 'تجاوز ACE غير مطبقة', ['security_descriptor' => ['dacl' => [['type' => 'ACCESS_DENIED', 'trustee_sid' => 'S-1-5-21-1000', 'access_mask' => '0x00000001', 'inherit_only' => true], ['type' => 'ACCESS_ALLOWED', 'trustee_sid' => 'S-1-5-21-1000', 'access_mask' => '0x00000001']]]], 'ALLOW'),
            $case('CASE-007-MISSING-PRINCIPAL', 'معرّف مستخدم مفقود', ['principal' => null], 'INSUFFICIENT_STATE'),
            $case('CASE-008-MISSING-DESCRIPTOR', 'واصف أمان مفقود', ['security_descriptor' => null], 'INSUFFICIENT_STATE'),
            $case('CASE-009-MISSING-MAPPING', 'خريطة الحقوق العامة مفقودة', ['requested_access_mask' => '0x80000000', 'generic_mapping_id' => null], 'INSUFFICIENT_STATE'),
            $case('CASE-010-UNSUPPORTED-ACE', 'نوع ACE غير مدعوم', ['security_descriptor' => ['dacl' => [['type' => 'SYSTEM_AUDIT', 'trustee_sid' => 'S-1-5-21-1000', 'access_mask' => '0x00000001']]]], 'UNSUPPORTED_STATE'),
            $case('CASE-011-UNSUPPORTED-PRIVILEGE', 'امتياز خارج النطاق', ['declared_privileges' => ['SeBackupPrivilege']], 'UNSUPPORTED_STATE'),
            $case('CASE-012-DENY-ONLY-GROUP', 'مجموعة deny-only لا تمنح السماح', ['token_groups' => [['sid' => 'S-1-5-32-545', 'enabled' => false, 'deny_only' => true]], 'security_descriptor' => ['dacl' => [['type' => 'ACCESS_ALLOWED', 'trustee_sid' => 'S-1-5-32-545', 'access_mask' => '0x00000001']]]], 'DENY'),
        ];
    }

    /** @param mixed $value */
    private function digest($value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
