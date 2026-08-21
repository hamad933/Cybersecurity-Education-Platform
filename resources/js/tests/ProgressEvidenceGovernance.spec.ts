import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';

describe('Progress & Evidence governed workspace', () => {
  const source = readFileSync(resolve('resources/js/pages/ProgressEvidence/Workspace.vue'), 'utf8');

  it('accepts only a verified Handoff receipt at browser Candidate intake', () => {
    expect(source).toContain('v-model="intake.handoff_receipt_id"');
    expect(source).not.toContain('v-model="intake.source_type"');
    expect(source).not.toContain('v-model="intake.source_id"');
    expect(source).not.toContain('v-model="intake.source_revision"');
    expect(source).not.toContain('v-model="intake.source_digest"');
    expect(source).not.toContain('v-model="intake.selected_material_refs');
  });

  it('renders governed provenance, field errors, selection semantics, and explicit empty states', () => {
    expect(source).toContain('selectedEvidence.source_digest');
    expect(source).toContain('selectedReviewRevision.source_digest');
    expect(source).toContain('role="alert"');
    expect(source).toContain('aria-live="assertive"');
    expect(source).toContain(':aria-current=');
    expect(source).toContain(':aria-pressed=');
    expect(source.match(/class="empty-state"/g)?.length ?? 0).toBeGreaterThanOrEqual(7);
  });

  it('exposes legal lifecycle actions without a fifth Progress area', () => {
    expect(source).toContain("state: 'DRAFT'");
    expect(source).toContain("state: 'RETURNED_FOR_CONTEXT'");
    expect(source).toContain("state: 'DECLINED'");
    expect(source).toContain("state: 'WITHDRAWN'");
    expect(source).toContain("transitionEvidenceLifecycle('WITHDRAWN')");
    expect(source).toContain("transitionEvidenceLifecycle('SUPERSEDED')");
    expect(source).toContain("type Surface = 'evidence' | 'reviews' | 'mastery' | 'portfolio'");
  });

  it('offers only governed policy revisions and remaining pinned review criteria', () => {
    expect(source).toContain('v-for="policy in mastery_policies"');
    expect(source).toContain('v-for="criterion in remainingReviewCriteria"');
    expect(source).not.toContain('Policy Revision<input');
  });
});
