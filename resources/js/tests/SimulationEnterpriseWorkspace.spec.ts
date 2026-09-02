import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import Workspace from '../pages/SimulationEnterprise/Workspace.vue';
import type {
  EnterpriseItem,
  LabItem,
  ResultAnalyticsProjection,
  ResultCompareProjection,
  ResultItem,
  ResultMode,
  ResultsWorkspaceProjection,
  RunItem,
  RunPreflightWorkspace,
  RunWorkspaceProjection,
  ScenarioItem,
  SimulationSection,
  WorkspaceProps,
} from '../pages/SimulationEnterprise/types';

Object.assign(router, { get: vi.fn() });

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
  status: 'PUBLISHED',
  environment_binding_mode: 'ENTERPRISE_BASELINE',
  baseline_id: '01900000-0000-7000-8000-000000000502',
  digest: 'e'.repeat(64),
  configuration: {
    objective: 'trace-authentication-causality',
    steps: ['observe', 'correlate', 'validate'],
  },
  validation: { requires_trace: true },
  can_prepare: true,
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
  definition_digest: 'f'.repeat(64),
  provenance: 'SIMULATED',
  source_fixture: false,
  prepared_at: '2026-08-25T00:00:00Z',
  ready_at: '2026-08-25T00:01:00Z',
  started_at: '2026-08-25T00:02:00Z',
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

function analyticsFor(
  resultId: string,
  runId: string,
  options: {
    outcome?: string;
    score?: string | null;
    replayStatus?: ResultAnalyticsProjection['replay']['status'];
  } = {},
): ResultAnalyticsProjection {
  const revisionId = resultId.slice(0, -3) + '801';
  const outcome = options.outcome ?? 'ACHIEVED';
  const score = options.score === undefined ? '94' : options.score;
  const replayStatus = options.replayStatus ?? 'READY';
  const events = run.events.map((event, index) => ({
    ...event,
    source_ref: 'timeline:sequence:' + event.sequence,
    projection_status: replayStatus === 'READY' ? ('READY' as const) : ('UNAVAILABLE' as const),
    state_at_point:
      replayStatus === 'READY'
        ? {
            projection_scope: 'GOVERNED_OPERATION_CONTROLS_ONLY' as const,
            controls: (index === 0 ? {} : { IDENTITY_MFA: false }) as Record<string, boolean>,
          }
        : null,
    ...(index === 1 ? { operation_key: 'workspace-operation-001' } : {}),
  }));

  return {
    status: replayStatus === 'READY' ? 'READY' : 'PARTIAL_ANALYTICS',
    overview: {
      status: 'READY',
      canonical: {
        result_id: resultId,
        run_id: runId,
        result_revision: 1,
        result_digest: 'c'.repeat(64),
        provenance: 'SIMULATED',
        source_fixture: false,
        sealed_by: 'actor-1',
        sealed_at: '2026-08-25T01:00:00Z',
        run_type: 'Scenario Run',
        run_lifecycle: 'COMPLETED',
      },
      lineage: {
        status: 'READY',
        revision_count: 1,
        root_revision_id: revisionId,
        effective_revision_id: revisionId,
        revisions: [
          {
            id: revisionId,
            base_revision_id: null,
            revision_digest: 'e'.repeat(64),
            actor_identity: 'actor-1',
            correction_reason: null,
            created_at: '2026-08-25T01:01:00Z',
          },
        ],
      },
      effective: {
        id: revisionId,
        result_id: resultId,
        revision_digest: 'e'.repeat(64),
        base_revision_id: null,
        correction_reason: null,
        actor_identity: 'actor-1',
        created_at: '2026-08-25T01:01:00Z',
        outcome,
        score,
        summary_ar: 'تعليق Result مختوم وقابل للتتبع فقط.',
      },
    },
    replay: {
      status: replayStatus,
      projector: {
        availability: replayStatus,
        grammar_version: 'CEP_INTERNAL_OPERATION_V1',
        semantic_version:
          replayStatus === 'READY' ? 'cep.results.replay.operation-engine-v1/v1' : null,
      },
      events,
      operation_count: replayStatus === 'READY' ? 1 : null,
      write_behavior: 'ZERO_WRITE_PROJECTION',
    },
    aar: {
      status: 'READY',
      facts: [
        {
          id: 'effective-outcome',
          kind: 'EFFECTIVE_RESULT_FIELD',
          label_ar: 'النتيجة الفعالة',
          value: outcome,
          source_ref: 'revision:' + revisionId + '/outcome',
        },
        ...run.events.map((event) => ({
          id: 'timeline-' + event.sequence,
          kind: 'SEALED_TIMELINE_EVENT',
          label_ar: 'حدث مختوم',
          value: event.event_type,
          source_ref: 'timeline:sequence:' + event.sequence,
          sequence: event.sequence,
        })),
      ],
      operation_count: replayStatus === 'READY' ? 1 : null,
      sealed_commentary: {
        value: 'تعليق Result مختوم وقابل للتتبع فقط.',
        source_ref: 'revision:' + revisionId + '/summary_ar',
        classification: 'SEALED_RESULT_COMMENTARY',
      },
      unavailable_sections: [
        { key: 'causal_factors', reason: 'UNAVAILABLE_FROM_SEALED_TRUTH' },
        { key: 'lessons', reason: 'UNAVAILABLE_FROM_SEALED_TRUTH' },
        { key: 'missed_detections', reason: 'UNAVAILABLE_FROM_SEALED_TRUTH' },
        { key: 'derived_metrics', reason: 'UNAVAILABLE_FROM_SEALED_TRUTH' },
      ],
      source_policy: 'SEALED_HISTORY_AND_EFFECTIVE_REVISION_ONLY',
      write_behavior: 'ZERO_WRITE_PROJECTION',
    },
    candidate_evidence: {
      status: 'READY',
      write_behavior: 'ZERO_WRITE_SOURCE_PREVIEW',
      w04_state: 'NOT_CREATED_OR_CLAIMED',
      envelope: {
        result_id: resultId,
        run_id: runId,
        status: 'SOURCE_PREVIEW_ONLY',
        effective_revision_id: revisionId,
        effective_revision_digest: 'e'.repeat(64),
        source_result_digest: 'c'.repeat(64),
        provenance: 'SIMULATED',
        source_fixture: false,
        material_context: { enterprise_id: run.enterprise_id, lab_ids: ['lab-01'] },
      },
    },
  };
}

