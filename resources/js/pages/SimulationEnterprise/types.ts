export type NavigationItem = { key: string; label: string; href: string };
export type JsonMap = Record<string, unknown>;

export type EventItem = {
  sequence: number;
  event_type: string;
  payload: JsonMap;
  occurred_at: string;
};

export type SnapshotItem = {
  id: string;
  sequence: number;
  event_sequence: number;
  state_digest: string;
  captured_at: string;
};

export type EnterpriseItem = {
  id: string;
  slug: string;
  name_ar: string;
  description_ar?: string | null;
  definition: JsonMap;
  is_fixture: boolean;
  digital_twin_revision?: {
    id: string;
    revision: number;
    digest: string;
    topology: JsonMap;
  } | null;
  baseline?: { id: string; revision: number; digest: string; state: JsonMap } | null;
};

export type ScenarioItem = {
  id: string;
  slug: string;
  title_ar: string;
  revision: number;
  baseline_id: string;
  digest: string;
  orchestration: JsonMap;
  validation: JsonMap;
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
  slug: string;
  title_ar: string;
  revision: number;
  baseline_id: string;
  digest: string;
  configuration: JsonMap;
  validation: JsonMap;
};

export type RuntimeState = JsonMap & {
  engine?: string;
  trace_digest?: string;
  telemetry?: Record<string, unknown>;
  validation?: Record<string, unknown>;
};

export type RunItem = {
  id: string;
  run_type: string;
  lifecycle: string;
  definition_title_ar: string;
  enterprise_id: string;
  digital_twin_revision_id: string;
  baseline_id: string;
  scenario_definition_id?: string | null;
  standalone_lab_definition_id?: string | null;
  seed: number;
  execution_policies: JsonMap;
  runtime_state: RuntimeState;
  input_digest: string;
  available_actions: string[];
  events: EventItem[];
  snapshots: SnapshotItem[];
  result_id?: string | null;
};

export type ResultItem = {
  id: string;
  run_id: string;
  run_type: string;
  run_lifecycle: string;
  outcome: string;
  score?: number | null;
  summary_ar: string;
  sealed_payload: JsonMap;
  replay_timeline: EventItem[];
  artifacts: unknown[];
  sealed_at: string;
  candidate_evidence_handoff?: {
    id: string;
    status: string;
    candidate_manifest: JsonMap;
    intake_contract_ref?: string | null;
  } | null;
};

export type WorkspaceRecord = EnterpriseItem | ScenarioItem | LabItem | RunItem | ResultItem;
export type FieldEntry = { key: string; value: string };
