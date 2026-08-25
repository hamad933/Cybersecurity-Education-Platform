import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import Workspace from '../pages/SimulationEnterprise/Workspace.vue';
import type {
  ResultItem,
  RunItem,
  ScenarioItem,
  SimulationSection,
  WorkspaceProps,
} from '../pages/SimulationEnterprise/types';

const target = (suffix: string) => ({
  enterprise_id: `01900000-0000-7000-8000-0000000001${suffix}`,
  enterprise_name_ar: `مؤسسة ${suffix}`,
  digital_twin_id: `01900000-0000-7000-8000-0000000002${suffix}`,
  digital_twin_name_ar: `توأم ${suffix}`,
  digital_twin_revision_id: `01900000-0000-7000-8000-0000000003${suffix}`,
  baseline_id: `01900000-0000-7000-8000-0000000004${suffix}`,
  baseline_revision: 1,
  baseline_digest: suffix.repeat(64),
  capabilities: ['IDENTITY_POLICY', 'INTERNAL_TELEMETRY'],
  provenance: 'SIMULATED',
  source_fixture: false,
});

const scenario = (suffix: string): ScenarioItem => ({
  id: `01900000-0000-7000-8000-0000000005${suffix}`,
  slug: `scenario-${suffix}`,
  title_ar: `سيناريو ${suffix}`,
  revision: 1,
  digest: suffix.repeat(64),
  environment_contract: {
    schema: 'cep.simulation.environment-contract.v1',
    execution_model: 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
    required_capabilities: ['IDENTITY_POLICY', 'INTERNAL_TELEMETRY'],
  },
  orchestration: { modules: [] },
  validation: { required_events: ['RUN_PREPARED'] },
  provenance: 'SIMULATED',
  preparation_targets: [target(suffix)],
  lab_module_references: [],
});

const run: RunItem = {
  id: '01900000-0000-7000-8000-000000000601',
  run_type: 'Scenario Run',
  lifecycle: 'RUNNING',
  definition_title_ar: 'تشغيل محاكاة داخلي',
  enterprise_id: '01900000-0000-7000-8000-000000000602',
  digital_twin_id: '01900000-0000-7000-8000-000000000603',
  digital_twin_revision_id: '01900000-0000-7000-8000-000000000604',
  baseline_id: '01900000-0000-7000-8000-000000000605',
  scenario_definition_id: '01900000-0000-7000-8000-000000000606',
  standalone_lab_definition_id: null,
  seed: 20260814,
  execution_policies: { mode: 'GUIDED' },
  runtime_state: {
    engine: 'INTERNAL_HIGH_FIDELITY',
    telemetry: { event_rate: 4, active_controls: 1 },
  },
  input_digest: 'a'.repeat(64),
  provenance: 'SIMULATED',
  source_fixture: false,
  available_actions: ['operate', 'pause', 'snapshot', 'complete'],
  events: [
    {
      sequence: 1,
      event_type: 'RUN_PREPARED',
      payload: { snapshot_kind: 'RUN_PREPARATION' },
      actor_id: 'actor-1',
      occurred_at: '2026-08-25T00:00:00Z',
    },
  ],
  operations: [],
  snapshots: [
    {
      id: '01900000-0000-7000-8000-000000000607',
      sequence: 1,
      event_sequence: 1,
      snapshot_kind: 'RUN_PREPARATION',
      state: { phase: 'PREPARED' },
      state_digest: 'b'.repeat(64),
      captured_by: 'actor-1',
      captured_at: '2026-08-25T00:00:00Z',
    },
  ],
  checkpoints: [
    {
      id: '01900000-0000-7000-8000-000000000608',
      sequence: 1,
      source_snapshot_id: '01900000-0000-7000-8000-000000000607',
      state: { phase: 'PREPARED' },
      state_digest: 'b'.repeat(64),
      restorable: true,
      created_by: 'actor-1',
      created_at: '2026-08-25T00:00:00Z',
    },
  ],
  result_id: null,
};

const result: ResultItem = {
  id: '01900000-0000-7000-8000-000000000701',
  run_id: run.id,
  run_type: 'Scenario Run',
  run_lifecycle: 'COMPLETED',
  outcome: 'SUCCEEDED',
  score: 94,
  summary_ar: 'أُكمل التشغيل وحُفظت الحقيقة التشغيلية.',
  sealed_payload: { runtime_state: { phase: 'COMPLETED' } },
  replay_timeline: run.events,
  artifacts: [],
  result_revision: 1,
  result_digest: 'c'.repeat(64),
  provenance: 'SIMULATED',
  source_fixture: false,
  sealed_by: 'actor-1',
  sealed_at: '2026-08-25T01:00:00Z',
  replay_compare: null,
  candidate_evidence_handoff: null,
};