const result: ResultItem = {
  id: '01900000-0000-7000-8000-000000000701',
  run_id: run.id,
  run_type: 'Scenario Run',
  run_lifecycle: 'COMPLETED',
  outcome: 'PARTIAL',
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
  analytics: analyticsFor('01900000-0000-7000-8000-000000000701', run.id),
  legacy_history: {
    replay_compare: null,
    candidate_evidence_handoff: null,
  },
};

const secondResult: ResultItem = {
  ...result,
  id: '01900000-0000-7000-8000-000000000702',
  run_id: '01900000-0000-7000-8000-000000000612',
  result_digest: 'd'.repeat(64),
  analytics: analyticsFor(
    '01900000-0000-7000-8000-000000000702',
    '01900000-0000-7000-8000-000000000612',
    { outcome: 'PARTIAL', score: null },
  ),
};

function compareProjection(items: ResultItem[]): ResultCompareProjection {
  const projectedItems = items.map((item) => ({
    result_id: item.id,
    run_id: item.run_id,
    canonical_result_digest: item.result_digest,
    effective_revision_id: item.analytics.overview.effective?.id ?? '',
    effective_revision_digest: item.analytics.overview.effective?.revision_digest ?? '',
  }));
  const values = (
    key: 'outcome' | 'score',
  ): ResultCompareProjection['dimensions'][number]['values'] =>
    items.map((item) => {
      const value = item.analytics.overview.effective?.[key] ?? null;
      return {
        result_id: item.id,
        run_id: item.run_id,
        value,
        display: value ?? 'N/A',
        availability: value === null ? 'N/A' : 'READY',
        source_ref: 'revision:' + item.analytics.overview.effective?.id + '/' + key,
      };
    });

  return {
    status: 'PARTIAL_ANALYTICS',
    selection_valid: true,
    selected_result_ids: items.map((item) => item.id),
    selected_run_ids: items.map((item) => item.run_id),
    items: projectedItems,
    dimensions: [
      {
        key: 'outcome',
        label_ar: 'النتيجة الفعالة',
        value_type: 'categorical',
        source: 'effective_revision',
        status: 'READY',
        compatible: true,
        values: values('outcome'),
      },
      {
        key: 'score',
        label_ar: 'الدرجة الفعالة',
        value_type: 'decimal',
        source: 'effective_revision',
        status: 'N/A',
        compatible: false,
        values: values('score'),
      },
    ],
    comparison_semantics: 'cep.results.compare.registry/v1',
    write_behavior: 'ZERO_WRITE_PROJECTION',
  };
}

