import { mount } from '@vue/test-utils';

import Workspace from '../pages/ProgressEvidence/Workspace.vue';

const candidate = {
  id: 'candidate-1',
  capability_id: 'CAP-WEB-01',
  proposed_title: 'تحليل تدفق مصادقة',
  proposed_summary: 'ملخص مرشّح',
  state: 'SUBMITTED_FOR_INTAKE',
  source_type: 'SIMULATION',
  source_id: 'RUN-0042',
  source_revision: 'result-r3',
  source_digest: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
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
      source_digest: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
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
};

const portfolio = {
  id: 'portfolio-1',
  name: 'عرض التحقيقات المختارة',
  view_scope: 'CAP-WEB',
  grouping: 'CAPABILITY',
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
        source_digest: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
        selected_material_refs: ['artifact:http-transaction'],
        capability_id: 'CAP-WEB-01',
      },
    ],
    mastery_policies: [
      {
        id: 'POLICY-MASTERY-R4',
        policy_key: 'CAP-WEB-MASTERY',
        revision: 4,
        qualifying_review_decisions: ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'],
      },
    ],
  };
}

describe('Progress & Evidence governed workspace', () => {
  it('keeps exactly four primary areas and does not project Evidence state onto a Candidate', async () => {
    const wrapper = mount(Workspace, { props: propsFor('evidence') });

    expect(wrapper.findAll('.area-link')).toHaveLength(4);
    expect(wrapper.get('.left-rail').text()).toContain('الأدلة');
    expect(wrapper.get('.left-rail').text()).toContain('المراجعات');
    expect(wrapper.get('.left-rail').text()).toContain('الإتقان');
    expect(wrapper.get('.left-rail').text()).toContain('الملف المهني');

    const candidateDetail = wrapper.get('[data-testid="candidate-detail"]');
    expect(candidateDetail.text()).toContain('SUBMITTED_FOR_INTAKE');
    expect(candidateDetail.text()).not.toContain('ACTIVE');
    expect(candidateDetail.text()).not.toContain('UNREVIEWED');
    expect(candidateDetail.text()).not.toContain('NONE');

    await wrapper.get('.canonical-group .record-row').trigger('click');
    const evidenceDetail = wrapper.get('[data-testid="evidence-detail"]');
    expect(evidenceDetail.text()).toContain('Evidence Lifecycle');
    expect(evidenceDetail.text()).toContain('ACTIVE');
    expect(evidenceDetail.text()).toContain('Review Status');
    expect(evidenceDetail.text()).toContain('UNREVIEWED');
    expect(evidenceDetail.text()).toContain('Effective Review Decision');
    expect(evidenceDetail.text()).toContain('NONE');
  });

  it('keeps formal review findings and decisions in CENTER while RIGHT owns reviewer and criterion context', () => {
    const wrapper = mount(Workspace, { props: propsFor('reviews') });

    const center = wrapper.get('[data-testid="review-workbench"]');
    expect(center.text()).toContain('SATISFIED');
    expect(center.text()).toContain('ACCEPT_WITH_LIMITATIONS');
    expect(center.text()).toContain('القبول محكوم بالنطاق الحالي فقط.');

    const right = wrapper.get('[data-testid="context-panel"]');
    expect(right.text()).toContain('reviewer-owner-1');
    expect(right.text()).toContain('CRIT-AUTH-01');
    expect(right.text()).toContain('CRIT-TRACE-02');
    expect(right.text()).not.toContain('ACCEPT_WITH_LIMITATIONS');
  });

  it('renders Mastery Judgment and Freshness as separate governed dimensions without gamification', () => {
    const wrapper = mount(Workspace, { props: propsFor('mastery') });

    const detail = wrapper.get('[data-testid="mastery-detail"]');
    expect(detail.text()).toContain('الحكم · Judgment');
    expect(detail.text()).toContain('MASTERED');
    expect(detail.text()).toContain('الحداثة · Freshness');
    expect(detail.text()).toContain('REVALIDATION_REQUIRED');
    expect(detail.text()).toContain('Judgment ≠ Freshness');
    expect(detail.text()).not.toContain('%');
    expect(detail.text()).not.toContain('نقطة');
  });

  it('keeps Portfolio as a curated canonical-reference projection rather than a second Evidence store', () => {
    const wrapper = mount(Workspace, { props: propsFor('portfolio') });

    const center = wrapper.get('[data-testid="portfolio-detail"]');
    expect(center.text()).toContain('تحليل المصادقة المحكوم');
    expect(center.text()).toContain('revision-1');
    expect(center.text()).toContain('Canonical Evidence Reference');
    expect(center.text()).not.toContain('ACTIVE');
    expect(center.text()).not.toContain('MASTERED');

    const right = wrapper.get('[data-testid="context-panel"]');
    expect(right.text()).toContain('CAP-WEB');
    expect(right.text()).toContain('CAPABILITY');
    expect(right.text()).not.toContain('revision-1');
  });
});
