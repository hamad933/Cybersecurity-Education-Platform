import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import EnterpriseContext from '../pages/SimulationEnterprise/components/EnterpriseContext.vue';
import EnterpriseSurface from '../pages/SimulationEnterprise/components/EnterpriseSurface.vue';
import LabContext from '../pages/SimulationEnterprise/components/LabContext.vue';
import LabSurface from '../pages/SimulationEnterprise/components/LabSurface.vue';
import StructureList from '../pages/SimulationEnterprise/components/StructureList.vue';
import WorkspaceToolbar from '../pages/SimulationEnterprise/components/WorkspaceToolbar.vue';
import type {
  DigitalTwinRevisionItem,
  EnterpriseItem,
  LabItem,
} from '../pages/SimulationEnterprise/types';

const twinRevision: DigitalTwinRevisionItem = {
  id: '01900000-0000-7000-8000-000000000103',
  digital_twin_id: '01900000-0000-7000-8000-000000000102',
  revision: 2,
  status: 'VALIDATED',
  based_on_revision_id: '01900000-0000-7000-8000-000000000099',
  digest: 'd'.repeat(64),
  topology: { nodes: [], links: [] },
  behavior_model: { causality: 'STATE_EVENT_TELEMETRY_VALIDATION' },
  validation_report: { valid: true },
  validated_at: '2026-08-29T00:00:00Z',
  published_at: null,
  components: [
    {
      id: '01900000-0000-7000-8000-000000000121',
      component_key: 'CRM-APPLICATION',
      ownership_scope: 'ENTERPRISE_ENTITY',
      enterprise_entity_id: '01900000-0000-7000-8000-000000000111',
      device_template_revision_id: '01900000-0000-7000-8000-000000000131',
      name_ar: 'CRM application component',
      simulation_definition: { initial_state: { service: 'UP' } },
    },
    {
      id: '01900000-0000-7000-8000-000000000122',
      component_key: 'TRAINING-SENDER',
      ownership_scope: 'SIMULATION_LOCAL',
      enterprise_entity_id: null,
      device_template_revision_id: null,
      name_ar: 'Simulation-local sender',
      simulation_definition: { purpose: 'TRAINING_ONLY' },
    },
  ],
  relationships: [
    {
      id: '01900000-0000-7000-8000-000000000123',
      source_component_id: '01900000-0000-7000-8000-000000000121',
      target_component_id: '01900000-0000-7000-8000-000000000122',
      relationship_type: 'CONNECTS_TO',
      properties: { channel: 'SIMULATED_HTTP' },
    },
  ],
  baselines: [],
};

const enterprise: EnterpriseItem = {
  id: '01900000-0000-7000-8000-000000000101',
  slug: 'enterprise-simdef',
  name_ar: 'Simulation definition enterprise',
  description_ar: 'Canonical Enterprise definition.',
  definition: { purpose: 'SIMULATED' },
  provenance: 'SIMULATED',
  is_fixture: false,
  entities: [
    {
      id: '01900000-0000-7000-8000-000000000111',
      enterprise_id: '01900000-0000-7000-8000-000000000101',
      entity_key: 'APP-CRM',
      entity_type: 'APPLICATION',
      name_ar: 'Canonical CRM application',
      lifecycle_state: 'ACTIVE',
      properties: { criticality: 'HIGH' },
    },
    {
      id: '01900000-0000-7000-8000-000000000112',
      enterprise_id: '01900000-0000-7000-8000-000000000101',
      entity_key: 'IDP-CORE',
      entity_type: 'IDENTITY',
      name_ar: 'Canonical identity service',
      lifecycle_state: 'ACTIVE',
      properties: {},
    },
  ],
  relationships: [
    {
      id: '01900000-0000-7000-8000-000000000113',
      enterprise_id: '01900000-0000-7000-8000-000000000101',
      source_entity_id: '01900000-0000-7000-8000-000000000111',
      target_entity_id: '01900000-0000-7000-8000-000000000112',
      relationship_type: 'AUTHENTICATES_WITH',
      properties: { protocol: 'SIMULATED_OIDC' },
    },
  ],
  device_templates: [],
  digital_twins: [
    {
      id: twinRevision.digital_twin_id,
      slug: 'application-security-twin',
      name_ar: 'Application security Twin',
      provenance: 'SIMULATED',
      is_fixture: false,
      revisions: [twinRevision],
    },
  ],
};

