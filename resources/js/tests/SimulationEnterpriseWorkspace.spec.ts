import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import Workspace from '../pages/SimulationEnterprise/Workspace.vue';
import type {
  EnterpriseItem,
  LabItem,
  ResultItem,
  RunItem,
  ScenarioItem,
  SimulationSection,
  WorkspaceProps,
} from '../pages/SimulationEnterprise/types';

const enterprise: EnterpriseItem = {
  id: '01900000-0000-7000-8000-000000000101',
  slug: 'enterprise-fixture',
  name_ar: 'مؤسسة الاختبار',
  description_ar: 'تعريف مؤسسي حقيقي للاختبار.',
  definition: { zones: ['EDGE', 'APPLICATION', 'IDENTITY'] },
  provenance: 'SIMULATED',
  is_fixture: false,
  digital_twins: [
    {
      id: '01900000-0000-7000-8000-000000000102',
      slug: 'identity-twin',
      name_ar: 'توأم الهوية',
      provenance: 'SIMULATED',
      is_fixture: false,
      revisions: [
        {
          id: '01900000-0000-7000-8000-000000000103',
          digital_twin_id: '01900000-0000-7000-8000-000000000102',
          revision: 2,
          digest: 'd'.repeat(64),
          topology: {
            nodes: [
              { id: 'EDGE-01', kind: 'gateway' },
              { id: 'APP-01', kind: 'application' },
              { id: 'IDP-01', kind: 'identity' },
            ],
            links: [
              { from: 'EDGE-01', to: 'APP-01' },
              { from: 'APP-01', to: 'IDP-01' },
            ],
          },
          behavior_model: { telemetry: 'INTERNAL_EVENT_STREAM' },
          baselines: [],
        },
      ],
    },
  ],
};

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
  orchestration: { phases: ['initial_access', 'identity_validation', 'telemetry_review'] },
  validation: { required_events: ['RUN_PREPARED'] },
  provenance: 'SIMULATED',
  preparation_targets: [target(suffix)],
  lab_module_references: [
    {
      reference_id: `01900000-0000-7000-8000-0000000008${suffix}`,
      module_key: `AUTH-${suffix}`,
      ordinal: 2,
      policy: { required: true },
      lab_definition_id: `01900000-0000-7000-8000-0000000009${suffix}`,
      lab_title_ar: `مختبر مرجعي ${suffix}`,
    },
  ],
});

