export type SimulationSection = 'enterprise' | 'scenarios' | 'labs' | 'runs' | 'results';
export type ResultMode = 'overview' | 'replay' | 'aar' | 'compare' | 'candidate-evidence';
export type RunWorkspaceMode = 'preflight' | 'operations';
export type ResultProjectionStatus =
  | 'READY'
  | 'LOADING'
  | 'EMPTY'
  | 'UNAVAILABLE'
  | 'ERROR'
  | 'INITIAL_REVISION_REQUIRED'
  | 'LINEAGE_RECONCILIATION_REQUIRED'
  | 'SEMANTIC_PROJECTOR_UNAVAILABLE'
  | 'PARTIAL_ANALYTICS';

export type NavigationItem = { key: SimulationSection; label: string; href: string };
export type JsonMap = Record<string, unknown>;

export type TopologyNode = {
  id: string;
  label: string;
  kind: string;
  raw: JsonMap;
};

export type TopologyLink = {
  from: string;
  to: string;
  label: string | null;
};

export type OrderedDefinitionItem = {
  id: string;
  label: string;
  ordinal: number;
  raw: unknown;
};

export type EventItem = {
  sequence: number;
  event_type: string;
  payload: JsonMap;
  actor_id: string;
  occurred_at: string;
};

export type SnapshotItem = {
  id: string;
  sequence: number;
  event_sequence: number;
  snapshot_kind: 'RUN_PREPARATION' | 'OPERATION' | 'FINALIZATION' | 'MANUAL';
  state: JsonMap;
  state_digest: string;
  captured_by: string;
  captured_at: string;
};

export type CheckpointItem = {
  id: string;
  sequence: number;
  source_snapshot_id: string;
  state: JsonMap;
  state_digest: string;
  restorable: boolean;
  created_by: string;
  created_at: string;
};

export type DigitalTwinRevisionItem = {
  id: string;
  digital_twin_id: string;
  revision: number;
  status?: 'DRAFT' | 'VALIDATED' | 'PUBLISHED';
  based_on_revision_id?: string | null;
  digest: string;
  topology: JsonMap;
  behavior_model: JsonMap;
  validation_report?: JsonMap;
  validated_at?: string | null;
  published_at?: string | null;
  components?: DigitalTwinComponentItem[];
  relationships?: DigitalTwinRelationshipItem[];
  baselines: Array<{
    id: string;
    digital_twin_id: string;
    digital_twin_revision_id: string;
    revision: number;
    digest: string;
    state: JsonMap;
  }>;
};

export type EnterpriseEntityItem = {
  id: string;
  enterprise_id: string;
  entity_key: string;
  entity_type: string;
  name_ar: string;
  name_en?: string | null;
  lifecycle_state: string;
  properties: JsonMap;
};

export type EnterpriseRelationshipItem = {
  id: string;
  enterprise_id: string;
  source_entity_id: string;
  target_entity_id: string;
  relationship_type: string;
  properties: JsonMap;
};

export type DeviceTemplateRevisionItem = {
  id: string;
  device_template_id: string;
  revision: number;
  status: 'DRAFT' | 'VALIDATED' | 'PUBLISHED';
  capabilities: string[];
  state_model: JsonMap;
  behavior_rules: JsonMap;
  validation_report: JsonMap;
  digest: string;
};

export type DeviceTemplateItem = {
  id: string;
  template_key: string;
  device_type: string;
  name_ar: string;
  revisions: DeviceTemplateRevisionItem[];
};

export type DigitalTwinComponentItem = {
  id: string;
  component_key: string;
  ownership_scope: 'ENTERPRISE_ENTITY' | 'SIMULATION_LOCAL';
  enterprise_entity_id?: string | null;
  device_template_revision_id?: string | null;
  name_ar: string;
  simulation_definition: JsonMap;
};

export type DigitalTwinRelationshipItem = {
  id: string;
  source_component_id: string;
  target_component_id: string;
  relationship_type: string;
  properties: JsonMap;
};

