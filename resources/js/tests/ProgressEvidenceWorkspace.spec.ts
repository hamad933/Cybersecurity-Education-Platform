import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';

import Workspace from '../pages/ProgressEvidence/Workspace.vue';

const sourceDigest = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

const candidate = {
  id: 'candidate-1',
  handoff_receipt_id: 'handoff-1',
  capability_id: 'CAP-WEB-01',
  proposed_title: 'تحليل تدفق مصادقة',
  proposed_summary: 'ملخص مرشّح',
  state: 'SUBMITTED_FOR_INTAKE',
  source_type: 'SIMULATION',
  source_id: 'RUN-0042',
  source_revision: 'result-r3',
  source_digest: sourceDigest,
  evidence_claim: 'يثبت القدرة على تحليل تدفق مصادقة وتفسير السبب الجذري.',
  governed_purpose: 'FORMAL_CAPABILITY_EVIDENCE',
  selected_material_refs: ['artifact:http-transaction', 'artifact:timeline'],
  criterion_scope: ['CRIT-AUTH-01'],
};

const evidence = {
  id: 'evidence-1',
  capability_id: 'CAP-WEB-01',
  evidence_claim: 'تحليل تدفق مصادقة موثّق بمراجع محكومة.',
  governed_purpose: 'FORMAL_CAPABILITY_EVIDENCE',
  lifecycle_state: 'ACTIVE',
  review_status: 'UNREVIEWED',
  effective_review_decision: 'NONE',
  effective_review_decision_id: null,
  current_revision_number: 1,
  current_revision_id: 'revision-1',
  title: 'تحليل المصادقة المحكوم',
  summary: 'Evidence مختومة ومؤهلة للمراجعة.',
  source_type: 'SIMULATION',
  source_id: 'RUN-0042',
  source_revision: 'result-r3',
  source_digest: sourceDigest,
  revisions: [
    {
      id: 'revision-1',
      evidence_id: 'evidence-1',
      revision: 1,
      previous_revision_id: null,
      revision_reason: 'Admission from Candidate Evidence',
      source_type: 'SIMULATION',
      source_id: 'RUN-0042',
      source_revision: 'result-r3',
      source_digest: sourceDigest,
      selected_material_refs: ['artifact:http-transaction', 'artifact:timeline'],
      criterion_scope: ['CRIT-AUTH-01'],
    },
  ],
};

const review = {
  id: 'review-1',
  evidence_id: 'evidence-1',
  evidence_revision_id: 'revision-1',
  reviewer_id: 'reviewer-owner-1',
  review_scope_key: 'CAP-WEB-01',
  criterion_refs: ['CRIT-AUTH-01', 'CRIT-TRACE-02'],
  status: 'READY_FOR_DECISION',
  findings: [
    {
      id: 'finding-1',
      criterion_key: 'CRIT-AUTH-01',
      finding: 'SATISFIED',
      statement: 'المادة المثبتة تدعم المعيار مباشرة.',
    },
  ],
  decision: {
    id: 'decision-1',
    decision: 'ACCEPT_WITH_LIMITATIONS',
    rationale: 'القبول محكوم بالنطاق الحالي فقط.',
  },
};

const mastery = {
  id: 'mastery-state-2',
  target_id: 'CAP-WEB-01',
  judgment: 'MASTERED',
  freshness_status: 'REVALIDATION_REQUIRED',
  policy_revision_id: 'POLICY-MASTERY-R4',
  previous_state_id: 'mastery-state-1',
  review_decision_ids: ['decision-1'],
  supporting_evidence_revision_ids: ['revision-1'],
  contradicting_evidence_revision_ids: [],
  rationale: 'حكم بشري محكوم بمراجع القرار والدليل المحددة.',
};

