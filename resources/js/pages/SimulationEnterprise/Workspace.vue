<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import CepWorkspaceLayout from '../../layouts/CepWorkspaceLayout.vue';
import EnterpriseContext from './components/EnterpriseContext.vue';
import EnterpriseDeepDetail from './components/EnterpriseDeepDetail.vue';
import EnterpriseSurface from './components/EnterpriseSurface.vue';
import LabContext from './components/LabContext.vue';
import LabDeepDetail from './components/LabDeepDetail.vue';
import LabSurface from './components/LabSurface.vue';
import ResultContext from './components/ResultContext.vue';
import ResultDeepDetail from './components/ResultDeepDetail.vue';
import ResultSurface from './components/ResultSurface.vue';
import RunContext from './components/RunContext.vue';
import RunDeepDetail from './components/RunDeepDetail.vue';
import RunSurface from './components/RunSurface.vue';
import ScenarioContext from './components/ScenarioContext.vue';
import ScenarioDeepDetail from './components/ScenarioDeepDetail.vue';
import ScenarioSurface from './components/ScenarioSurface.vue';
import SimulationPrimaryNavigation from './components/SimulationPrimaryNavigation.vue';
import StructureList from './components/StructureList.vue';
import WorkspaceStatus from './components/WorkspaceStatus.vue';
import WorkspaceToolbar from './components/WorkspaceToolbar.vue';
import { orderedItems } from './projections';
import type {
  DigitalTwinRevisionItem,
  EnterpriseItem,
  JsonMap,
  LabItem,
  LabTaskItem,
  ResultItem,
  RunItem,
  ScenarioItem,
  WorkspaceProps,
} from './types';
import './workspace.css';

type RecordItem = EnterpriseItem | ScenarioItem | LabItem | RunItem | ResultItem;
type PostPayload = Parameters<typeof router.post>[1];

const props = defineProps<WorkspaceProps>();
const page = usePage<{ errors?: Record<string, string> }>();

const selectedId = ref<string | null>(null);
const selectedContextId = ref<string | null>(null);
const selectedContextKind = ref<string | null>(null);
const pendingAction = ref<string | null>(null);
const localError = ref<string | null>(null);
const bottomOpen = ref(false);

const records = computed<RecordItem[]>(() => {
  if (props.section === 'enterprise') return props.enterprises;
  if (props.section === 'scenarios') return props.scenarios;
  if (props.section === 'labs') return props.labs;
  if (props.section === 'runs') return props.runs;
  return props.results;
});

watch(
  records,
  (items) => {
    if (!items.length) {
      selectedId.value = null;
    } else if (!selectedId.value || !items.some((item) => item.id === selectedId.value)) {
      selectedId.value = items[0].id;
    }
  },
  { immediate: true },
);

watch(selectedId, () => {
  bottomOpen.value = false;
  selectedContextId.value = null;
  selectedContextKind.value = null;
});

const selectedEnterprise = computed(() =>
  props.section === 'enterprise'
    ? (props.enterprises.find((item) => item.id === selectedId.value) ?? null)
    : null,
);
const selectedScenario = computed(() =>
  props.section === 'scenarios'
    ? (props.scenarios.find((item) => item.id === selectedId.value) ?? null)
    : null,
);
const selectedLab = computed(() =>
  props.section === 'labs'
    ? (props.labs.find((item) => item.id === selectedId.value) ?? null)
    : null,
);
const selectedRun = computed(() =>
  props.section === 'runs'
    ? (props.runs.find((item) => item.id === selectedId.value) ?? null)
    : null,
);
const selectedResult = computed(() =>
  props.section === 'results'
    ? (props.results.find((item) => item.id === selectedId.value) ?? null)
    : null,
);

const selectedTwinRevision = computed<DigitalTwinRevisionItem | null>(() => {
  if (!selectedEnterprise.value || selectedContextKind.value !== 'digital-twin') return null;
  return (
    selectedEnterprise.value.digital_twins
      .flatMap((twin) => twin.revisions)
      .find((revision) => revision.id === selectedContextId.value) ?? null
  );
});