function resultsWorkspace(
  mode: ResultMode,
  results: ResultItem[],
  compare: ResultCompareProjection | null = null,
): ResultsWorkspaceProjection {
  const selectedIds = compare?.selected_result_ids ?? [];
  return {
    status: results.length ? 'READY' : 'EMPTY',
    mode,
    available_modes: ['overview', 'replay', 'aar', 'compare', 'candidate-evidence'],
    selected_result_id: results[0]?.id ?? null,
    compare_result_ids: selectedIds,
    compare:
      compare ??
      ({
        status: 'EMPTY',
        selection_valid: false,
        selected_result_ids: [],
        selected_run_ids: [],
        items: [],
        dimensions: [],
        reason: 'COMPARE_MINIMUM_DISTINCT_RUNS_REQUIRED',
        write_behavior: 'ZERO_WRITE_PROJECTION',
      } satisfies ResultCompareProjection),
  };
}

const runPreflight: RunPreflightWorkspace = {
  status: 'READY',
  execution_model: 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
  default_seed: 20260814,
  execution_modes: ['GUIDED', 'UNGUIDED'],
  scenario_definitions: [
    {
      status: 'READY',
      run_type: 'Scenario Run',
      definition_id: scenario('11').id,
      definition_slug: scenario('11').slug,
      definition_title_ar: scenario('11').title_ar,
      definition_revision: 1,
      definition_status: 'PUBLISHED',
      definition_digest: '1'.repeat(64),
      environment_contract_digest: '2'.repeat(64),
      execution_model: 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
      required_capabilities: ['IDENTITY_POLICY', 'INTERNAL_TELEMETRY'],
      targets: [
        {
          ...target('11'),
          status: 'COMPATIBLE',
          required_capabilities: ['IDENTITY_POLICY', 'INTERNAL_TELEMETRY'],
          missing_capabilities: [],
        },
      ],
      provenance: 'SIMULATED',
      source_fixture: true,
      blocking_reason: null,
    },
  ],
  lab_definitions: [
    {
      status: 'INCOMPATIBLE',
      run_type: 'Standalone Lab Run',
      definition_id: lab.id,
      definition_slug: lab.slug,
      definition_title_ar: lab.title_ar,
      definition_revision: lab.revision,
      definition_status: 'PUBLISHED',
      definition_digest: lab.digest,
      environment_contract_digest: '3'.repeat(64),
      environment_binding_mode: 'ENTERPRISE_BASELINE',
      execution_model: 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
      required_capabilities: ['IDENTITY_POLICY', 'MISSING_CAPABILITY'],
      available_capabilities: ['IDENTITY_POLICY'],
      missing_capabilities: ['MISSING_CAPABILITY'],
      target: target('11'),
      provenance: 'SIMULATED',
      source_fixture: true,
      blocking_reason: 'PINNED_BASELINE_MISSING_REQUIRED_CAPABILITIES',
    },
  ],
};

const runWorkspace = (
  mode: RunWorkspaceProjection['mode'],
  type: RunWorkspaceProjection['preflight_type'] = 'scenario',
  definitionId: string | null = runPreflight.scenario_definitions[0].definition_id,
): RunWorkspaceProjection => ({
  status: 'READY',
  mode,
  available_modes: ['preflight', 'operations'],
  preflight_type: type,
  definition_id: definitionId,
});