export type DigitalTwinItem = {
  id: string;
  slug: string;
  name_ar: string;
  provenance: string;
  is_fixture: boolean;
  revisions: DigitalTwinRevisionItem[];
};

export type EnterpriseItem = {
  id: string;
  slug: string;
  name_ar: string;
  description_ar?: string | null;
  definition: JsonMap;
  provenance: string;
  is_fixture: boolean;
  entities?: EnterpriseEntityItem[];
  relationships?: EnterpriseRelationshipItem[];
  device_templates?: DeviceTemplateItem[];
  digital_twins: DigitalTwinItem[];
};

export type PreparationTarget = {
  enterprise_id: string;
  enterprise_name_ar: string;
  digital_twin_id: string;
  digital_twin_name_ar: string;
  digital_twin_revision_id: string;
  baseline_id: string;
  baseline_revision: number;
  baseline_digest: string;
  capabilities: string[];
  provenance: string;
  source_fixture: boolean;
};

export type ScenarioItem = {
  id: string;
  slug: string;
  title_ar: string;
  revision: number;
  digest: string;
  environment_contract: JsonMap & {
    schema?: string;
    execution_model?: string;
    required_capabilities?: string[];
  };
  orchestration: JsonMap;
  validation: JsonMap;
  provenance: string;
  preparation_targets: PreparationTarget[];
  lab_module_references: Array<{
    reference_id: string;
    module_key: string;
    ordinal: number;
    policy: JsonMap;
    lab_definition_id: string;
    lab_title_ar: string;
  }>;
};

export type LabItem = {
  id: string;
  lab_id?: string;
  based_on_revision_id?: string | null;
  slug: string;
  title_ar: string;
  revision: number;
  status?: 'DRAFT' | 'VALIDATED' | 'PUBLISHED';
  environment_binding_mode?: 'LAB_LOCAL' | 'ENTERPRISE_BASELINE';
  enterprise_id?: string | null;
  baseline_id: string | null;
  environment_contract?: JsonMap & {
    schema?: string;
    execution_model?: string;
    required_capabilities?: string[];
  };
  digest: string;
  configuration: JsonMap;
  validation: JsonMap;
  validation_report?: JsonMap;
  validated_at?: string | null;
  published_at?: string | null;
  tasks?: LabTaskItem[];
  task_dependencies?: LabTaskDependencyItem[];
  device_template_references?: Array<{
    id: string;
    reference_key: string;
    device_template_revision_id: string;
    required_capabilities: string[];
    parameters: JsonMap;
  }>;
  can_prepare?: boolean;
  provenance: string;
};

export type LabTaskItem = {
  id: string;
  task_key: string;
  title_ar: string;
  objective: string;
  permitted_tools: string[];
  required_capabilities: string[];
  required_role?: string | null;
  expected_signals: unknown[];
  validation_rule: JsonMap;
  completion_weight: number;
  is_optional: boolean;
};

export type LabTaskDependencyItem = {
  id: string;
  predecessor_task_id: string;
  successor_task_id: string;
  dependency_type: 'REQUIRED' | 'CONDITIONAL';
  condition?: JsonMap | null;
};

export type RunPreflightTarget = PreparationTarget & {
  status?: 'COMPATIBLE' | 'INCOMPATIBLE';
  required_capabilities?: string[];
  missing_capabilities?: string[];
};

export type RunPreflightDefinition = {
  status: 'READY' | 'INCOMPATIBLE' | 'UNAVAILABLE' | 'ERROR';
  run_type: 'Scenario Run' | 'Standalone Lab Run';
  definition_id: string;
  definition_slug?: string;
  definition_title_ar?: string;
  definition_revision?: number;
  definition_status?: string;
  definition_digest?: string;
  environment_contract_digest?: string;
  environment_binding_mode?: string;
  execution_model?: string;
  required_capabilities?: string[];
  available_capabilities?: string[];
  missing_capabilities?: string[];
  targets?: RunPreflightTarget[];
  target?: RunPreflightTarget | null;
  provenance?: string;
  source_fixture?: boolean;
  blocking_reason?: string | null;
};