const selectedEnterpriseContext = computed<JsonMap | null>(() => {
  const enterprise = selectedEnterprise.value;
  const contextId = selectedContextId.value;
  if (!enterprise || !contextId) return null;

  const entity = enterprise.entities?.find(
    (item) => item.id === contextId || item.entity_key === contextId,
  );
  if (entity) return { context_type: 'ENTERPRISE_ENTITY', ...entity };
  const enterpriseRelationship = enterprise.relationships?.find((item) => item.id === contextId);
  if (enterpriseRelationship) {
    return {
      context_type: 'ENTERPRISE_RELATIONSHIP',
      label: enterpriseRelationship.relationship_type,
      ...enterpriseRelationship,
      raw: enterpriseRelationship.properties,
    };
  }

  for (const twin of enterprise.digital_twins) {
    for (const revision of twin.revisions) {
      if (revision.id === contextId) {
        return {
          context_type: 'DIGITAL_TWIN_REVISION',
          twin_name_ar: twin.name_ar,
          ...revision,
        };
      }
      const component = revision.components?.find(
        (item) => item.id === contextId || item.component_key === contextId,
      );
      if (component) return { context_type: 'DIGITAL_TWIN_COMPONENT', ...component };
      const relationship = revision.relationships?.find((item) => item.id === contextId);
      if (relationship) {
        return {
          context_type: 'DIGITAL_TWIN_RELATIONSHIP',
          label: relationship.relationship_type,
          ...relationship,
          raw: relationship.properties,
        };
      }
      const node = orderedItems(revision.topology.nodes, 'Node').find(
        (item) => item.id === contextId,
      );
      if (node) {
        return {
          context_type: 'DIGITAL_TWIN_TOPOLOGY_NODE',
          id: node.id,
          label: node.label,
          raw: node.raw,
        };
      }
    }
  }
  return null;
});

const selectedLabTask = computed<LabTaskItem | null>(() => {
  if (!selectedLab.value || !selectedContextId.value) return null;
  return selectedLab.value.tasks?.find((task) => task.id === selectedContextId.value) ?? null;
});

const pageTitle = computed(
  () =>
    ({
      enterprise: 'المؤسسة والنسخة الرقمية',
      scenarios: 'استوديو السيناريو',
      labs: 'المختبرات',
      runs: 'التشغيلات',
      results: 'النتائج وإعادة العرض',
    })[props.section],
);

const structureTitle = computed(
  () =>
    ({
      enterprise: 'المؤسسات المنشورة',
      scenarios: 'مكتبة السيناريوهات',
      labs: 'تعريفات المختبرات',
      runs: 'سجل التشغيلات',
      results: 'الأرشيف التاريخي',
    })[props.section],
);

const structureDescription = computed(
  () =>
    ({
      enterprise: 'اختر مؤسسة لاستكشاف Digital Twins ومراجعاتها.',
      scenarios: 'اختر عقدًا بيئيًا محمولًا لتجهيزه.',
      labs: 'اختر مختبرًا مستقلًا مثبت الهدف.',
      runs: 'اختر تشغيلًا لإدارة دورة حياته.',
      results: 'اختر نتيجة مختومة للقراءة وReplay.',
    })[props.section],
);

const structureItems = computed(() =>
  records.value.map((item) => {
    if ('name_ar' in item) {
      return {
        id: item.id,
        title: item.name_ar,
        subtitle: item.slug,
        state: `${item.digital_twins.length} twins`,
      };
    }
    if ('lifecycle' in item) {
      return {
        id: item.id,
        title: item.definition_title_ar,
        subtitle: item.run_type,
        state: item.lifecycle,
      };
    }
    if ('outcome' in item) {
      return {
        id: item.id,
        title: `Result · ${item.run_id.slice(0, 8)}`,
        subtitle: item.run_type,
        state: item.outcome,
      };
    }
    return {
      id: item.id,
      title: item.title_ar,
      subtitle: item.slug,
      state: `REV ${item.revision}`,
    };
  }),
);