function mountWorkspace(
  section: SimulationSection,
  overrides: Partial<WorkspaceProps> = {},
  attachTo?: Element,
) {
  const props: WorkspaceProps = {
    section,
    navigation: [],
    enterprises: [],
    scenarios: [],
    labs: [],
    runs: [],
    results: [],
    results_workspace: null,
    run_preflight: null,
    run_workspace: null,
    outcomes: ['NOT_EVALUATED', 'SUCCEEDED'],
    ...overrides,
  };

  if (section === 'results' && props.results_workspace === null) {
    props.results_workspace = resultsWorkspace('overview', props.results);
  }

  return mount(Workspace, { props, ...(attachTo ? { attachTo } : {}) });
}

describe('Simulation Enterprise workspace assurance states', () => {
  beforeEach(() => {
    vi.mocked(router.post).mockReset();
    vi.mocked(router.get).mockReset();
  });

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

  it('routes Scenario preparation into the focused server-owned Run Preflight', async () => {
    const scenarios = [scenario('11'), scenario('22')];
    const wrapper = mountWorkspace('scenarios', { scenarios });
    await nextTick();

    expect(wrapper.findAll('[data-testid="scenario-preflight-entry"]')).toHaveLength(1);
    expect(wrapper.get('[data-testid="scenario-boundary-note"]').text()).toContain(
      'Environment Contract',
    );
    expect(wrapper.get('[data-testid="scenario-boundary-note"]').text()).toContain(
      'Runtime Snapshot يبقى التقاطًا تاريخيًا داخل Run',
    );
    expect(wrapper.get('[data-testid="scenario-boundary-note"]').text()).not.toContain(
      'وتُنشأ منه Runtime Snapshot',
    );
    expect(wrapper.find('.sim-domain-mark').exists()).toBe(true);
    expect(wrapper.find('.sim-live-dot').exists()).toBe(false);

    await wrapper.get('[data-testid="scenario-preflight-entry"] button').trigger('click');
    expect(router.get).toHaveBeenCalledWith(
      '/simulation/runs',
      {
        mode: 'preflight',
        preflight_type: 'scenario',
        definition: scenarios[0].id,
      },
      expect.objectContaining({ preserveScroll: true }),
    );

    await wrapper.findAll('[data-testid="structure-list"] button')[1].trigger('click');
    await nextTick();
    await wrapper.get('[data-testid="scenario-preflight-entry"] button').trigger('click');
    expect(vi.mocked(router.get).mock.calls[1][1]).toMatchObject({
      definition: scenarios[1].id,
    });
    expect(router.post).not.toHaveBeenCalled();
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
    expect(center.get('.sim-lifecycle-track').text()).toContain('PREPARING');
    expect(center.get('.sim-lifecycle-track').text()).not.toContain('PREPARED');
    expect(center.get('[data-testid="run-runtime-telemetry"]').text()).toContain(
      'الحالة التشغيلية المرصودة',
    );
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

  it('renders truthful Scenario and incompatible Standalone Lab preflight without client invention', async () => {
    vi.mocked(router.post).mockImplementation((_url, _data, options) =>
      options?.onFinish?.({} as never),
    );
    const wrapper = mountWorkspace('runs', {
      runs: [run],
      run_preflight: runPreflight,
      run_workspace: runWorkspace('preflight'),
    });
    await nextTick();

    const center = wrapper.get('[data-testid="run-preflight"]');
    expect(center.text()).toContain('CEP_INTERNAL_HIGH_FIDELITY_SIMULATION');
    expect(center.text()).toContain('IDENTITY_POLICY');
    expect(center.text()).toContain('AVAILABLE');
    expect(center.text()).toContain('SOURCE FIXTURE');
    expect(center.text()).toContain('UNAVAILABLE UNTIL RUN CREATION');
    expect(center.text()).not.toContain('Docker');
    expect(center.text()).not.toContain('SSH');
    expect(wrapper.get('[data-testid="preflight-right"]').text()).toContain(
      'لا تعيد Vue حساب القرار',
    );

    await wrapper.get('[data-testid="preflight-submit"]').trigger('submit');
    expect(router.post).toHaveBeenCalledOnce();
    expect(vi.mocked(router.post).mock.calls[0][0]).toBe(
      `/simulation/scenarios/${runPreflight.scenario_definitions[0].definition_id}/runs`,
    );
    expect(vi.mocked(router.post).mock.calls[0][1]).toMatchObject({
      baseline_id: runPreflight.scenario_definitions[0].targets?.[0].baseline_id,
      seed: 20260814,
      mode: 'GUIDED',
    });

    const labNode = wrapper.get('[data-structure-kind="preflight-standalone-lab"] button');
    await labNode.trigger('click');
    await nextTick();
    expect(wrapper.get('[data-testid="preflight-blocking-reason"]').text()).toContain(
      'PINNED_BASELINE_MISSING_REQUIRED_CAPABILITIES',
    );
    expect(wrapper.get('[data-testid="preflight-capabilities"]').text()).toContain(
      'MISSING_CAPABILITY',
    );
    expect(wrapper.get('[data-testid="preflight-submit"] button').attributes()).toHaveProperty(
      'disabled',
    );
  });

  it('restores Overview from the governed workspace projection and shows the effective leaf', async () => {
    const wrapper = mountWorkspace('results', {
      results: [result],
      results_workspace: resultsWorkspace('overview', [result]),
    });
    await nextTick();

    const center = wrapper.get('[data-testid="result-overview"]');
    expect(center.text()).toContain('ACHIEVED');
    expect(center.text()).toContain('UNIQUE LEAF');
    expect(center.text()).toContain('تعليق Result مختوم وقابل للتتبع فقط');
    expect(center.text()).not.toContain('PARTIALنتيجة');
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);
    expect(wrapper.get('[data-testid="result-actions"]').findAll('[role="tab"]')).toHaveLength(5);
  });

  it('routes Results modes through the URL, exposes LOADING, and keeps legacy mutations unreachable', async () => {
    vi.mocked(router.get).mockImplementation(() => undefined as never);
    const wrapper = mountWorkspace('results', {
      results: [result],
      results_workspace: resultsWorkspace('overview', [result]),
    });
    await nextTick();

    const replayTab = wrapper
      .get('[data-testid="result-actions"]')
      .findAll('[role="tab"]')
      .find((tab) => tab.text() === 'Replay');
    expect(replayTab).toBeDefined();
    await replayTab!.trigger('click');
    await nextTick();

    expect(router.get).toHaveBeenCalledWith(
      '/simulation/results',
      expect.objectContaining({ mode: 'replay', result: result.id }),
      expect.objectContaining({ preserveState: true, replace: true }),
    );
    expect(wrapper.get('[data-testid="result-center"]').attributes('aria-busy')).toBe('true');
    expect(wrapper.get('[data-testid="result-center"] [role="status"]').text()).toContain(
      'جارٍ تحميل الإسقاط المحكوم',
    );
    expect(wrapper.text()).not.toContain('إعادة البناء والمقارنة');
    expect(wrapper.text()).not.toContain('إنشاء Candidate Handoff');
    expect(router.post).not.toHaveBeenCalled();
  });

  it('moves real DOM focus across Results tabs and issues one request per native activation', async () => {
    const visits: Array<Parameters<typeof router.get>[2]> = [];
    vi.mocked(router.get).mockImplementation((_url, _data, options) => {
      visits.push(options);
    });
    const wrapper = mountWorkspace(
      'results',
      {
        results: [result],
        results_workspace: resultsWorkspace('overview', [result]),
      },
      document.body,
    );
    await nextTick();

    const tabs = wrapper.get('[data-testid="result-actions"]').findAll('[role="tab"]');
    (tabs[0].element as HTMLElement).focus();
    expect(document.activeElement).toBe(tabs[0].element);

    await tabs[0].trigger('keydown', { key: 'ArrowLeft' });
    expect(document.activeElement).toBe(tabs[1].element);
    expect(router.get).toHaveBeenCalledTimes(1);

    tabs[1].element.dispatchEvent(
      new KeyboardEvent('keydown', { key: 'ArrowLeft', bubbles: true, cancelable: true }),
    );
    (tabs[2].element as HTMLButtonElement).click();
    expect(router.get).toHaveBeenCalledTimes(1);
    expect(tabs.every((tab) => tab.attributes('disabled') !== undefined)).toBe(true);

    visits[0]?.onFinish?.({} as never);
    await nextTick();

    const nativeActivate = (button: HTMLButtonElement, key: 'Enter' | ' '): void => {
      const down = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true });
      button.dispatchEvent(down);
      if (key === 'Enter' && !down.defaultPrevented) button.click();
      const up = new KeyboardEvent('keyup', { key, bubbles: true, cancelable: true });
      button.dispatchEvent(up);
      if (key === ' ' && !down.defaultPrevented && !up.defaultPrevented) button.click();
    };

    nativeActivate(tabs[2].element as HTMLButtonElement, 'Enter');
    expect(router.get).toHaveBeenCalledTimes(2);
    visits[1]?.onFinish?.({} as never);
    await nextTick();

    nativeActivate(tabs[3].element as HTMLButtonElement, ' ');
    expect(router.get).toHaveBeenCalledTimes(3);
    visits[2]?.onFinish?.({} as never);
    await nextTick();

    await tabs[3].trigger('keydown', { key: 'End' });
    expect(document.activeElement).toBe(tabs[4].element);
    expect(router.get).toHaveBeenCalledTimes(4);
    visits[3]?.onFinish?.({} as never);
    await nextTick();

    await tabs[4].trigger('keydown', { key: 'Home' });
    expect(document.activeElement).toBe(tabs[0].element);
    expect(router.get).toHaveBeenCalledTimes(5);
    wrapper.unmount();
  });

  it('renders Replay from typed projector output and drives one RIGHT context source', async () => {
    const wrapper = mountWorkspace('results', {
      results: [result],
      results_workspace: resultsWorkspace('replay', [result]),
    });
    await nextTick();

    const center = wrapper.get('[data-testid="result-replay"]');
    expect(center.text()).toContain('cep.results.replay.operation-engine-v1/v1');
    expect(center.findAll('[data-testid="replay-event"]')).toHaveLength(run.events.length);
    await center.findAll('[data-testid="replay-event"]')[1].trigger('click');
    await nextTick();

    expect(center.text()).toContain('GOVERNED_OPERATION_CONTROLS_ONLY');
    expect(center.text()).toContain('IDENTITY_MFA');
    const right = wrapper.get('[data-testid="result-right"]');
    expect(right.text()).toContain('timeline:sequence:2');
    expect(right.text()).toContain('workspace-operation-001');
    expect(right.text()).toContain('ZERO WRITE');
  });

  it('renders sparse AAR as traceable facts and explicit absence without fabricated causality', async () => {
    const wrapper = mountWorkspace('results', {
      results: [result],
      results_workspace: resultsWorkspace('aar', [result]),
    });
    await nextTick();

    const center = wrapper.get('[data-testid="result-aar"]');
    expect(center.text()).toContain('SEALED_HISTORY_AND_EFFECTIVE_REVISION_ONLY');
    expect(center.text()).toContain('timeline:sequence:1');
    expect(center.text()).toContain('UNAVAILABLE_FROM_SEALED_TRUTH');
    expect(center.text()).not.toContain('أقوى عامل سببي');
    expect(center.text()).not.toContain('فرصة مفقودة');

    await center.findAll('.sim-aar-facts button')[1].trigger('click');
    expect(wrapper.get('[data-testid="result-right"]').text()).toContain('SEALED_TIMELINE_EVENT');
  });

  it('compares distinct Runs from backend dimensions and renders incompatible values as N/A', async () => {
    const comparison = compareProjection([result, secondResult]);
    const wrapper = mountWorkspace('results', {
      results: [result, secondResult],
      results_workspace: resultsWorkspace('compare', [result, secondResult], comparison),
    });
    await nextTick();

    const center = wrapper.get('[data-testid="result-compare"]');
    expect(center.text()).toContain('cep.results.compare.registry/v1');
    expect(center.text()).toContain('N/A');
    expect(center.text()).not.toContain('زيادة 0');
    expect(
      wrapper.get('[data-testid="structure-list"]').findAll('.sim-structure-item--multi'),
    ).toHaveLength(2);

    await center.findAll('.sim-compare-matrix tbody button')[1].trigger('click');
    const right = wrapper.get('[data-testid="result-right"]');
    expect(right.text()).toContain('score');
    expect(right.text()).toContain('N/A');
    expect(right.text()).toContain('revision:');
  });

  it('previews Candidate Evidence without a form, W04 claim, legacy write, or current legacy truth', async () => {
    const historical = {
      ...result,
      legacy_history: {
        replay_compare: {
          id: 'legacy-compare',
          integrity_match: true,
          sealed_result_digest: 'c'.repeat(64),
          reconstructed_state_digest: 'd'.repeat(64),
          reconstruction: { historical: true },
          actor_id: 'legacy-actor',
          compared_at: '2026-08-20T00:00:00Z',
        },
        candidate_evidence_handoff: null,
      },
    };
    const wrapper = mountWorkspace('results', {
      results: [historical],
      results_workspace: resultsWorkspace('candidate-evidence', [historical]),
    });
    await nextTick();

    const center = wrapper.get('[data-testid="result-candidate-evidence"]');
    expect(center.text()).toContain('SOURCE_PREVIEW_ONLY');
    expect(center.text()).toContain('W04 NOT CLAIMED');
    expect(center.find('form').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('INTEGRITY_MATCH');
    expect(router.post).not.toHaveBeenCalled();

    await wrapper.get('[data-testid="result-actions"]').findAll('button').at(-1)!.trigger('click');
    const bottom = wrapper.get('[data-testid="result-bottom"]');
    expect(bottom.text()).toContain('Historical compatibility rows');
    expect(bottom.text()).toContain('historical');
  });

  it('renders initial-revision and semantic-projector failure states without guessing', async () => {
    const initialResult: ResultItem = {
      ...result,
      analytics: {
        ...result.analytics,
        status: 'INITIAL_REVISION_REQUIRED',
        overview: {
          ...result.analytics.overview,
          status: 'INITIAL_REVISION_REQUIRED',
          lineage: {
            status: 'INITIAL_REVISION_REQUIRED',
            revision_count: 0,
            root_revision_id: null,
            effective_revision_id: null,
            revisions: [],
          },
          effective: null,
        },
        replay: { status: 'UNAVAILABLE', reason: 'INITIAL_REVISION_REQUIRED' },
        aar: { status: 'UNAVAILABLE', reason: 'INITIAL_REVISION_REQUIRED' },
        candidate_evidence: { status: 'UNAVAILABLE', reason: 'INITIAL_REVISION_REQUIRED' },
      },
    };
    const initialWrapper = mountWorkspace('results', {
      results: [initialResult],
      results_workspace: resultsWorkspace('overview', [initialResult]),
    });
    await nextTick();
    expect(initialWrapper.get('[data-testid="results-lineage-state"]').text()).toContain(
      'يلزم إنشاء المراجعة الابتدائية',
    );

    const unavailable = {
      ...result,
      analytics: analyticsFor(result.id, result.run_id, {
        replayStatus: 'SEMANTIC_PROJECTOR_UNAVAILABLE',
      }),
    };
    const replayWrapper = mountWorkspace('results', {
      results: [unavailable],
      results_workspace: resultsWorkspace('replay', [unavailable]),
    });
    await nextTick();
    expect(replayWrapper.get('[data-testid="semantic-projector-unavailable"]').text()).toContain(
      'Semantic Projector غير متاح',
    );
    expect(replayWrapper.text()).not.toContain('GOVERNED_OPERATION_CONTROLS_ONLY');
  });
});