export type RunPreflightWorkspace = {
  status: 'READY' | 'EMPTY' | 'UNAVAILABLE' | 'ERROR';
  execution_model: 'CEP_INTERNAL_HIGH_FIDELITY_SIMULATION';
  default_seed: number;
  execution_modes: string[];
  scenario_definitions: RunPreflightDefinition[];
  lab_definitions: RunPreflightDefinition[];
};

export type RunWorkspaceProjection = {
  status: 'READY' | 'EMPTY' | 'UNAVAILABLE' | 'ERROR';
  mode: RunWorkspaceMode;
  available_modes: RunWorkspaceMode[];
  preflight_type: 'scenario' | 'standalone-lab';
  definition_id: string | null;
};

export type RuntimeState = JsonMap & {
  engine?: string;
  trace_digest?: string;
  telemetry?: Record<string, unknown>;
  validation?: Record<string, unknown>;
};

export type RunItem = {
  id: string;
  run_type: 'Scenario Run' | 'Standalone Lab Run';
  lifecycle: string;
  definition_title_ar: string;
  enterprise_id: string;
  digital_twin_id: string;
  digital_twin_revision_id: string;
  baseline_id: string;
  scenario_definition_id?: string | null;
  standalone_lab_definition_id?: string | null;
  seed: number;
  execution_policies: JsonMap;
  runtime_state: RuntimeState;
  input_digest: string;
  definition_digest: string;
  provenance: string;
  source_fixture: boolean;
  prepared_at: string;
  ready_at?: string | null;
  started_at?: string | null;
  completed_at?: string | null;
  stopped_at?: string | null;
  failed_at?: string | null;
  available_actions: string[];
  events: EventItem[];
  operations: Array<{
    id: string;
    operation_key: string;
    verb: string;
    target: string;
    input: JsonMap;
    telemetry: JsonMap;
    actor_id: string;
  }>;
  snapshots: SnapshotItem[];
  checkpoints: CheckpointItem[];
  result_id?: string | null;
};

export type ResultRevisionProjection = {
  id: string;
  result_id: string;
  revision_digest: string;
  base_revision_id: string | null;
  correction_reason?: string | null;
  actor_identity?: string | null;
  created_at: string;
  outcome?: string | null;
  score?: string | null;
  summary_ar?: string | null;
};

export type ResultCanonicalProjection = {
  result_id: string;
  run_id: string;
  result_revision: number;
  result_digest: string;
  provenance: string;
  source_fixture: boolean;
  sealed_by: string;
  sealed_at: string;
  run_type: string;
  run_lifecycle: string;
};

export type ResultLineageProjection = {
  status: ResultProjectionStatus;
  revision_count?: number;
  root_revision_id?: string | null;
  effective_revision_id?: string | null;
  revisions?: Array<{
    id: string;
    base_revision_id: string | null;
    revision_digest: string;
    actor_identity?: string | null;
    correction_reason?: string | null;
    created_at: string;
  }>;
};

export type ReplayProjectionEvent = EventItem & {
  source_ref: string;
  projection_status: 'READY' | 'UNAVAILABLE';
  operation_key?: string;
  state_at_point: {
    projection_scope: 'GOVERNED_OPERATION_CONTROLS_ONLY';
    controls: Record<string, boolean>;
  } | null;
};

