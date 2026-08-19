<?php

declare(strict_types=1);

namespace Tests\Unit\Simulator\ScenarioLab;

use App\Modules\Simulator\ScenarioLab\Application\ScenarioLabAuthoringService;
use App\Modules\Simulator\ScenarioLab\Domain\DefinitionStatus;
use App\Modules\Simulator\ScenarioLab\Domain\LabDefinition;
use App\Modules\Simulator\ScenarioLab\Domain\ScenarioDefinition;
use DomainException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ScenarioLabAuthoringServiceTest extends TestCase
{
    private ScenarioLabAuthoringService $authoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authoring = new ScenarioLabAuthoringService;
    }

    #[Test]
    public function scenario_definition_is_portable_validated_versioned_and_publishable(): void
    {
        $draft = $this->scenarioDraft();
        $report = $this->authoring->validateScenario($draft);

        $this->assertTrue($report->isValid(), implode("\n", $report->errors));
        $published = $this->authoring->publishScenario($draft);
        $this->assertSame(DefinitionStatus::PUBLISHED, $published->status);
        $this->assertSame(1, $published->revision);
        $this->assertNotNull($published->digest);
        $this->assertSame(64, strlen((string) $published->digest));

        $nextDraft = $this->authoring->newScenarioDraft($published);
        $this->assertSame(DefinitionStatus::DRAFT, $nextDraft->status);
        $this->assertSame(2, $nextDraft->revision);
        $this->assertNull($nextDraft->digest);
    }

    #[Test]
    public function scenario_environment_contract_rejects_fixed_enterprise_or_baseline_lineage(): void
    {
        $draft = $this->authoring->draftScenario(
            definitionId: 'scenario-fixed-lineage',
            slug: 'fixed-lineage',
            titleAr: 'سيناريو غير قابل للنقل',
            environmentContract: [
                'required_capabilities' => ['identity-authentication'],
                'baseline_id' => 'baseline-123',
            ],
            phases: $this->phases(),
            orchestration: $this->orchestration(),
            validationRules: $this->rules(),
        );

        $report = $this->authoring->validateScenario($draft);
        $this->assertFalse($report->isValid());
        $this->assertStringContainsString('portable', implode(' ', $report->errors));
    }

    #[Test]
    public function reusable_lab_supports_task_graph_policies_and_scenario_module_reference_without_creating_runtime_state(): void
    {
        $lab = $this->authoring->publishLab($this->labDraft());
        $scenario = $this->authoring->attachLabModuleReference(
            $this->scenarioDraft(),
            $lab,
            'triage-module',
            'containment',
            ['required_role' => 'analyst'],
        );

        $this->assertSame(DefinitionStatus::DRAFT, $scenario->status);
        $this->assertCount(1, $scenario->labModuleReferences);
        $reference = $scenario->labModuleReferences[0];
        $this->assertSame($lab->definitionId, $reference['lab_definition_id']);
        $this->assertSame($lab->revision, $reference['lab_revision']);
        $this->assertSame($lab->digest, $reference['lab_digest']);
        $this->assertArrayNotHasKey('run_id', $reference);
        $this->assertArrayNotHasKey('lab_module_instance_id', $reference);
        $this->assertArrayNotHasKey('standalone_lab_run_id', $reference);
        $this->assertTrue($this->authoring->validateScenario($scenario)->isValid());
    }

    #[Test]
    public function scenario_and_lab_remain_distinct_definition_types(): void
    {
        $scenario = $this->scenarioDraft();
        $lab = $this->labDraft();

        $this->assertInstanceOf(ScenarioDefinition::class, $scenario);
        $this->assertInstanceOf(LabDefinition::class, $lab);
        $this->assertNotSame($scenario::class, $lab::class);
    }

    #[Test]
    public function published_revisions_are_immutable_and_module_keys_are_unique(): void
    {
        $lab = $this->authoring->publishLab($this->labDraft());
        $scenario = $this->authoring->attachLabModuleReference(
            $this->scenarioDraft(),
            $lab,
            'triage-module',
        );

        $this->expectException(DomainException::class);
        $this->authoring->attachLabModuleReference($scenario, $lab, 'triage-module');
    }

    #[Test]
    public function cyclic_lab_task_graph_cannot_be_published(): void
    {
        $draft = $this->authoring->draftLab(
            definitionId: 'lab-cycle',
            slug: 'cycle-lab',
            titleAr: 'مختبر دورة غير صالحة',
            purpose: 'اختبار التحقق من الرسم البياني للمهام.',
            environment: [
                'mode' => 'PORTABLE_CONTRACT',
                'required_capabilities' => ['endpoint-analysis'],
            ],
            tasks: [
                ['key' => 'a', 'title' => 'أ', 'depends_on' => ['b']],
                ['key' => 'b', 'title' => 'ب', 'depends_on' => ['a']],
            ],
            policies: ['guidance_mode' => 'GUIDED', 'participation_mode' => 'SOLO'],
            usageModes: ['STANDALONE', 'SCENARIO_MODULE'],
            validationRules: $this->rules(),
        );

        $report = $this->authoring->validateLab($draft);
        $this->assertFalse($report->isValid());
        $this->assertStringContainsString('acyclic', implode(' ', $report->errors));

        $this->expectException(DomainException::class);
        $this->authoring->publishLab($draft);
    }

    #[Test]
    public function lab_definition_rejects_external_execution_infrastructure(): void
    {
        $draft = $this->authoring->draftLab(
            definitionId: 'lab-external-runtime',
            slug: 'external-runtime',
            titleAr: 'مختبر غير صالح للبنية الخارجية',
            purpose: 'إثبات حدود محرك المحاكاة الداخلي.',
            environment: [
                'mode' => 'ISOLATED',
                'simulated_capabilities' => ['terminal'],
                'docker_image' => 'forbidden/example:latest',
            ],
            tasks: [['key' => 'inspect', 'title' => 'فحص', 'depends_on' => []]],
            policies: ['guidance_mode' => 'UNGUIDED', 'participation_mode' => 'SOLO'],
            usageModes: ['STANDALONE'],
            validationRules: $this->rules(),
        );

        $report = $this->authoring->validateLab($draft);
        $this->assertFalse($report->isValid());
        $this->assertStringContainsString('external', strtolower(implode(' ', $report->errors)));
    }

    private function scenarioDraft(): ScenarioDefinition
    {
        return $this->authoring->draftScenario(
            definitionId: 'scenario-incident-response',
            slug: 'incident-response',
            titleAr: 'سيناريو الاستجابة للحوادث',
            environmentContract: [
                'required_capabilities' => ['identity-authentication', 'endpoint-analysis', 'telemetry-query'],
                'required_devices' => ['analyst-workstation'],
                'state_requirements' => ['directory_service' => 'degraded'],
            ],
            phases: $this->phases(),
            orchestration: $this->orchestration(),
            roles: [
                ['key' => 'analyst', 'title' => 'محلل الاستجابة'],
                ['key' => 'lead', 'title' => 'قائد الحادث'],
            ],
            policies: ['participation_mode' => 'ROLE_BASED'],
            validationRules: $this->rules(),
            overview: ['objective' => 'احتواء نشاط هوية مشتبه به والتحقق من السبب الجذري.'],
        );
    }

    private function labDraft(): LabDefinition
    {
        return $this->authoring->draftLab(
            definitionId: 'lab-endpoint-triage',
            slug: 'endpoint-triage',
            titleAr: 'مختبر فرز نقطة النهاية',
            purpose: 'تدريب قابل لإعادة الاستخدام على فرز نقطة نهاية داخل حالة محاكاة.',
            environment: [
                'mode' => 'PORTABLE_CONTRACT',
                'required_capabilities' => ['endpoint-analysis', 'file-system', 'event-viewer'],
            ],
            tasks: [
                ['key' => 'collect', 'title' => 'جمع المؤشرات', 'depends_on' => []],
                ['key' => 'classify', 'title' => 'تصنيف المؤشرات', 'depends_on' => ['collect']],
                ['key' => 'contain', 'title' => 'تحديد إجراء الاحتواء', 'depends_on' => ['classify']],
            ],
            policies: ['guidance_mode' => 'SELECTABLE', 'participation_mode' => 'SELECTABLE'],
            usageModes: ['STANDALONE', 'SCENARIO_MODULE'],
            validationRules: [
                ['key' => 'collection-complete', 'criterion' => 'Required simulated indicators were collected.'],
                ['key' => 'classification-supported', 'criterion' => 'Classification is supported by the simulated telemetry.'],
            ],
            knowledgeLinks: ['knowledge:endpoint-triage'],
            initialState: ['endpoint_health' => 'DEGRADED'],
            toolRequirements: ['terminal', 'event-viewer'],
            availableActions: ['inspect-process', 'query-events', 'tag-indicator'],
        );
    }

    /** @return list<array<string, mixed>> */
    private function phases(): array
    {
        return [
            ['key' => 'detection', 'title' => 'الاكتشاف', 'ordinal' => 1],
            ['key' => 'containment', 'title' => 'الاحتواء', 'ordinal' => 2],
            ['key' => 'recovery', 'title' => 'الاستعادة', 'ordinal' => 3],
        ];
    }

    /** @return array<string, mixed> */
    private function orchestration(): array
    {
        return [
            'entry_phase' => 'detection',
            'timeline' => [
                ['at' => 'T+00', 'event' => 'initial-alert'],
                ['at' => 'T+10', 'event' => 'identity-inject'],
            ],
            'transitions' => [
                ['from' => 'detection', 'to' => 'containment', 'trigger' => 'triage-complete'],
                ['from' => 'containment', 'to' => 'recovery', 'trigger' => 'containment-approved'],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function rules(): array
    {
        return [
            ['key' => 'objective-complete', 'criterion' => 'Required simulated objective is satisfied.'],
        ];
    }
}