const structureGroups = computed(() => {
  if (selectedEnterprise.value) {
    const enterpriseGroups = [
      {
        label: 'Canonical Enterprise Entities',
        kind: 'enterprise-entity',
        items: (selectedEnterprise.value.entities ?? []).map((entity) => ({
          id: entity.id,
          label: entity.name_ar,
          meta: entity.entity_type,
        })),
      },
    ];
    if (selectedEnterprise.value.relationships?.length) {
      enterpriseGroups.push({
        label: 'Typed Enterprise Relationships',
        kind: 'enterprise-relationship',
        items: selectedEnterprise.value.relationships.map((relationship) => ({
          id: relationship.id,
          label: relationship.relationship_type,
          meta: 'TYPED',
        })),
      });
    }
    return enterpriseGroups.concat(
      selectedEnterprise.value.digital_twins.flatMap((twin) => [
        {
          label: twin.name_ar,
          kind: 'digital-twin',
          items: twin.revisions.map((revision) => ({
            id: revision.id,
            label: `Revision ${revision.revision}`,
            meta: `${revision.status ?? 'PUBLISHED'} - ${revision.baselines.length} BASELINES`,
          })),
        },
        ...twin.revisions.flatMap((revision) => [
          ...(revision.components?.length
            ? [
                {
                  label: `Revision ${revision.revision} components`,
                  kind: 'digital-twin-component',
                  items: revision.components.map((component) => ({
                    id: component.id,
                    label: component.name_ar,
                    meta: component.ownership_scope,
                  })),
                },
              ]
            : []),
          ...(revision.relationships?.length
            ? [
                {
                  label: `Revision ${revision.revision} relationships`,
                  kind: 'digital-twin-relationship',
                  items: revision.relationships.map((relationship) => ({
                    id: relationship.id,
                    label: relationship.relationship_type,
                    meta: 'TYPED',
                  })),
                },
              ]
            : []),
        ]),
      ]),
    );
  }
  if (selectedScenario.value) {
    const phases = orderedItems(selectedScenario.value.orchestration.phases, 'Phase');
    return [
      {
        label: 'مراحل orchestration',
        kind: 'phase',
        items: phases.map((phase) => ({
          id: phase.id,
          label: `Phase ${String(phase.ordinal).padStart(2, '0')}`,
          meta: phase.ordinal === 1 ? 'ENTRY' : undefined,
        })),
      },
      {
        label: 'Lab Module References',
        kind: 'scenario-module',
        items: selectedScenario.value.lab_module_references.map((module) => ({
          id: module.reference_id,
          label: module.lab_title_ar,
          meta: module.module_key,
        })),
      },
    ];
  }
  if (selectedLab.value) {
    const persistedTasks = selectedLab.value.tasks ?? [];
    const taskItems = persistedTasks.length
      ? persistedTasks.map((task, index) => ({
          id: task.id,
          label: task.title_ar,
          meta: `${task.task_key} - ${index + 1}`,
        }))
      : orderedItems(selectedLab.value.configuration.steps, 'Task').map((step) => ({
          id: step.id,
          label: `Task ${String(step.ordinal).padStart(2, '0')}`,
          meta: step.ordinal === 1 ? 'ENTRY' : undefined,
        }));
    return [
      {
        label: 'Task Graph',
        kind: 'lab-step',
        items: taskItems,
      },
      {
        label: 'Definition anchors',
        kind: 'lab-anchor',
        items: [
          { id: 'baseline', label: 'Baseline', meta: 'PINNED' },
          { id: 'validation', label: 'Validation', meta: 'CONTRACT' },
        ],
      },
    ];
  }
  if (selectedRun.value) {
    return [
      {
        label: 'Operational sequence',
        kind: 'run-stream',
        items: [
          { id: 'events', label: 'Event stream', meta: String(selectedRun.value.events.length) },
          {
            id: 'operations',
            label: 'Operations',
            meta: String(selectedRun.value.operations.length),
          },
          {
            id: 'snapshots',
            label: 'Runtime Snapshots',
            meta: String(selectedRun.value.snapshots.length),
          },
          {
            id: 'checkpoints',
            label: 'Prepared Checkpoints',
            meta: String(selectedRun.value.checkpoints.length),
          },
        ],
      },
    ];
  }
  if (selectedResult.value) {
    return [
      {
        label: 'Sealed result structure',
        kind: 'result-history',
        items: [
          {
            id: 'replay',
            label: 'Replay timeline',
            meta: String(selectedResult.value.replay_timeline.length),
          },
          { id: 'payload', label: 'Frozen payload', meta: 'SEALED' },
          {
            id: 'artifacts',
            label: 'Runtime artifacts',
            meta: String(selectedResult.value.artifacts.length),
          },
          { id: 'provenance', label: 'Provenance', meta: selectedResult.value.provenance },
        ],
      },
    ];
  }
  return [];
});

