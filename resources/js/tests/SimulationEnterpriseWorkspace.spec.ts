import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import Workspace from '../pages/SimulationEnterprise/Workspace.vue';

const navigation = [
  { key: 'enterprise', label: 'المؤسسة', href: '/simulation' },
  { key: 'scenarios', label: 'السيناريوهات', href: '/simulation/scenarios' },
  { key: 'labs', label: 'المختبرات', href: '/simulation/labs' },
  { key: 'runs', label: 'التشغيلات', href: '/simulation/runs' },
  { key: 'results', label: 'النتائج', href: '/simulation/results' },
];

const enterprise = {
  id: 'enterprise-1',
  slug: 'cep-fixture',
  name_ar: 'المؤسسة التجريبية',
  description_ar: 'وصف مؤسسي',
  definition: { purpose: 'SYNTHETIC_WAVE1_FIXTURE', zones: ['EDGE', 'APP'] },
  is_fixture: true,
  digital_twin_revision: {
    id: 'twin-1',
    revision: 3,
    digest: 'twin-digest',
    topology: {
      nodes: [
        { id: 'EDGE-01', kind: 'gateway' },
        { id: 'APP-01', kind: 'application' },
      ],
      links: [{ from: 'EDGE-01', to: 'APP-01' }],
    },
  },
  baseline: {
    id: 'baseline-1',
    revision: 4,
    digest: 'baseline-digest',
    state: { identity_policy: 'MFA', telemetry_state: 'enabled' },
  },
};

const scenario = {
  id: 'scenario-1',
  slug: 'privileged-login',
  title_ar: 'سيناريو دخول مميّز',
  revision: 2,
  baseline_id: 'baseline-1',
  digest: 'scenario-digest',
  orchestration: { phases: ['initial_access', 'identity_validation', 'telemetry_review'] },
  validation: { deterministic: true, trace_required: true },
  lab_module_references: [
    {
      reference_id: 'reference-1',
      module_key: 'AUTH-INVESTIGATION-01',
      ordinal: 1,
      policy: { mode: 'GUIDED' },
      lab_definition_id: 'lab-1',
      lab_title_ar: 'مختبر تحليل المصادقة',
    },
  ],
};

const lab = {
  id: 'lab-1',
  slug: 'auth-investigation',
  title_ar: 'مختبر تحليل المصادقة',
  revision: 5,
  baseline_id: 'baseline-1',
  digest: 'lab-digest',
  configuration: {
    objective: 'trace-authentication-causality',
    steps: ['observe', 'correlate', 'validate'],
  },
  validation: { requires_trace: true },
};

const run = {
  id: 'run-1',
  run_type: 'Scenario Run',
  lifecycle: 'RUNNING',
  definition_title_ar: 'سيناريو دخول مميّز',
  enterprise_id: 'enterprise-1',
  digital_twin_revision_id: 'twin-1',
  baseline_id: 'baseline-1',
  scenario_definition_id: 'scenario-1',
  standalone_lab_definition_id: null,
  seed: 20260814,
  execution_policies: { mode: 'GUIDED' },
  runtime_state: {
    engine: 'INTERNAL_DETERMINISTIC_ENGINE',
    trace_digest: 'trace-digest',
    telemetry: { events_processed: 7 },
    validation: { deterministic: true },
  },
  input_digest: 'input-digest',
  available_actions: ['pause', 'snapshot', 'complete', 'stop'],
  events: [
    {
      sequence: 1,
      event_type: 'RUN_STARTED',
      payload: { source: 'internal' },
      occurred_at: '2026-08-19T01:00:00Z',
    },
  ],
  snapshots: [
    {
      id: 'snapshot-1',
      sequence: 1,
      event_sequence: 1,
      state_digest: 'snapshot-digest',
      captured_at: '2026-08-19T01:01:00Z',
    },
  ],
  result_id: null,
};

const result = {
  id: 'result-1',
  run_id: 'run-1',
  run_type: 'Scenario Run',
  run_lifecycle: 'COMPLETED',
  outcome: 'PARTIAL',
  score: 84,
  summary_ar: 'نتيجة مختومة للاختبار.',
  sealed_payload: { trace_digest: 'trace-digest' },
  replay_timeline: run.events,
  artifacts: [{ kind: 'trace_digest', ref: 'internal://fixture/trace' }],
  sealed_at: '2026-08-19T01:05:00Z',
  candidate_evidence_handoff: null,
};