export type ResultAnalyticsProjection = {
  status: ResultProjectionStatus;
  diagnostic_id?: string;
  overview: {
    status: ResultProjectionStatus;
    canonical: ResultCanonicalProjection;
    lineage: ResultLineageProjection;
    effective: ResultRevisionProjection | null;
  };
  replay: {
    status: ResultProjectionStatus;
    reason?: string;
    projector?: {
      availability: string;
      grammar_version: string | null;
      semantic_version: string | null;
      reason?: string;
    };
    events?: ReplayProjectionEvent[];
    operation_count?: number | null;
    write_behavior?: 'ZERO_WRITE_PROJECTION';
  };
  aar: {
    status: ResultProjectionStatus;
    reason?: string;
    facts?: Array<{
      id: string;
      kind: string;
      label_ar: string;
      value: unknown;
      source_ref: string;
      sequence?: number;
    }>;
    operation_count?: number | null;
    sealed_commentary?: {
      value: string;
      source_ref: string;
      classification: 'SEALED_RESULT_COMMENTARY';
    } | null;
    unavailable_sections?: Array<{ key: string; reason: string }>;
    source_policy?: string;
    write_behavior?: 'ZERO_WRITE_PROJECTION';
  };
  candidate_evidence: {
    status: ResultProjectionStatus;
    reason?: string;
    write_behavior?: 'ZERO_WRITE_SOURCE_PREVIEW';
    w04_state?: 'NOT_CREATED_OR_CLAIMED';
    envelope?: JsonMap;
  };
};

export type ResultCompareProjection = {
  status: ResultProjectionStatus;
  selection_valid: boolean;
  selected_result_ids: string[];
  selected_run_ids: string[];
  reason?: string;
  comparison_semantics?: string;
  write_behavior: 'ZERO_WRITE_PROJECTION';
  items: Array<{
    result_id: string;
    run_id: string;
    canonical_result_digest: string;
    effective_revision_id: string;
    effective_revision_digest: string;
  }>;
  dimensions: Array<{
    key: string;
    label_ar: string;
    value_type: string;
    source: string;
    status: 'READY' | 'N/A';
    compatible: boolean;
    values: Array<{
      result_id: string;
      run_id: string;
      value: string | number | boolean | null;
      display: string;
      availability: 'READY' | 'N/A';
      source_ref: string;
    }>;
  }>;
};

export type ResultContextSelection =
  | { kind: 'overview' }
  | { kind: 'replay-event'; event: ReplayProjectionEvent }
  | {
      kind: 'aar-fact';
      fact: {
        id: string;
        kind: string;
        label_ar: string;
        value: unknown;
        source_ref: string;
        sequence?: number;
      };
    }
  | { kind: 'compare-dimension'; dimension: ResultCompareProjection['dimensions'][number] }
  | { kind: 'candidate-evidence' };

export type ResultsWorkspaceProjection = {
  status: ResultProjectionStatus;
  mode: ResultMode;
  available_modes: ResultMode[];
  selected_result_id: string | null;
  compare_result_ids: string[];
  compare: ResultCompareProjection;
};

export type ResultItem = {
  id: string;
  run_id: string;
  run_type: 'Scenario Run' | 'Standalone Lab Run';
  run_lifecycle: string;
  outcome: string;
  score?: number | null;
  summary_ar: string;
  sealed_payload: JsonMap;
  replay_timeline: EventItem[];
  artifacts: unknown[];
  result_revision: number;
  result_digest: string;
  provenance: string;
  source_fixture: boolean;
  sealed_by: string;
  sealed_at: string;
  analytics: ResultAnalyticsProjection;
  legacy_history: {
    replay_compare: {
      id: string;
      integrity_match: boolean;
      sealed_result_digest: string;
      reconstructed_state_digest: string;
      reconstruction: JsonMap;
      actor_id: string;
      compared_at: string;
    } | null;
    candidate_evidence_handoff: {
      id: string;
      status: string;
      candidate_manifest: JsonMap;
      source_result_revision: number;
      source_result_digest: string;
      provenance: string;
      source_fixture: boolean;
      manifest_digest: string;
      created_by: string;
      intake_contract_ref?: string | null;
    } | null;
  };
};

export type WorkspaceProps = {
  section: SimulationSection;
  navigation: NavigationItem[];
  enterprises: EnterpriseItem[];
  scenarios: ScenarioItem[];
  labs: LabItem[];
  runs: RunItem[];
  results: ResultItem[];
  results_workspace: ResultsWorkspaceProjection | null;
  run_preflight: RunPreflightWorkspace | null;
  run_workspace: RunWorkspaceProjection | null;
  outcomes: string[];
};
