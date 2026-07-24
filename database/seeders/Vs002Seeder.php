<?php

namespace Database\Seeders;

use App\Modules\Curriculum\Models\CurriculumPlacement;
use App\Modules\Enterprise\Models\EnterpriseBaselineRevision;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Learning\Models\MasteryRuleRevision;
use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Simulator\Models\AuthorizationPolicyRevision;
use App\Modules\Simulator\Models\EndpointContractRevision;
use App\Modules\Simulator\Models\ScenarioRevision;
use App\Modules\Simulator\Models\SimulatorRuleRevision;
use App\Modules\SourceGovernance\Models\SourceClaim;
use App\Modules\SourceGovernance\Models\SourceRecord;
use Illuminate\Database\Seeder;

class Vs002Seeder extends Seeder
{
    public function run(): void
    {
        $this->seedAuthorities();
        KnowledgeUnit::query()->firstOrCreate(['id' => config('vs002.knowledge_unit_id')], [
            'title_ar' => 'اختبار حدود الثقة والتفويض على مستوى كائنات Web وAPI',
            'title_en' => 'Test Web and API trust boundaries and object-level authorization',
        ]);

        $blocks = $this->lessonBlocks();
        $citations = config('vs002.required_claim_ids');
        LessonRevision::query()->firstOrCreate(
            ['knowledge_unit_id' => config('vs002.knowledge_unit_id'), 'revision' => 1],
            [
                'state' => 'published',
                'lock_version' => 4,
                'blocks' => $blocks,
                'citations' => $citations,
                'authority_baseline_id' => config('vs002.authority_baseline_id'),
                'content_digest' => $this->digest(['blocks' => $blocks, 'citations' => $citations]),
                'review_decision' => 'APPROVED',
                'published_at' => now(),
            ],
        );
        CurriculumPlacement::query()->firstOrCreate(
            ['capability_id' => config('vs002.capability_id'), 'knowledge_unit_id' => config('vs002.knowledge_unit_id'), 'revision' => 1],
            ['lifecycle' => ['reviewed_authority', 'published_lesson', 'structured_micro_practice', 'guided_web_api_simulator', 'security_finding', 'remediation_revision', 'verification', 'simulated_evidence', 'mastery', 'failure_based_review']],
        );

        $snapshot = [
            'workspace' => 'Synthetic Institution Case File Service',
            'actors' => [
                ['id' => 'SIM-ALICE', 'server_role' => 'user'],
                ['id' => 'SIM-BOB', 'server_role' => 'user'],
                ['id' => 'SIM-ADMIN', 'server_role' => 'admin'],
            ],
            'resources' => [
                ['id' => 'CF-ALICE-001', 'owner_id' => 'SIM-ALICE', 'classification' => 'synthetic_training'],
                ['id' => 'CF-BOB-001', 'owner_id' => 'SIM-BOB', 'classification' => 'synthetic_training'],
            ],
            'network' => 'localhost-synthetic-only',
        ];
        $baseline = EnterpriseBaselineRevision::query()->firstOrCreate(
            ['baseline_id' => 'ENT-BASELINE-VS002', 'revision' => 1],
            ['state' => 'published', 'snapshot' => $snapshot, 'snapshot_digest' => $this->digest($snapshot), 'published_at' => now()],
        );

        $rulesBody = [
            'behavior_version' => 'web_authorization_v1',
            'trust_boundaries' => ['client_to_http', 'route_normalization', 'authentication_context', 'resource_lookup', 'authorization_policy', 'response_serialization', 'security_finding'],
            'outcomes' => ['ALLOW', 'DENY', 'UNAUTHENTICATED', 'NOT_FOUND', 'INSUFFICIENT_STATE', 'UNSUPPORTED_STATE'],
            'default' => 'DENY',
            'no_expression_language' => true,
        ];
        $rules = SimulatorRuleRevision::query()->firstOrCreate(
            ['rule_set_id' => config('vs002.rule_set_id'), 'revision' => 1],
            ['authority_baseline_id' => config('vs002.authority_baseline_id'), 'state' => 'approved', 'rules' => $rulesBody, 'digest' => $this->digest($rulesBody), 'approved_at' => now()],
        );
        $contractBody = [
            'method' => 'GET',
            'route_template' => '/api/case-files/{caseFileId}',
            'requested_action' => 'case_file.read',
            'allowed_request_fields' => [],
            'response_shape_id' => 'CASEFILE-SAFE-V1',
            'allowed_response_fields' => config('vs002.allowed_response_fields'),
        ];
        $contract = EndpointContractRevision::query()->firstOrCreate(
            ['contract_id' => config('vs002.endpoint_contract_id'), 'revision' => 1],
            [
                'state' => 'published',
                'method' => $contractBody['method'],
                'route_template' => $contractBody['route_template'],
                'requested_action' => $contractBody['requested_action'],
                'allowed_request_fields' => $contractBody['allowed_request_fields'],
                'response_shape_id' => $contractBody['response_shape_id'],
                'allowed_response_fields' => $contractBody['allowed_response_fields'],
                'authority_baseline_id' => config('vs002.authority_baseline_id'),
                'digest' => $this->digest($contractBody),
                'published_at' => now(),
            ],
        );
        $vulnerableRules = [
            'behavior_version' => 'web_authorization_v1',
            'default' => 'DENY',
            'subject_source' => 'server_session',
            'allow' => ['any_authenticated_subject_after_id_lookup'],
            'ownership_check' => 'MISSING',
            'client_role_authoritative' => false,
            'response_shape' => config('vs002.allowed_response_fields'),
        ];
        AuthorizationPolicyRevision::query()->firstOrCreate(
            ['policy_id' => config('vs002.policy_id'), 'revision' => 1],
            ['state' => 'published', 'mode' => 'vulnerable', 'rules' => $vulnerableRules, 'source_claim_ids' => config('vs002.required_claim_ids'), 'digest' => $this->digest($vulnerableRules), 'published_at' => now()],
        );
        $unsupportedRules = ['behavior_version' => 'unsupported', 'type' => 'dynamic_expression', 'expression' => null, 'supported' => false];
        AuthorizationPolicyRevision::query()->firstOrCreate(
            ['policy_id' => config('vs002.policy_id'), 'revision' => 3],
            ['state' => 'published', 'mode' => 'unsupported', 'rules' => $unsupportedRules, 'source_claim_ids' => ['WEB-AUTH-003'], 'digest' => $this->digest($unsupportedRules), 'published_at' => now()],
        );

        $cases = $this->scenarioCases();
        ScenarioRevision::query()->firstOrCreate(
            ['scenario_id' => config('vs002.scenario_id'), 'revision' => 1],
            ['state' => 'published', 'rule_set_revision_id' => $rules->id, 'enterprise_baseline_revision_id' => $baseline->id, 'cases' => $cases, 'digest' => $this->digest(['contract' => $contract->id, 'cases' => $cases]), 'published_at' => now()],
        );

        $practice = [
            'prompt_ar' => 'حلّل طلب Bob لملف تملكه Alice وحدد موضع غياب التحكم والقرار والاستجابة وحقل الكشف الآمن.',
            'case_id' => 'CASE-WEB-002',
            'answer_key_version' => 1,
            'answer_key' => [
                'actor' => 'SIM-BOB',
                'resource_owner' => 'SIM-ALICE',
                'requested_action' => 'case_file.read',
                'missing_trust_boundary' => 'authorization_policy',
                'expected_policy_decision' => 'DENY',
                'expected_http_response_class' => '4xx',
                'decisive_rule' => 'WEB-RULE-CROSS-OWNER-DENY',
                'safe_detection_field' => 'trace_digest',
                'rationale_concepts' => [
                    ['ownership', 'الملكية', 'المالك'],
                    ['server-side', 'الخادم', 'server'],
                    ['deny', 'رفض'],
                ],
            ],
        ];
        MicroPractice::query()->firstOrCreate(
            ['practice_id' => 'MP-KU-D05-004-001', 'revision' => 1],
            ['capability_id' => config('vs002.capability_id'), 'knowledge_unit_id' => config('vs002.knowledge_unit_id'), 'definition' => $practice, 'digest' => $this->digest($practice)],
        );
        $mastery = [
            'positive_authorization' => true,
            'negative_cross_owner' => true,
            'missing_control_identified' => true,
            'accepted_finding' => true,
            'remediation_revision' => true,
            'verification_run' => true,
            'safe_rendering_and_provenance' => true,
            'matching_replay' => true,
            'same_actor' => true,
        ];
        MasteryRuleRevision::query()->firstOrCreate(
            ['rule_id' => config('vs002.mastery_rule_id'), 'revision' => 1],
            ['requirements' => $mastery, 'digest' => $this->digest($mastery), 'state' => 'approved'],
        );
    }