const lab: LabItem = {
  id: '01900000-0000-7000-8000-000000000201',
  lab_id: '01900000-0000-7000-8000-000000000200',
  based_on_revision_id: null,
  slug: 'lab-branching-investigation',
  title_ar: 'Branching investigation Lab',
  revision: 1,
  status: 'PUBLISHED',
  environment_binding_mode: 'LAB_LOCAL',
  enterprise_id: null,
  baseline_id: null,
  environment_contract: {
    schema: 'cep.simulation.lab-environment-contract.v1',
    execution_model: 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION',
    required_capabilities: ['APPLICATION_LOGGING'],
  },
  digest: 'e'.repeat(64),
  configuration: { profile: 'ISOLATED' },
  validation: { result_schema: 'cep.lab-result.v1' },
  validation_report: {
    valid: true,
    task_graph: { parallel_task_count: 1, conditional_dependency_count: 1 },
  },
  validated_at: '2026-08-29T00:00:00Z',
  published_at: '2026-08-29T00:01:00Z',
  tasks: [
    {
      id: '01900000-0000-7000-8000-000000000211',
      task_key: 'OBSERVE',
      title_ar: 'Observe signal',
      objective: 'Observe the simulated request log.',
      permitted_tools: ['Browser', 'Event Viewer'],
      required_capabilities: ['APPLICATION_LOGGING'],
      required_role: 'ANALYST',
      expected_signals: ['HTTP_LOG'],
      validation_rule: { signal: 'HTTP_LOG', operator: 'EXISTS' },
      completion_weight: 1,
      is_optional: false,
    },
    {
      id: '01900000-0000-7000-8000-000000000212',
      task_key: 'TRACE',
      title_ar: 'Trace causality',
      objective: 'Trace state to event to telemetry.',
      permitted_tools: ['Event Viewer'],
      required_capabilities: ['APPLICATION_LOGGING'],
      expected_signals: ['HTTP_LOG'],
      validation_rule: { signal: 'HTTP_LOG', operator: 'EXISTS' },
      completion_weight: 1,
      is_optional: false,
    },
    {
      id: '01900000-0000-7000-8000-000000000213',
      task_key: 'VERIFY',
      title_ar: 'Verify branch',
      objective: 'Verify an independent conditional branch.',
      permitted_tools: ['Browser'],
      required_capabilities: ['APPLICATION_LOGGING'],
      expected_signals: ['HTTP_LOG'],
      validation_rule: { signal: 'HTTP_LOG', operator: 'EXISTS' },
      completion_weight: 1,
      is_optional: false,
    },
  ],
  task_dependencies: [
    {
      id: '01900000-0000-7000-8000-000000000221',
      predecessor_task_id: '01900000-0000-7000-8000-000000000211',
      successor_task_id: '01900000-0000-7000-8000-000000000212',
      dependency_type: 'REQUIRED',
      condition: null,
    },
    {
      id: '01900000-0000-7000-8000-000000000222',
      predecessor_task_id: '01900000-0000-7000-8000-000000000211',
      successor_task_id: '01900000-0000-7000-8000-000000000213',
      dependency_type: 'CONDITIONAL',
      condition: { when: 'HTTP_LOG_PRESENT' },
    },
  ],
  device_template_references: [],
  can_prepare: false,
  provenance: 'SIMULATED',
};