const portfolio = {
  id: 'portfolio-1',
  name: 'عرض التحقيقات المختارة',
  view_scope: 'CAP-WEB',
  grouping: 'CAPABILITY',
  filters: { review_decisions: ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'] },
  annotations: { purpose: 'curated-projection' },
  items: [
    {
      id: 'portfolio-item-1',
      evidence_id: 'evidence-1',
      current_revision_id: 'revision-1',
      title: 'تحليل المصادقة المحكوم',
    },
  ],
};

function propsFor(surface: 'evidence' | 'reviews' | 'mastery' | 'portfolio') {
  return {
    surface,
    summary: {},
    candidates: [candidate],
    evidence: [evidence],
    review_requests: [],
    reviews: [review],
    mastery: [mastery],
    mastery_history: [
      {
        ...mastery,
        id: 'mastery-state-1',
        judgment: 'INCONCLUSIVE',
        freshness_status: 'CURRENT',
        previous_state_id: null,
      },
      mastery,
    ],
    portfolios: [portfolio],
    handoff_receipts: [
      {
        id: 'handoff-1',
        source_type: 'SIMULATION',
        source_id: 'RUN-0042',
        source_revision: 'result-r3',
        source_digest: sourceDigest,
        selected_material_refs: ['artifact:http-transaction'],
        capability_id: 'CAP-WEB-01',
      },
    ],
    mastery_policies: [
      {
        id: 'POLICY-MASTERY-R4',
        policy_key: 'CAP-WEB-MASTERY',
        revision: 4,
        target_type: 'CAPABILITY',
        target_id: 'CAP-WEB-01',
        qualifying_review_decisions: ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'],
      },
    ],
  };
}

describe('Progress & Evidence governed workspace', () => {
  beforeEach(() => {
    localStorage.clear();
    document.documentElement.removeAttribute('data-theme');
  });

  it('keeps Candidate Evidence distinct from canonical Evidence and shows trusted handoff truth', async () => {
    const wrapper = mount(Workspace, { props: propsFor('evidence') });

    expect(wrapper.findAll('.surface-tab')).toHaveLength(4);
    const candidateDetail = wrapper.get('[data-testid="candidate-detail"]');
    expect(candidateDetail.text()).toContain('SUBMITTED_FOR_INTAKE');
    expect(candidateDetail.text()).not.toContain('ACTIVE');
    expect(candidateDetail.text()).not.toContain('UNREVIEWED');
    expect(candidateDetail.text()).not.toContain('NONE');

    const right = wrapper.get('[data-testid="context-panel"]');
    expect(right.text()).toContain('handoff-1');
    expect(right.text()).toContain(sourceDigest);

    await wrapper.get('.canonical-group .record-row').trigger('click');
    const evidenceDetail = wrapper.get('[data-testid="evidence-detail"]');
    expect(evidenceDetail.text()).toContain('Evidence Lifecycle');
    expect(evidenceDetail.text()).toContain('ACTIVE');
    expect(evidenceDetail.text()).toContain('Review Status');
    expect(evidenceDetail.text()).toContain('UNREVIEWED');
    expect(evidenceDetail.text()).toContain('Effective Review Decision');
    expect(evidenceDetail.text()).toContain('NONE');
  });

  it('keeps formal Review Findings and human Review Decisions distinct', () => {
    const wrapper = mount(Workspace, { props: propsFor('reviews') });

    const center = wrapper.get('[data-testid="review-workbench"]');
    expect(center.text()).toContain('Review Findings');
    expect(center.text()).toContain('SATISFIED');
    expect(center.text()).toContain('Review Decision');
    expect(center.text()).toContain('ACCEPT_WITH_LIMITATIONS');
    expect(center.text()).toContain('القبول محكوم بالنطاق الحالي فقط.');

    const right = wrapper.get('[data-testid="context-panel"]');
    expect(right.text()).toContain('سلطة المراجعة');
    expect(right.text()).toContain('reviewer-owner-1');
    expect(right.text()).toContain('CRIT-AUTH-01');
    expect(right.text()).not.toContain('ACCEPT_WITH_LIMITATIONS');
  });

  it('renders Mastery Judgment and Freshness as separate governed dimensions without percentages', () => {
    const wrapper = mount(Workspace, { props: propsFor('mastery') });

    const detail = wrapper.get('[data-testid="mastery-detail"]');
    expect(detail.text()).toContain('الحكم · Judgment');
    expect(detail.text()).toContain('MASTERED');
    expect(detail.text()).toContain('الحداثة · Freshness');
    expect(detail.text()).toContain('REVALIDATION_REQUIRED');
    expect(detail.text()).toContain('Judgment ≠ Freshness');
    expect(detail.text()).toContain('Supporting Evidence Criterion Scope');
    expect(detail.text()).toContain('Governed Review Finding');
    expect(detail.text()).toContain('CRIT-AUTH-01');
    expect(detail.text()).toContain('SATISFIED');
    expect(detail.text()).toContain('Review Decision ID');
    expect(detail.text()).toContain('decision-1');
    expect(detail.text()).toContain('Supporting Evidence Revisions');
    expect(detail.text()).toContain('revision-1');
    expect(detail.text()).not.toContain('Peding / Unavailable');
    expect(detail.text()).not.toContain('%');
    expect(detail.text()).not.toContain('نقطة');
  });

  it('does not infer criterion satisfaction from aggregate Mastery Judgment', () => {
    const props = propsFor('mastery');
    props.reviews = [{ ...review, findings: [] }];

    const wrapper = mount(Workspace, { props });
    const detail = wrapper.get('[data-testid="mastery-detail"]');

    expect(detail.text()).toContain('MASTERED');
    expect(detail.text()).toContain('CRIT-AUTH-01');
    expect(detail.text()).toContain('غير محسوم على مستوى المعيار');
    expect(detail.text()).not.toContain('مستوفى (SATISFIED)');
  });

  it('keeps Portfolio as a curated canonical-reference projection rather than a second Evidence store', () => {
    const wrapper = mount(Workspace, { props: propsFor('portfolio') });

    const center = wrapper.get('[data-testid="portfolio-detail"]');
    expect(center.text()).toContain('تحليل المصادقة المحكوم');
    expect(center.text()).toContain('revision-1');
    expect(center.text()).toContain('Canonical Evidence Reference');
    expect(center.text()).toContain('Reference-only curated projection');
    expect(center.text()).toContain('Evidence ID');
    expect(center.text()).not.toContain(evidence.evidence_claim);
    expect(center.text()).not.toContain(evidence.summary);
    expect(center.text()).not.toContain('MASTERED');

    const right = wrapper.get('[data-testid="context-panel"]');
    expect(right.text()).toContain('CAP-WEB');
    expect(right.text()).toContain('CAPABILITY');
    expect(right.text()).toContain('review_decisions');
    expect(right.text()).toContain('ACCEPT_WITH_LIMITATIONS');
    expect(right.text()).not.toContain('Accepted Evidence');
    expect(right.text()).not.toContain('revision-1');
  });

  it('assigns TOP, LEFT, CENTER, RIGHT, and BOTTOM to their governed owners', () => {
    const wrapper = mount(Workspace, { props: propsFor('evidence') });

    const top = wrapper.get('[data-cep-region="top"]');
    const left = wrapper.get('[data-cep-region="left"]');
    const center = wrapper.get('[data-cep-region="center"]');
    const right = wrapper.get('[data-cep-region="right"]');

    expect(top.text()).toContain('إدخال');
    expect(top.text()).not.toContain(candidate.proposed_title);
    expect(left.text()).toContain(candidate.proposed_title);
    expect(left.text()).toContain(evidence.title);
    expect(center.text()).toContain(candidate.evidence_claim);
    expect(center.text()).not.toContain('handoff-1');
    expect(right.text()).toContain('handoff-1');
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);
  });

  it('keeps the temporary BOTTOM workspace closed until a workflow action opens it', async () => {
    const wrapper = mount(Workspace, { props: propsFor('evidence') });

    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);
    const intakeButton = wrapper
      .findAll('.top-actions button')
      .find((button) => button.text().includes('إدخال'));
    expect(intakeButton).toBeDefined();

    await intakeButton!.trigger('click');

    const bottom = wrapper.get('[data-cep-region="bottom"]');
    expect(bottom.text()).toContain('إعداد Candidate Evidence');
    expect(bottom.find('[data-testid="temporary-workspace-content"]').exists()).toBe(true);
    expect(bottom.find('form').exists()).toBe(true);
  });
});