    private function seedAuthorities(): void
    {
        $sources = [
            ['claim' => 'WEB-AUTH-001', 'title' => 'RFC 9110 HTTP Semantics', 'url' => 'https://datatracker.ietf.org/doc/html/rfc9110', 'segment' => 'Sections 3, 9, 11, and 15', 'scope' => 'HTTP method, target resource, authentication framework, and response status semantics.', 'excluded' => 'Does not define application object ownership.'],
            ['claim' => 'WEB-AUTH-002', 'title' => 'OWASP API1:2023 Broken Object Level Authorization', 'url' => 'https://owasp.org/API-Security/editions/2023/en/0xa1-broken-object-level-authorization/', 'segment' => 'Is the API Vulnerable and How To Prevent', 'scope' => 'Object-level checks for endpoints receiving object IDs.', 'excluded' => 'No live exploitation or protocol specification.'],
            ['claim' => 'WEB-AUTH-003', 'title' => 'OWASP Authorization Cheat Sheet', 'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html', 'segment' => 'Deny by Default and Validate Permissions on Every Request', 'scope' => 'Default deny and per-request authorization.', 'excluded' => 'No full production access-control architecture.'],
            ['claim' => 'WEB-AUTH-004', 'title' => 'Laravel 13.x Authorization', 'url' => 'https://laravel.com/docs/13.x/authorization', 'segment' => 'Introduction and Authorizing Actions', 'scope' => 'Authentication is distinct from action/resource authorization.', 'excluded' => 'No public API or token architecture.'],
            ['claim' => 'WEB-AUTH-005', 'title' => 'Laravel 13.x Validation', 'url' => 'https://laravel.com/docs/13.x/validation', 'segment' => 'Validation rules and error responses', 'scope' => 'Bounded server-side request validation.', 'excluded' => 'No claim about every framework rule.'],
            ['claim' => 'WEB-AUTH-006', 'title' => 'Vue Security', 'url' => 'https://vuejs.org/guide/best-practices/security.html', 'segment' => 'HTML content, untrusted templates, HTML and URL injection', 'scope' => 'Interpolation escaping and avoidance of untrusted raw HTML.', 'excluded' => 'No sanitizer or CSP claim.'],
            ['claim' => 'WEB-AUTH-007', 'title' => 'WHATWG Fetch Standard', 'url' => 'https://fetch.spec.whatwg.org/', 'segment' => 'Origin header and request origin', 'scope' => 'Origin as explicit normalized request context.', 'excluded' => 'No CORS, cookie, or credential enforcement claim.'],
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
    }

    /** @return list<array{type:string,body:string}> */
    private function lessonBlocks(): array
    {
        return [
            ['type' => 'heading', 'body' => 'من المصادقة إلى قرار التفويض على كائن API'],
            ['type' => 'paragraph', 'body' => 'المصادقة تثبت سياق الجلسة، لكنها لا تمنح تلقائياً حق قراءة كل مورد. قرار التفويض يحتاج subject وaction وresource وعلاقة الملكية أو دوراً صريحاً من الخادم.'],
            ['type' => 'request', 'body' => "GET /api/case-files/CF-ALICE-001\nHost: local.training.invalid\nX-Synthetic-Actor: SIM-BOB"],
            ['type' => 'rules', 'body' => 'المسار الآمن يطبّع المعرّف، يتحقق من الجلسة، يجلب المورد، ثم يطبق سياسة افتراضية بالرفض تسمح للمالك أو لدور إداري صريح من الخادم فقط. حقول role أوowner القادمة من العميل لا تصبح مدخلات موثوقة.'],
            ['type' => 'code', 'body' => "vulnerable: authenticated -> load(id) -> return\nsecure: subject + action + resource + ownership/server-role -> decision"],
            ['type' => 'response', 'body' => "HTTP/1.1 403 Forbidden\nContent-Type: application/json\n{\"decision\":\"DENY\",\"origin\":\"SIMULATED\"}"],
            ['type' => 'log', 'body' => 'request_id, correlation_id, actor_id, resource_id, policy_revision_id, decisive_rule_id, trace_digest; no password, cookie, token, or body.'],
            ['type' => 'callout', 'body' => 'الإصلاح ينشئ Policy Revision جديدة غير قابلة للتعديل، ثم يعيد الطلب نفسه ويربط Finding بالتشغيل الضعيف والتشغيل المرفوض بعد الإصلاح.'],
            ['type' => 'code', 'body' => '<img src=x onerror="document.documentElement.dataset.xssProbe=\'executed\'">'],
            ['type' => 'boundaries', 'body' => 'هذه محاكاة محلية وهمية: لا target حي، لا enumeration، لا scanner، لا موصل خارجي، ولا ادعاء لسلوك CORS أوcookies أوCSP أوOAuth أوmiddleware غير المختبر.'],
        ];
    }

    /** @return list<array<string,mixed>> */
    private function scenarioCases(): array
    {
        $base = [
            'actor_id' => 'SIM-ALICE',
            'session_state' => 'authenticated',
            'server_role' => 'user',
            'method' => 'GET',
            'route_template' => '/api/case-files/{caseFileId}',
            'resource_id' => 'CF-ALICE-001',
            'resource_exists' => true,
            'target_owner_id' => 'SIM-ALICE',
            'requested_action' => 'case_file.read',
            'client_supplied_role' => null,
            'client_supplied_owner_id' => null,
            'serializer_requested_fields' => ['id', 'title', 'status', 'owner_display'],
            'origin_context' => 'synthetic-same-origin',
        ];
        $case = fn (string $id, string $title, int $policyRevision, array $changes, string $expected): array => [
            'case_id' => $id,
            'title_ar' => $title,
            'policy_revision' => $policyRevision,
            'expected' => $expected,
            'input' => array_replace($base, $changes),
        ];

        return [
            $case('CASE-WEB-001', 'Alice تقرأ ملفها', 1, [], 'ALLOW'),
            $case('CASE-WEB-002', 'Bob يقرأ ملف Alice تحت السياسة الضعيفة', 1, ['actor_id' => 'SIM-BOB'], 'ALLOW_WITH_FINDING'),
            $case('CASE-WEB-003', 'Bob يقرأ ملف Alice بعد الإصلاح', 2, ['actor_id' => 'SIM-BOB'], 'DENY'),
            $case('CASE-WEB-004', 'طلب غير مصادق', 1, ['actor_id' => '', 'session_state' => 'unauthenticated'], 'UNAUTHENTICATED'),
            $case('CASE-WEB-005', 'دور إداري صريح من الخادم', 2, ['actor_id' => 'SIM-ADMIN', 'server_role' => 'admin'], 'ALLOW'),
            $case('CASE-WEB-006', 'Bob يرسل role=admin من العميل', 2, ['actor_id' => 'SIM-BOB', 'client_supplied_role' => 'admin', 'client_supplied_owner_id' => 'SIM-BOB'], 'DENY'),
            $case('CASE-WEB-007', 'الفعل والطريقة لا يطابقان العقد', 2, ['method' => 'POST', 'requested_action' => 'case_file.update'], 'DENY'),
            $case('CASE-WEB-008', 'المورد غير موجود', 1, ['resource_id' => 'CF-MISSING-404', 'resource_exists' => false], 'NOT_FOUND'),
            $case('CASE-WEB-009', 'معرّف المورد مفقود أو غير صالح', 1, ['resource_id' => '../'], 'INSUFFICIENT_STATE'),
            $case('CASE-WEB-010', 'نوع السياسة غير مدعوم', 3, [], 'UNSUPPORTED_STATE'),
            $case('CASE-WEB-011', 'محاولة Serializer لإضافة حقل غير معتمد', 1, ['serializer_requested_fields' => ['id', 'title', 'status', 'owner_display', 'internal_notes', 'session_token']], 'ALLOW_WITH_SAFE_FINDING'),
            $case('CASE-WEB-012', 'إعادة تشغيل مثبتة للمسار الضعيف', 1, ['actor_id' => 'SIM-BOB'], 'ALLOW_WITH_FINDING'),
        ];
    }

    private function digest(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