describe('Simulation definition correction workspace', () => {
  it('renders corrected Twin components and typed relationships and emits selected context', async () => {
    const surface = mount(EnterpriseSurface, {
      props: { enterprise, selectedContextId: null },
    });
    const center = surface.get('[data-testid="enterprise-topology"]');
    expect(center.findAll('[data-testid="topology-node"]')).toHaveLength(2);
    expect(center.findAll('[data-testid="topology-link"]')).toHaveLength(1);
    expect(center.text()).toContain('CONNECTS_TO');

    await center.findAll('[data-testid="topology-node"]')[0].trigger('click');
    expect(surface.emitted('selectContext')).toEqual([[twinRevision.components?.[0].id]]);

    const context = mount(EnterpriseContext, {
      props: {
        enterprise,
        selectedContext: {
          context_type: 'DIGITAL_TWIN_COMPONENT',
          component_key: 'CRM-APPLICATION',
          ownership_scope: 'ENTERPRISE_ENTITY',
          name_ar: 'CRM application component',
        },
      },
    });
    const componentContext = context.get('[data-testid="enterprise-selected-context"]');
    expect(componentContext.text()).toContain('DIGITAL_TWIN_COMPONENT');
    expect(componentContext.text()).toContain('ENTERPRISE_ENTITY');
    expect(componentContext.text()).toContain('CRM-APPLICATION');

    const structure = mount(StructureList, {
      props: {
        title: 'Enterprise structure',
        description: 'Canonical objects and typed Twin definitions.',
        items: [],
        selectedId: null,
        selectedContextId: null,
        groups: [
          {
            label: 'Canonical Enterprise Entities',
            kind: 'enterprise-entity',
            items: [{ id: enterprise.entities?.[0].id ?? '', label: 'Canonical CRM application' }],
          },
        ],
      },
    });
    await structure.get('[data-structure-kind="enterprise-entity"] button').trigger('click');
    expect(structure.emitted('selectContext')).toEqual([
      [enterprise.entities?.[0].id, 'enterprise-entity'],
    ]);

    const toolbar = mount(WorkspaceToolbar, {
      props: {
        section: 'enterprise',
        scenario: null,
        lab: null,
        twinRevision,
        run: null,
        result: null,
        pending: false,
      },
    });
    const publish = toolbar
      .findAll('button')
      .find((button) => button.text().includes('Publish immutable revision'));
    expect(publish).toBeDefined();
    expect(publish?.attributes('disabled')).toBeUndefined();
    await publish?.trigger('click');
    expect(toolbar.emitted('definitionAction')).toEqual([['digital-twin', 'publish']]);
  });

  it('renders the persisted non-linear Lab graph and shows selected task detail only after selection', async () => {
    const surface = mount(LabSurface, { props: { lab, selectedTaskId: null } });
    const center = surface.get('[data-testid="lab-task-graph"]');
    expect(center.findAll('[data-testid="lab-task-node"]')).toHaveLength(3);
    expect(center.get('[data-testid="lab-task-dependencies"]').findAll('li')).toHaveLength(2);
    expect(center.text()).toContain('REQUIRED');
    expect(center.text()).toContain('CONDITIONAL');

    await center.findAll('[data-testid="lab-task-node"]')[1].trigger('click');
    expect(surface.emitted('selectTask')).toEqual([[lab.tasks?.[1].id]]);

    const context = mount(LabContext, {
      props: { lab, selectedTask: lab.tasks?.[1] ?? null },
    });
    const taskContext = context.get('[data-testid="lab-selected-task"]');
    expect(taskContext.text()).toContain('Trace state to event to telemetry.');
    expect(taskContext.text()).toContain('Event Viewer');
    expect(taskContext.text()).toContain('APPLICATION_LOGGING');

    const toolbar = mount(WorkspaceToolbar, {
      props: {
        section: 'labs',
        scenario: null,
        lab,
        twinRevision: null,
        run: null,
        result: null,
        pending: false,
      },
    });
    const prepareForm = toolbar.get('[data-testid="lab-prepare-controls"]');
    expect(prepareForm.get('button[type="submit"]').attributes('disabled')).toBeDefined();
    await prepareForm.trigger('submit');
    expect(toolbar.emitted('prepareLab')).toBeUndefined();

    const clone = prepareForm
      .findAll('button')
      .find((button) => button.text().includes('Clone as new revision'));
    expect(clone).toBeDefined();
    expect(clone?.attributes('disabled')).toBeUndefined();
    await clone?.trigger('click');
    expect(toolbar.emitted('definitionAction')).toEqual([['lab', 'clone']]);
  });

  it('exposes the Lab draft validation transition without enabling runtime preparation', async () => {
    const draft: LabItem = {
      ...lab,
      id: '01900000-0000-7000-8000-000000000299',
      status: 'DRAFT',
      validated_at: null,
      published_at: null,
      validation_report: {},
      can_prepare: false,
    };
    const toolbar = mount(WorkspaceToolbar, {
      props: {
        section: 'labs',
        scenario: null,
        lab: draft,
        twinRevision: null,
        run: null,
        result: null,
        pending: false,
      },
    });

    const form = toolbar.get('[data-testid="lab-prepare-controls"]');
    expect(form.get('button[type="submit"]').attributes('disabled')).toBeDefined();
    const validate = form
      .findAll('button')
      .find((button) => button.text().includes('Validate definition'));
    expect(validate).toBeDefined();
    expect(validate?.attributes('disabled')).toBeUndefined();
    await validate?.trigger('click');
    expect(toolbar.emitted('definitionAction')).toEqual([['lab', 'validate']]);
  });
});
