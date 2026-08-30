export type Claim = {
  id: string;
  claim_id: string;
  segment_ref: string;
  supported_scope: string;
  excluded_semantics: string;
  assessment: string;
  used_by_active_revision: boolean;
};

export type Source = {
  id: string;
  authority_class: string;
  title: string;
  exact_url: string | null;
  relative_path: string | null;
  sha256: string;
  review_status: string;
  metadata: Record<string, unknown>;
  claims: Claim[];
};

export type ComparisonRow = {
  source_id: string;
  title: string;
  authority_class: string;
  review_status: string;
  claim_count: number;
  active_revision_claim_count: number;
  anchor_count: number;
  has_integrity_digest: boolean;
};

export type ConflictVariant = {
  source_id: string;
  source_title: string;
  segment_ref: string;
  supported_scope: string;
  excluded_semantics: string;
  assessment: string;
};

export type Conflict = {
  claim_id: string;
  status: string;
  variants: ConflictVariant[];
  preferred_source_id: null;
  system_truth_decision: null;
};

export type ProvenanceRow = {
  source_id: string;
  title: string;
  locator: string | null;
  sha256: string;
  anchors: Array<{ claim_id: string; segment_ref: string }>;
  traceability_state: string;
};

export type ResearchAnalysis = {
  comparison: { rows: ComparisonRow[]; meaning: string };
  provenance: { sources: ProvenanceRow[]; meaning: string };
  conflicts: Conflict[];
  reconciliation: {
    pending_conflict_count: number;
    human_judgment_required: boolean;
    system_truth_decision: null;
    allowed_next_tools: string[];
    persistence_boundary: {
      state: 'RQ_PERSISTENT_RECONCILIATION_OWNER_REQUIRED';
      durable_write_authorized: false;
      persistent_owner: null;
      decision_record: null;
      current_experience: 'read_only_analysis_with_ephemeral_human_note';
    };
  };
  revision_reasoning: {
    canonical_claim_ids: string[];
    resolved_claim_ids: string[];
    unresolved_claim_ids: string[];
    claim_sources: Record<string, string[]>;
    meaning: string;
  };
  review: {
    kind: string;
    decision_authority: string;
    system_may_decide_truth: boolean;
    evidence_review: boolean;
  };
};