const serverError = computed(
  () => page.props.errors?.simulation ?? Object.values(page.props.errors ?? {})[0] ?? null,
);

const temporaryLabel = computed(
  () =>
    ({
      enterprise: 'البنية والتعريفات الخام',
      scenarios: 'Environment Contract وOrchestration الخام',
      labs: 'إعداد المختبر والتحقق الخام',
      runs: 'Runtime State / Snapshot Payloads / Operations',
      results: 'Frozen Payload / Artifacts / Reconstruction',
    })[props.section],
);

function post(path: string, data: PostPayload | undefined, action: string): void {
  if (pendingAction.value !== null) return;
  localError.value = null;
  pendingAction.value = action;
  router.post(path, data, {
    preserveScroll: true,
    onError: (errors) => {
      localError.value = String(
        errors.simulation ?? Object.values(errors)[0] ?? 'تعذر إكمال الإجراء.',
      );
    },
    onFinish: () => {
      pendingAction.value = null;
    },
  });
}

function prepareScenario(payload: { baseline_id: string; seed: number; mode: string }): void {
  if (!selectedScenario.value) return;
  post(`/simulation/scenarios/${selectedScenario.value.id}/runs`, payload, 'prepare-scenario');
}

function prepareLab(payload: { seed: number; mode: string }): void {
  if (!selectedLab.value) return;
  post(`/simulation/labs/${selectedLab.value.id}/runs`, payload, 'prepare-lab');
}

function definitionAction(target: 'lab' | 'digital-twin', action: string): void {
  if (target === 'lab' && selectedLab.value) {
    post(`/simulation/labs/${selectedLab.value.id}/${action}`, undefined, `${target}-${action}`);
  }
  if (target === 'digital-twin' && selectedTwinRevision.value) {
    post(
      `/simulation/digital-twin-revisions/${selectedTwinRevision.value.id}/${action}`,
      undefined,
      `${target}-${action}`,
    );
  }
}

function selectContext(id: string, kind: string): void {
  selectedContextId.value = id;
  selectedContextKind.value = kind;
}

function runAction(action: string): void {
  if (!selectedRun.value) return;
  post(`/simulation/runs/${selectedRun.value.id}/${action}`, undefined, action);
}

function applyOperation(value: boolean): void {
  if (!selectedRun.value) return;
  post(
    `/simulation/runs/${selectedRun.value.id}/operations`,
    {
      operation_key: `ui:${selectedRun.value.id}:${Date.now()}`,
      verb: 'SET_CONTROL_STATE',
      target: 'IDENTITY_MFA',
      value,
    },
    'operate',
  );
}

function sealResult(payload: { outcome: string; summary_ar: string; score: number | null }): void {
  if (!selectedRun.value) return;
  post(`/simulation/runs/${selectedRun.value.id}/result`, payload, 'seal-result');
}