const lab: LabItem = {
  id: '01900000-0000-7000-8000-000000000501',
  slug: 'lab-auth-investigation',
  title_ar: 'مختبر تحليل المصادقة',
  revision: 3,
  baseline_id: '01900000-0000-7000-8000-000000000502',
  digest: 'e'.repeat(64),
  configuration: {
    objective: 'trace-authentication-causality',
    steps: ['observe', 'correlate', 'validate'],
  },
  validation: { requires_trace: true },
  provenance: 'SIMULATED',
};

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
    {
      sequence: 2,
      event_type: 'RUN_STARTED',
      payload: { lifecycle: 'RUNNING' },
      actor_id: 'actor-1',
      occurred_at: '2026-08-25T00:02:00Z',
    },
  ],
  operations: [
    {
      id: '01900000-0000-7000-8000-000000000609',
      operation_key: 'operation-1',
      verb: 'SET_CONTROL_STATE',
      target: 'IDENTITY_MFA',
      input: { value: false },
      telemetry: { state_changed: true },
      actor_id: 'actor-1',
    },
  ],
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

  it('renders Enterprise topology nodes and links from the truthful CENTER payload', async () => {
    const wrapper = mountWorkspace('enterprise', { enterprises: [enterprise] });
    await nextTick();

    const center = wrapper.get('[data-cep-region="center"] [data-testid="enterprise-topology"]');
    expect(center.findAll('[data-testid="topology-node"]')).toHaveLength(3);
    expect(center.findAll('[data-testid="topology-link"]')).toHaveLength(2);
    expect(center.text()).toContain('3 NODES');
    expect(
      wrapper.get('[data-cep-region="left"] [data-structure-kind="digital-twin"]').text(),
    ).toContain('Revision 2');
    expect(center.text()).toContain('UNLABELED');
    expect(center.text()).not.toContain('AUTHENTICATES_WITH');

    const context = wrapper.get('[data-cep-region="right"]');
    expect(context.text()).not.toContain('EDGE-01');
    expect(context.text()).not.toContain('Enterprise-backed');
    expect(context.text()).not.toContain('Telemetry generation');
    expect(context.text()).not.toContain('HTTP / HTTPS interaction');
    expect(context.text()).not.toContain('Fully compatible');

    const enabledTopologyControls = center.findAll('button:not([disabled])');
    expect(enabledTopologyControls).toHaveLength(2);
    expect(enabledTopologyControls.map((button) => button.attributes('title'))).toEqual([
      'تصغير',
      'تكبير',
    ]);
  });

  it('renders Scenario orchestration as a visual phase flow with truthful Lab references', async () => {
    const selected = scenario('11');
    const wrapper = mountWorkspace('scenarios', { scenarios: [selected] });
    await nextTick();

    const center = wrapper.get('[data-cep-region="center"] [data-testid="scenario-orchestration"]');
    expect(center.findAll('[data-testid="scenario-phase"]')).toHaveLength(3);
    expect(center.findAll('[data-testid="scenario-module-node"]')).toHaveLength(1);
    expect(center.text()).toContain('initial_access');
    expect(center.text()).toContain(selected.lab_module_references[0].module_key);
    expect(wrapper.get('[data-cep-region="left"] [data-structure-kind="phase"]').text()).toContain(
      'Phase 01',
    );
    expect(wrapper.get('[data-cep-region="right"]').text()).not.toContain('initial_access');
    expect(wrapper.get('[data-testid="scenario-boundary-note"]').text()).toContain(
      'Environment Contract',
    );
    expect(center.findAll('button')).toHaveLength(0);

    const context = wrapper.get('[data-cep-region="right"]');
    expect(context.text()).toContain('IDENTITY_POLICY');
    expect(context.text()).not.toContain('SOC Analyst');
    expect(context.text()).not.toContain('Role: Defender');
    expect(context.text()).not.toContain('SIEM correlation rule');
    expect(context.text()).not.toContain('Alert Notification');
    expect(context.text()).not.toContain('Branch impact');
  });

  it('renders the Lab definition as a real ordered task graph and structural branch', async () => {
    const wrapper = mountWorkspace('labs', { labs: [lab] });
    await nextTick();

    const center = wrapper.get('[data-cep-region="center"] [data-testid="lab-task-graph"]');
    expect(center.findAll('[data-testid="lab-task-node"]')).toHaveLength(3);
    expect(center.text()).toContain('observe');
    expect(center.text()).toContain('correlate');
    expect(center.text()).toContain('BASELINE PINNED');
    expect(
      wrapper.get('[data-cep-region="left"] [data-structure-kind="lab-step"]').text(),
    ).toContain('Task 03');
    expect(center.findAll('button')).toHaveLength(0);

    const context = wrapper.get('[data-cep-region="right"]');
    expect(context.text()).toContain('trace-authentication-causality');
    expect(context.text()).toContain('requires_trace');
    expect(context.text()).not.toContain('observe');
    expect(context.text()).not.toContain('State confirmation');
    expect(context.text()).not.toContain('HTTP interaction');
    expect(context.text()).not.toContain('Request Inspector');
    expect(context.text()).not.toContain('Vulnerable response behavior');
    expect(center.text()).not.toContain('detecting and exploiting');
  });

  it('keeps primary run events, runtime telemetry, snapshots and checkpoints in CENTER', async () => {
    const wrapper = mountWorkspace('runs', { runs: [run] });
    await nextTick();

    const center = wrapper.get('[data-cep-region="center"] [data-testid="run-center"]');
    expect(center.text()).toContain('RUN_PREPARED');
    expect(center.get('[data-testid="run-runtime-telemetry"]').text()).toContain('event_rate');
    expect(center.get('[data-testid="run-snapshots"]').text()).toContain('RUN_PREPARATION');
    expect(center.get('[data-testid="run-checkpoints"]').text()).toContain('RESTORABLE');
    expect(center.findAll('[data-testid="run-operation"]')).toHaveLength(1);
    expect(center.findAll('[data-testid="run-operational-truth"]')).toHaveLength(1);
    expect(center.findAll('[data-testid="run-event"]')).toHaveLength(run.events.length);
    expect(center.text()).not.toContain('Split View');
    expect(center.text()).not.toContain('SIEM / Monitoring');
    expect(wrapper.get('[data-cep-region="right"]').text()).not.toContain('event_rate');
    expect(
      wrapper.get('[data-cep-region="right"] [data-testid="run-interpretation"]').text(),
    ).toContain('حدود التفسير');
    expect(
      wrapper.get('[data-cep-region="left"] [data-structure-kind="run-stream"]').text(),
    ).toContain('Runtime Snapshots');
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);

    const eventButtons = center.findAll('[data-testid="run-event"]');
    expect(eventButtons.every((button) => button.element.tagName === 'BUTTON')).toBe(true);
    await eventButtons[0].trigger('click');
    expect(eventButtons[0].attributes('aria-pressed')).toBe('true');
    expect(center.get('.sim-event-inspector').text()).toContain(run.events[0].event_type);

    const runContext = wrapper.get('[data-cep-region="right"]');
    expect(runContext.text()).not.toContain('anomalous SQL pattern');
    expect(runContext.text()).not.toContain('Possible injection attempt');
    expect(runContext.text()).not.toContain('database impact');
    expect(runContext.text()).not.toContain('before containment');

    await wrapper.get('[data-testid="run-actions"] button:last-child').trigger('click');
    const bottom = wrapper.get('[data-cep-region="bottom"] [data-testid="run-bottom"]');
    expect(bottom.text()).not.toContain('Event timeline');
    expect(bottom.text()).not.toContain('RUN_PREPARED');
    expect(bottom.text()).toContain('Prepared Checkpoints');
  });

  it('renders Result replay history as immutable CENTER truth while BOTTOM stays raw and closed', async () => {
    const wrapper = mountWorkspace('results', { results: [result] });
    await nextTick();

    expect(wrapper.get('[data-testid="result-immutable-indicator"]').text()).toContain('IMMUTABLE');
    const center = wrapper.get('[data-cep-region="center"] [data-testid="result-center"]');
    expect(center.get('[data-testid="result-replay-timeline"]').text()).toContain('RUN_PREPARED');
    expect(center.findAll('[data-testid="replay-event"]')).toHaveLength(
      result.replay_timeline.length,
    );
    expect(center.text()).toContain('NO RUN MUTATION');
    expect(center.text()).not.toContain('Pause Replay');
    expect(center.text()).not.toContain('Jump to Marker');
    expect(center.text()).not.toContain('1x');
    expect(
      wrapper.get('[data-cep-region="left"] [data-structure-kind="result-history"]').text(),
    ).toContain('Replay timeline');
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);

    await wrapper.get('[data-testid="result-actions"] button:last-child').trigger('click');
    const bottom = wrapper.get('[data-cep-region="bottom"] [data-testid="result-bottom"]');
    expect(bottom.text()).toContain('Frozen Result Payload');
    expect(bottom.text()).not.toContain('RUN_PREPARED');
    expect(wrapper.text()).toContain('Result ≠ Evidence');
    expect(wrapper.text()).toContain('Candidate Evidence Handoff');
    expect(wrapper.text()).toContain('ليس قبولًا في Evidence canonical');
    expect(wrapper.text()).not.toContain('detected and contained');
    expect(wrapper.text()).not.toContain('fully verified against execution digest');
    expect(wrapper.text()).not.toContain('INTEGRITY_MATCH');
    expect(bottom.text()).toContain('لا توجد replay_compare محكومة');
  });
});