function propsFor(section: string) {
  return {
    section,
    navigation,
    enterprises: section === 'enterprise' ? [enterprise] : [],
    scenarios: section === 'scenarios' ? [scenario] : [],
    labs: section === 'labs' ? [lab] : [],
    runs: section === 'runs' ? [run] : [],
    results: section === 'results' ? [result] : [],
    outcomes: ['ACHIEVED', 'PARTIAL', 'NOT_ACHIEVED', 'INCONCLUSIVE', 'NOT_EVALUATED'],
  };
}

describe('Simulation & Enterprise governed workspace', () => {
  beforeEach(() => vi.mocked(router.post).mockClear());

  it('keeps exactly five primary areas and excludes Operations from primary navigation', () => {
    const wrapper = mount(Workspace, { props: propsFor('enterprise') });
    const nav = wrapper.get('[data-testid="simulation-primary-nav"]');

    expect(nav.findAll('button')).toHaveLength(5);
    expect(nav.text()).toContain('المؤسسة');
    expect(nav.text()).toContain('السيناريوهات');
    expect(nav.text()).toContain('المختبرات');
    expect(nav.text()).toContain('التشغيلات');
    expect(nav.text()).toContain('النتائج');
    expect(nav.text()).not.toContain('Operations');
  });

  it('renders enterprise topology in CENTER and unique object context in RIGHT without raw JSON', () => {
    const wrapper = mount(Workspace, { props: propsFor('enterprise') });

    expect(wrapper.get('[data-testid="enterprise-topology-canvas"]').text()).toContain('EDGE-01');
    expect(wrapper.get('[data-testid="enterprise-topology-canvas"]').text()).toContain('APP-01');
    expect(wrapper.get('[data-testid="enterprise-object-context"]').text()).toContain('twin-digest');
    expect(wrapper.find('pre').exists()).toBe(false);
  });

  it('renders scenario phases once as orchestration flow and keeps validation in RIGHT', () => {
    const wrapper = mount(Workspace, { props: propsFor('scenarios') });
    const center = wrapper.get('[data-testid="scenario-orchestration-canvas"]');

    expect(center.text()).toContain('initial_access');
    expect(center.text()).toContain('identity_validation');
    expect(wrapper.get('[data-testid="scenario-properties"]').text()).toContain('trace_required');
    expect(wrapper.text().split('initial_access')).toHaveLength(2);
  });

  it('renders lab steps as task graph and properties in the right inspector', () => {
    const wrapper = mount(Workspace, { props: propsFor('labs') });

    expect(wrapper.get('[data-testid="lab-task-graph"]').text()).toContain('observe');
    expect(wrapper.get('[data-testid="lab-task-graph"]').text()).toContain('correlate');
    expect(wrapper.get('[data-testid="lab-properties"]').text()).toContain('requires_trace');
  });

  it('keeps Operations as a mode inside the active Run and RIGHT as interpretation only', async () => {
    const wrapper = mount(Workspace, { props: propsFor('runs') });
    const runSurface = wrapper.get('[data-testid="run-runtime-console"]');

    expect(runSurface.text()).toContain('INTERNAL_DETERMINISTIC_ENGINE');
    expect(wrapper.get('[data-testid="run-interpretation"]').text()).toContain('التنفيذ الداخلي نشط');
    expect(wrapper.get('[data-testid="simulation-primary-nav"]').text()).not.toContain('Operations');

    await wrapper.get('[data-testid="run-operations-mode"]').trigger('click');
    expect(wrapper.get('[data-testid="run-operations-panel"]').text()).toContain('حفظ Snapshot');
  });

  it('keeps Replay, AAR, and Compare inside Results and does not invent comparison data', async () => {
    const wrapper = mount(Workspace, { props: propsFor('results') });
    const surface = wrapper.get('[data-testid="results-history-replay"]');

    expect(surface.text()).toContain('Replay');
    expect(surface.text()).toContain('AAR');
    expect(surface.text()).toContain('Compare');
    expect(wrapper.get('[data-testid="result-analysis"]').text()).toContain('دون استنتاج Mastery');

    const compare = surface.findAll('button').find((button) => button.text() === 'Compare');
    expect(compare).toBeDefined();
    await compare!.trigger('click');
    expect(surface.text()).toContain('يلزم Result تاريخي ثانٍ');
  });
});
