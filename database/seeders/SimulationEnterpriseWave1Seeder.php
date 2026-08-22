<?php

namespace Database\Seeders;

use App\Modules\Enterprise\Application\SimulationEnterpriseFixtureWriter;
use App\Modules\Simulator\Application\SimulationEnterpriseService;
use Illuminate\Database\Seeder;

final class SimulationEnterpriseWave1Seeder extends Seeder
{
    public function run(): void
    {
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
        );
        $twin = $enterpriseFixtures->publishDigitalTwinRevision((string) $enterprise['id'], [
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
        ]);
        $baseline = $enterpriseFixtures->publishBaseline(
            (string) $enterprise['id'],
            (string) $twin['id'],
            [
                'identity_policy' => ['mfa_required' => true],
                'application_state' => ['maintenance' => false],
                'telemetry_state' => ['collection' => 'enabled'],
            ],
        );
        $lab = $simulation->publishLab(
            (string) $enterprise['id'],
            (string) $baseline['id'],
            'lab-auth-investigation',
            'مختبر تحليل مصادقة داخلي',
            ['objective' => 'trace-authentication-causality', 'steps' => ['observe', 'correlate', 'validate']],
            ['requires_trace' => true],
        );
        $scenario = $simulation->publishScenario(
            (string) $enterprise['id'],
            (string) $baseline['id'],
            'scenario-privileged-login',
            'سيناريو دخول مميّز مشتبه به',
            ['phases' => ['initial_access', 'identity_validation', 'telemetry_review']],
            ['deterministic' => true, 'trace_required' => true],
        );
        $simulation->attachLabModule((string) $scenario['id'], (string) $lab['id'], 'AUTH-INVESTIGATION-01', [
            'mode' => 'GUIDED',
            'required' => true,
        ]);

        $run = $simulation->prepareScenarioRun((string) $scenario['id'], 20260814, ['mode' => 'GUIDED']);
        $simulation->markReady((string) $run['id']);
        $simulation->start((string) $run['id']);
        $simulation->completeInternalSimulation((string) $run['id']);
        $result = $simulation->sealResult(
            (string) $run['id'],
            'PARTIAL',
            'نتيجة تجريبية مختومة لإثبات مسار Wave 1 الداخلي فقط.',
            84.0,
            [['kind' => 'trace_digest', 'ref' => 'internal://wave1/trace']],
        );
        $simulation->createCandidateEvidenceHandoff((string) $result['id'], [
            'claim_ar' => 'مرشح دليل تجريبي مشتق من نتيجة محاكاة داخلية مختومة.',
            'artifact_refs' => ['internal://wave1/trace'],
            'source' => 'SIMULATION_RUN_RESULT',
        ], 'progress-evidence-intake:v1');
    }
}