function mountWorkspace(section: SimulationSection, overrides: Partial<WorkspaceProps> = {}) {
  const props: WorkspaceProps = {
    section,
    navigation: [],
    enterprises: [],
    scenarios: [],
    labs: [],
    runs: [],
    results: [],
    outcomes: ['NOT_EVALUATED', 'SUCCEEDED'],
    ...overrides,
  };

  return mount(Workspace, { props });
}

describe('Simulation Enterprise workspace assurance states', () => {
  beforeEach(() => vi.mocked(router.post).mockReset());

  it('submits the bounded in-run grammar and exposes pending and bounded error states', async () => {
    let visitOptions: Parameters<typeof router.post>[2] | undefined;
    vi.mocked(router.post).mockImplementation((_url, _data, options) => {
      visitOptions = options;
    });
    const wrapper = mountWorkspace('runs', { runs: [run] });
    await nextTick();

    await wrapper.get('form.action-form').trigger('submit');

    expect(router.post).toHaveBeenCalledOnce();
    expect(vi.mocked(router.post).mock.calls[0][0]).toBe(`/simulation/runs/${run.id}/operations`);
    expect(vi.mocked(router.post).mock.calls[0][1]).toMatchObject({
      verb: 'SET_CONTROL_STATE',
      target: 'IDENTITY_MFA',
      value: false,
    });
    expect(wrapper.get('.workspace').attributes('aria-busy')).toBe('true');
    expect(wrapper.find('[role="status"]').exists()).toBe(true);

    visitOptions?.onError?.({ simulation: 'Operations can be applied only to a RUNNING Run.' });
    await nextTick();
    expect(wrapper.get('[role="alert"]').text()).toContain('RUNNING Run');

    visitOptions?.onFinish?.({} as never);
    await nextTick();
    expect(wrapper.get('.workspace').attributes('aria-busy')).toBe('false');
  });

  it('gates Scenario preparation to the selected definition and compatible target', async () => {
    vi.mocked(router.post).mockImplementation((_url, _data, options) =>
      options?.onFinish?.({} as never),
    );
    const scenarios = [scenario('11'), scenario('22')];
    const wrapper = mountWorkspace('scenarios', { scenarios });
    await nextTick();

    expect(wrapper.findAll('[data-testid="scenario-prepare-controls"]')).toHaveLength(1);
    expect(wrapper.get('[data-testid="scenario-boundary-note"]').text()).toContain(
      'Environment Contract',
    );

    await wrapper.get('[data-testid="scenario-prepare-controls"]').trigger('submit');
    expect(vi.mocked(router.post).mock.calls[0][0]).toBe(
      `/simulation/scenarios/${scenarios[0].id}/runs`,
    );
    expect(vi.mocked(router.post).mock.calls[0][1]).toMatchObject({
      baseline_id: scenarios[0].preparation_targets[0].baseline_id,
    });

    await wrapper.findAll('[data-testid="structure-list"] button')[1].trigger('click');
    await nextTick();
    await wrapper.get('[data-testid="scenario-prepare-controls"]').trigger('submit');
    expect(vi.mocked(router.post).mock.calls[1][0]).toBe(
      `/simulation/scenarios/${scenarios[1].id}/runs`,
    );
    expect(vi.mocked(router.post).mock.calls[1][1]).toMatchObject({
      baseline_id: scenarios[1].preparation_targets[0].baseline_id,
    });
  });

  it('keeps run telemetry in RIGHT and operational history in closed BOTTOM detail', async () => {
    const wrapper = mountWorkspace('runs', { runs: [run] });
    await nextTick();

    expect(wrapper.get('[data-cep-region="right"] [data-testid="run-telemetry"]').text()).toContain(
      'event_rate',
    );
    expect(wrapper.get('[data-testid="run-center"]').text()).not.toContain('event_rate');
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);

    await wrapper.get('[data-testid="run-actions"] button:last-child').trigger('click');
    expect(wrapper.get('[data-cep-region="bottom"] [data-testid="run-bottom"]').text()).toContain(
      'Event timeline',
    );
    expect(wrapper.get('[data-testid="run-bottom"]').text()).toContain('Prepared Checkpoints');
  });

  it('renders Result as immutable historical truth and opens full replay only in BOTTOM', async () => {
    const wrapper = mountWorkspace('results', { results: [result] });
    await nextTick();

    expect(wrapper.get('[data-testid="result-immutable-indicator"]').text()).toContain('IMMUTABLE');
    expect(wrapper.get('[data-testid="result-center"]').text()).not.toContain('RUN_PREPARED');
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);

    await wrapper.get('[data-testid="result-actions"] button:last-child').trigger('click');
    const bottom = wrapper.get('[data-cep-region="bottom"] [data-testid="result-bottom"]');
    expect(bottom.text()).toContain('Sealed replay timeline');
    expect(bottom.text()).toContain('RUN_PREPARED');
    expect(wrapper.text()).toContain('Result ≠ Evidence');
  });
});
