<?php

namespace Database\Seeders;

use App\Modules\Enterprise\Application\SimulationEnterpriseFixtureWriter;
use App\Modules\Simulator\Application\SimulationEnterpriseService;
use Illuminate\Database\Seeder;

final class SimulationEnterpriseWave1Seeder extends Seeder
{
    public function run(): void
    {
        $actorId = 'SYSTEM:SIMULATION_WAVE1_SEEDER';
        /** @var SimulationEnterpriseFixtureWriter $enterpriseFixtures */
        $enterpriseFixtures = app(SimulationEnterpriseFixtureWriter::class);

        if ($enterpriseFixtures->hasFixture('cep-wave1-internal-enterprise')) {
            return;
        }

        /** @var SimulationEnterpriseService $simulation */
        $simulation = app(SimulationEnterpriseService::class);
        $enterprise = $enterpriseFixtures->createEnterprise(
            'cep-wave1-internal-enterprise',
            'بيئة CEP الداخلية التجريبية',
            [
                'purpose' => 'SYNTHETIC_WAVE1_FIXTURE',
                'zones' => ['USER_EDGE', 'APPLICATION', 'IDENTITY', 'DATA'],
            ],
            $actorId,
        );
        $primaryTwin = $enterpriseFixtures->createDigitalTwin(
            (string) $enterprise['id'],
            'identity-operations-twin',
            'التوأم الرقمي لعمليات الهوية',
            $actorId,
        );
        $secondaryTwin = $enterpriseFixtures->createDigitalTwin(
            (string) $enterprise['id'],
            'recovery-validation-twin',
            'التوأم الرقمي للتحقق من التعافي',
            $actorId,
        );
        $twinRevision = $enterpriseFixtures->publishDigitalTwinRevision((string) $enterprise['id'], (string) $primaryTwin['id'], [
            'nodes' => [
                ['id' => 'EDGE-01', 'kind' => 'gateway'],
                ['id' => 'APP-01', 'kind' => 'application'],
                ['id' => 'IDP-01', 'kind' => 'identity'],
            ],
            'links' => [
                ['from' => 'EDGE-01', 'to' => 'APP-01'],
                ['from' => 'APP-01', 'to' => 'IDP-01'],
            ],
        ], [
            'authentication' => 'SIMULATED_POLICY_ENGINE',
            'telemetry' => 'INTERNAL_EVENT_STREAM',
        ], $actorId);
        $enterpriseFixtures->publishDigitalTwinRevision((string) $enterprise['id'], (string) $secondaryTwin['id'], [
            'nodes' => [['id' => 'RECOVERY-01', 'kind' => 'recovery-service']],
            'links' => [],
        ], [
            'recovery' => 'SIMULATED_RECOVERY_ENGINE',
        ], $actorId);
        $baseline = $enterpriseFixtures->publishBaseline(
            (string) $enterprise['id'],
            (string) $primaryTwin['id'],
            (string) $twinRevision['id'],
            [
                'capabilities' => ['IDENTITY_POLICY', 'APPLICATION_STATE', 'INTERNAL_TELEMETRY'],
                'identity_policy' => ['mfa_required' => true],
                'application_state' => ['maintenance' => false],
                'telemetry_state' => ['collection' => 'enabled'],
            ],
            $actorId,
        );
        $lab = $simulation->publishLab(
            (string) $enterprise['id'],
            (string) $baseline['id'],
            'lab-auth-investigation',
            'مختبر تحليل مصادقة داخلي',
            [
                'objective' => 'trace-authentication-causality',
                'required_capabilities' => ['IDENTITY_POLICY', 'INTERNAL_TELEMETRY'],
                'steps' => ['observe', 'correlate', 'validate'],
            ],
            ['requires_trace' => true],
            $actorId,
        );
        $scenario = $simulation->publishScenario(
            'scenario-privileged-login',
            'سيناريو دخول مميّز مشتبه به',
            [
                'schema' => 'cep.simulation.environment-contract.v1',
                'execution_model' => 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
                'required_capabilities' => ['IDENTITY_POLICY', 'APPLICATION_STATE', 'INTERNAL_TELEMETRY'],
            ],
            ['phases' => ['initial_access', 'identity_validation', 'telemetry_review']],
            ['deterministic' => true, 'trace_required' => true],
            $actorId,
        );
        $simulation->attachLabModule((string) $scenario['id'], (string) $lab['id'], 'AUTH-INVESTIGATION-01', [
            'mode' => 'GUIDED',
            'required' => true,
        ]);

        $run = $simulation->prepareScenarioRun((string) $scenario['id'], (string) $baseline['id'], 20260814, ['mode' => 'GUIDED'], $actorId);
        $simulation->markReady((string) $run['id'], $actorId);
        $simulation->start((string) $run['id'], $actorId);
        $simulation->applyOperation((string) $run['id'], [
            'operation_key' => 'wave1-operation-001',
            'verb' => 'SET_CONTROL_STATE',
            'target' => 'IDENTITY_MFA',
            'value' => false,
        ], $actorId);
        $simulation->completeInternalSimulation((string) $run['id'], $actorId);
        $result = $simulation->sealResult(
            (string) $run['id'],
            'PARTIAL',
            'نتيجة تجريبية مختومة لإثبات مسار Wave 1 الداخلي فقط.',
            84.0,
            $actorId,
            [['kind' => 'trace_digest', 'ref' => 'internal://wave1/trace']],
        );
        $simulation->createCandidateEvidenceHandoff((string) $result['id'], [
            'claim_ar' => 'مرشح دليل تجريبي مشتق من نتيجة محاكاة داخلية مختومة.',
            'artifact_refs' => ['internal://wave1/trace'],
        ], 'progress-evidence-intake:v1', $actorId);
    }
}