function replayCompare(): void {
  if (!selectedResult.value) return;
  post(`/simulation/results/${selectedResult.value.id}/replay-compare`, undefined, 'replay');
}

function createHandoff(claim: string): void {
  if (!selectedResult.value) return;
  post(
    `/simulation/results/${selectedResult.value.id}/candidate-evidence-handoff`,
    { claim_ar: claim, artifact_refs: [], intake_contract_ref: 'progress-evidence-intake:v1' },
    'candidate-handoff',
  );
}
</script>

<template>
  <Head :title="`CEP — ${pageTitle}`" />
  <div class="workspace sim-workspace" :aria-busy="pendingAction !== null">
    <CepWorkspaceLayout
      active-destination="simulation"
      :temporary-workspace-open="bottomOpen"
      :temporary-workspace-label="temporaryLabel"
      @close-temporary-workspace="bottomOpen = false"
    >
      <template #primaryNavigation>
        <SimulationPrimaryNavigation :navigation="navigation" :section="section" />
      </template>

      <template #top>
        <div class="sim-top-stack">
          <WorkspaceToolbar
            :section="section"
            :scenario="selectedScenario"
            :lab="selectedLab"
            :twin-revision="selectedTwinRevision"
            :run="selectedRun"
            :result="selectedResult"
            :pending="pendingAction !== null"
            @prepare-scenario="prepareScenario"
            @prepare-lab="prepareLab"
            @run-action="runAction"
            @definition-action="definitionAction"
            @replay="replayCompare"
            @open-bottom="bottomOpen = true"
          />
          <WorkspaceStatus :pending-action="pendingAction" :error="localError || serverError" />
        </div>
      </template>

      <template #left>
        <StructureList
          :title="structureTitle"
          :description="structureDescription"
          :items="structureItems"
          :selected-id="selectedId"
          :selected-context-id="selectedContextId"
          :groups="structureGroups"
          @select="selectedId = $event"
          @select-context="selectContext"
        />
      </template>

      <EnterpriseSurface
        v-if="section === 'enterprise'"
        :enterprise="selectedEnterprise"
        :selected-context-id="selectedContextId"
        @select-context="selectContext($event, 'enterprise-object')"
      />
      <ScenarioSurface v-else-if="section === 'scenarios'" :scenario="selectedScenario" />
      <LabSurface
        v-else-if="section === 'labs'"
        :lab="selectedLab"
        :selected-task-id="selectedContextKind === 'lab-step' ? selectedContextId : null"
        @select-task="selectContext($event, 'lab-step')"
      />
      <RunSurface v-else-if="section === 'runs'" :run="selectedRun" />
      <ResultSurface v-else :result="selectedResult" />

      <template #right>
        <EnterpriseContext
          v-if="section === 'enterprise'"
          :enterprise="selectedEnterprise"
          :selected-context="selectedEnterpriseContext"
        />
        <ScenarioContext v-else-if="section === 'scenarios'" :scenario="selectedScenario" />
        <LabContext
          v-else-if="section === 'labs'"
          :lab="selectedLab"
          :selected-task="selectedLabTask"
        />
        <RunContext
          v-else-if="section === 'runs'"
          :run="selectedRun"
          :pending="pendingAction !== null"
          :outcomes="outcomes"
          @operate="applyOperation"
          @seal="sealResult"
        />
        <ResultContext
          v-else
          :result="selectedResult"
          :pending="pendingAction !== null"
          @create-handoff="createHandoff"
        />
      </template>

      <template #bottom>
        <EnterpriseDeepDetail v-if="section === 'enterprise'" :enterprise="selectedEnterprise" />
        <ScenarioDeepDetail v-else-if="section === 'scenarios'" :scenario="selectedScenario" />
        <LabDeepDetail v-else-if="section === 'labs'" :lab="selectedLab" />
        <RunDeepDetail v-else-if="section === 'runs'" :run="selectedRun" />
        <ResultDeepDetail v-else :result="selectedResult" />
      </template>
    </CepWorkspaceLayout>
  </div>
</template>
