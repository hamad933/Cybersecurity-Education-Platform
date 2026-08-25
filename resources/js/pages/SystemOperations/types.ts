export type Surface =
  | 'health'
  | 'processing'
  | 'validation'
  | 'ai-bridge'
  | 'backups'
  | 'audit'
  | 'releases'
  | 'configuration';

export type Counts = Record<string, number>;
export type CheckMap = Record<string, string>;

export interface ReleaseCheck {
  status: string;
  detail: string;
}

export interface ReleaseGate {
  ready: boolean;
  checks: Record<string, ReleaseCheck>;
}

export interface ProcessingRun {
  id: string;
  type: string;
  input_digest: string;
  status: string;
  attempt_count: number;
  max_attempts: number;
  worker_identifier: string | null;
  started_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  error_category: string | null;
  safe_error_message: string | null;
  created_at: string;
  updated_at?: string;
}

export interface OutboxMessage {
  id: string;
  type: string;
  producer_module: string;
  correlation_id: string;
  dispatch_state: string;
  attempts: number;
  occurred_at: string;
  next_attempt_at: string | null;
  dispatched_at: string | null;
}

export interface PackageRecord {
  id: string;
  package_type: string;
  schema_version?: number;
  owner_module: string;
  scope?: unknown;
  manifest?: unknown;
  package_digest: string;
  status: string;
  created_at: string;
}

export interface SourceImport {
  id: string;
  original_name: string;
  detected_media_type: string;
  extension: string;
  size_bytes: number;
  sha256: string;
  status: string;
  rejection_code: string | null;
  created_at: string;
}

export interface PromptRevision {
  id: string;
  prompt_package_id: string;
  revision: number;
  portable_package_id: string;
  input_digest: string;
  declared_scope: unknown;
  exported_at: string;
  prompt_purpose: string;
  prompt_status: string;
  prompt_current_revision: number;
  package_type: string;
  package_digest: string;
  package_scope: unknown;
  package_manifest: unknown;
  package_status: string;
}

export interface AiResult {
  id: string;
  prompt_package_revision_id: string;
  portable_package_id: string;
  result_digest: string;
  structured_result: unknown;
  status: string;
  imported_at: string;
  prompt_package_id: string;
  prompt_revision: number;
  prompt_input_digest: string;
  declared_scope: unknown;
  prompt_portable_package_id: string;
  prompt_purpose: string;
  prompt_status: string;
  returned_package_type: string;
  returned_package_digest: string;
  returned_package_scope: unknown;
  returned_package_manifest: unknown;
  returned_package_status: string;
}

export interface AiDecision {
  id: string;
  imported_ai_result_id: string;
  decision: string;
  rationale: string;
  lesson_revision_id: string | null;
  decided_at: string;
}

export interface PromptRecord {
  id: string;
  purpose: string;
  status: string;
  current_revision: number;
  created_at: string;
  updated_at: string;
}

export interface Backup {
  id: string;
  portable_package_id: string;
  status: string;
  database_driver: string;
  content_digest: string;
  created_at: string;
}

export interface Restore {
  id: string;
  backup_manifest_id: string;
  target_database: string;
  status: string;
  verification: unknown;
  started_at: string;
  completed_at: string | null;
}

export interface AuditRecord {
  id: string;
  sequence_no: number;
  actor_identifier: string | null;
  action: string;
  target_type: string;
  target_identifier: string | null;
  correlation_id: string;
  outcome: string;
  safe_metadata: unknown;
  occurred_at: string;
  previous_hash: string | null;
  record_hash: string;
}

export interface OperationalPolicy {
  execution?: string;
  automatic_provider_enabled?: boolean;
  automatic_publish?: boolean;
  polling?: boolean;
  embeddings?: boolean;
  append_only?: boolean;
  destructive_http_actions?: boolean;
  cancellation?: string;
  retry_route_available?: boolean;
  knowledge_decisions?: boolean;
}

export interface WorkspaceState {
  foundation?: { checks: CheckMap; healthy: boolean; failed_checks: string[] };
  processing?: { counts: Counts; runs?: ProcessingRun[] };
  outbox?: { counts: Counts; messages?: OutboxMessage[] };
  packages?: { counts?: Counts; records?: PackageRecord[] } | PackageRecord[];
  release_gate?: ReleaseGate;
  source_imports?: { counts: Counts; records: SourceImport[] };
  scope?: {
    technical_validation_only: boolean;
    knowledge_quality_decisions: boolean;
    canonical_knowledge_decisions: boolean;
  };
  policy?: OperationalPolicy;
  prompts?: PromptRecord[];
  prompt_revisions?: PromptRevision[];
  results?: AiResult[];
  decisions?: AiDecision[];
  backups?: Backup[];
  restores?: Restore[];
  safety?: { web_restore_mode: string; activation_route_available: boolean };
  chain?: { valid: boolean; count: number };
  records?: AuditRecord[];
  readiness?: ReleaseGate;
  authorization?: {
    deployment_authorized: boolean;
    deployment_workflow_available: boolean;
    scope: string;
  };
  profile?: string;
  auth_bypass?: boolean;
  force_https?: boolean;
  blob_disk?: string;
  queue_connection?: string;
  release_loopback_only?: boolean;
  ai_network_provider_enabled?: boolean;
  limits?: Record<string, number>;
  configuration_policy?: {
    mode: string;
    runtime_mutation_available: boolean;
    secrets_exposed: boolean;
  };
}

export interface DeepSection {
  label: string;
  value: unknown;
}

export interface DeepWorkspace {
  title: string;
  sections: DeepSection[];
}
