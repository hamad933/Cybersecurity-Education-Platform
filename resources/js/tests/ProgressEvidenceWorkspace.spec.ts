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
  groups: [
    {
      grouping: 'CAPABILITY',
      key: 'CAP-WEB-01',
      items: [
        {
          id: 'portfolio-item-1',
          evidence_id: 'evidence-1',
          current_revision_id: 'revision-1',
          title: 'تحليل المصادقة المحكوم',
        },
      ],
    },
  ],
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

  it('keeps Candidate Evidence distinct from canonical Evidence and shows only supplied source references', async () => {
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
    expect(right.text()).not.toContain('Trusted');
    expect(right.text()).not.toContain('Verified');

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
    expect(right.text()).toContain('Review Binding');
    expect(right.text()).toContain('reviewer-owner-1');
    expect(right.text()).not.toContain('ACCEPT_WITH_LIMITATIONS');
    expect(right.text()).not.toContain('Cybersecurity Investigation Standard');
    expect(right.text()).not.toContain('Authorized');
  });

  it('renders missing candidate provenance as unavailable without inventing verification claims', () => {
    const props = {
      ...propsFor('evidence'),
      candidates: [
        {
          ...candidate,
          handoff_receipt_id: 'handoff-missing',
          source_type: '',
          source_id: '',
          source_revision: '',
          source_digest: '',
          criterion_scope: [],
        },
      ],
      handoff_receipts: [],
    };

    const wrapper = mount(Workspace, { props });
    const right = wrapper.get('[data-testid="context-panel"]');

    expect(right.text()).toContain('handoff-missing');
    expect(right.text()).toContain('غير متوفر');
    expect(right.text()).not.toContain('Trusted');
    expect(right.text()).not.toContain('Verified');
    expect(right.text()).not.toContain('integrity');
    expect(right.text()).not.toContain('duplicate');
  });

  it('maps unresolved and negative governed enums to non-success visual tones', () => {
    const masteryCases = [
      ['NOT_EVALUATED', 'tone-neutral'],
      ['INSUFFICIENT_EVIDENCE', 'tone-warning'],
      ['INCONCLUSIVE', 'tone-warning'],
      ['NOT_MASTERED', 'tone-danger'],
    ] as const;

    for (const [judgment, tone] of masteryCases) {
      const props = { ...propsFor('mastery'), mastery: [{ ...mastery, judgment }] };
      const wrapper = mount(Workspace, { props });
      const badge = wrapper.get('.mastery-judgment-badge');

      expect(badge.classes()).toContain(tone);
      expect(badge.classes()).not.toContain('tone-positive');
    }

    for (const [outcome, tone] of [
      ['MORE_EVIDENCE_REQUIRED', 'tone-warning'],
      ['REJECT', 'tone-danger'],
    ] as const) {
      const props = {
        ...propsFor('reviews'),
        reviews: [{ ...review, decision: { ...review.decision, decision: outcome } }],
      };
      const wrapper = mount(Workspace, { props });
      const badge = wrapper.get('.decision-pill');

      expect(badge.classes()).toContain(tone);
      expect(badge.classes()).not.toContain('tone-positive');
    }

    const findingProps = {
      ...propsFor('reviews'),
      reviews: [
        {
          ...review,
          findings: [{ ...review.findings[0], finding: 'NOT_SATISFIED' }],
        },
      ],
    };
    const findingWrapper = mount(Workspace, { props: findingProps });
    expect(findingWrapper.get('.finding-state-pill').classes()).toContain('tone-danger');
  });

  it('disables review mutation actions for closed and already-decided reviews', async () => {
    const governedStops = [
      { ...review, status: 'CLOSED' },
      { ...review, status: 'READY_FOR_DECISION' },
    ];

    for (const stoppedReview of governedStops) {
      const wrapper = mount(Workspace, {
        props: { ...propsFor('reviews'), reviews: [stoppedReview] },
      });
      const findingButton = wrapper
        .findAll('.top-actions button')
        .find((button) => button.text().includes('Record Review Finding'))!;
      const decisionButton = wrapper
        .findAll('.top-actions button')
        .find((button) => button.text().includes('Issue Decision'))!;

      expect((findingButton.element as HTMLButtonElement).disabled).toBe(true);
      expect((decisionButton.element as HTMLButtonElement).disabled).toBe(true);
      await decisionButton.trigger('click');
      expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);
    }
  });

  it('selects reviews by governed lifecycle without REV-0084 identifier logic', () => {
    const props = {
      ...propsFor('reviews'),
      reviews: [
        { ...review, id: 'REV-0084-legacy', status: 'CLOSED' },
        { ...review, id: 'review-current', status: 'IN_REVIEW', decision: null },
      ],
    };

    const wrapper = mount(Workspace, { props });
    const center = wrapper.get('[data-testid="review-workbench"]');

    expect(center.text()).toContain('review-current');
    expect(center.text()).not.toContain('REV-0084-legacy');
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
    expect(center.text()).toContain('إسقاط محكوم');
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

  it('preserves multiple authoritative backend groups with identical null keys as distinct without cross-pollination or inferred status', () => {
    const duplicateNullKeyPortfolio = {
      ...portfolio,
      id: 'portfolio-duplicate-null',
      grouping: 'REVIEW_DECISION',
      groups: [
        {
          grouping: 'REVIEW_DECISION',
          key: null,
          items: [
            {
              id: 'portfolio-item-1',
              evidence_id: 'evidence-1',
              current_revision_id: 'revision-1',
              title: 'تحليل المصادقة الأول',
            },
          ],
        },
        {
          grouping: 'REVIEW_DECISION',
          key: null,
          items: [
            {
              id: 'portfolio-item-2',
              evidence_id: 'evidence-2',
              current_revision_id: 'revision-1',
              title: 'تحليل المصادقة الثاني',
            },
            {
              id: 'portfolio-item-3',
              evidence_id: 'evidence-3',
              current_revision_id: 'revision-2',
              title: 'تحليل المصادقة الثالث',
            },
          ],
        },
      ],
    };

    const wrapper = mount(Workspace, {
      props: { ...propsFor('portfolio'), portfolios: [duplicateNullKeyPortfolio] },
    });

    const center = wrapper.get('[data-testid="portfolio-detail"]');
    expect(center.text()).toContain('2 Groups');

    // Both groups render the unavailable translation
    const groupCards = wrapper.findAll('.portfolio-group-card');
    expect(groupCards).toHaveLength(2);
    expect(groupCards[0].get('.group-title').text()).toBe('غير متوفر');
    expect(groupCards[1].get('.group-title').text()).toBe('غير متوفر');

    // Assert unique DOM presentation attributes using the computed grp.id
    const id0 = groupCards[0].attributes('data-group-presentation-id');
    const id1 = groupCards[1].attributes('data-group-presentation-id');
    expect(id0).toBeDefined();
    expect(id1).toBeDefined();
    expect(id0).not.toEqual(id1);

    // Explicit unavailable rendering, no inferred status (avoid depending on generic text like CAP-WEB-01)
    expect(center.text()).not.toContain('MASTERED');

    // Check exact membership without cross-pollination
    expect(groupCards[0].text()).toContain('تحليل المصادقة الأول');
    expect(groupCards[0].text()).not.toContain('تحليل المصادقة الثاني');
    expect(groupCards[0].text()).not.toContain('تحليل المصادقة الثالث');

    expect(groupCards[1].text()).not.toContain('تحليل المصادقة الأول');
    expect(groupCards[1].text()).toContain('تحليل المصادقة الثاني');
    expect(groupCards[1].text()).toContain('تحليل المصادقة الثالث');
  });

  it('renders blank string keys as explicitly unavailable without inventing semantics', () => {
    const blankKeyPortfolio = {
      ...portfolio,
      id: 'portfolio-blank',
      groups: [
        {
          grouping: 'EVIDENCE_TYPE',
          key: '   ',
          items: [],
        },
      ],
    };

    const wrapper = mount(Workspace, {
      props: { ...propsFor('portfolio'), portfolios: [blankKeyPortfolio] },
    });

    const groupCards = wrapper.findAll('.portfolio-group-card');

    expect(groupCards).toHaveLength(1);
    expect(groupCards[0].get('.group-title').text()).toBe('غير متوفر');
  });

  it('uses authoritative backend groups even when differing from local lookup, avoids raw constants, and empty groups remains empty', () => {
    const emptyPortfolio = {
      ...portfolio,
      id: 'portfolio-2',
      groups: [],
      items: [],
    };
    const differingPortfolio = {
      ...portfolio,
      id: 'portfolio-3',
      grouping: 'REVIEW_DECISION',
      groups: [
        {
          grouping: 'REVIEW_DECISION',
          key: 'REJECT',
          items: [
            {
              id: 'portfolio-item-1',
              evidence_id: 'evidence-1',
              current_revision_id: 'revision-1',
              title: 'تحليل المصادقة المحكوم',
            },
          ],
        },
      ],
    };

    const wrapperEmpty = mount(Workspace, {
      props: { ...propsFor('portfolio'), portfolios: [emptyPortfolio] },
    });
    const centerEmpty = wrapperEmpty.get('[data-testid="portfolio-detail"]');
    expect(centerEmpty.text()).toContain('0 Groups');
    expect(centerEmpty.text()).not.toContain('group-1'); // Not generating fallback
    expect(centerEmpty.text()).not.toContain('PORTFOLIO_CAPABILITY_PROJECTION');
    expect(centerEmpty.text()).not.toContain('PORTFOLIO_PROJECTION');

    const wrapperDiffering = mount(Workspace, {
      props: { ...propsFor('portfolio'), portfolios: [differingPortfolio] },
    });
    const centerDiffering = wrapperDiffering.get('[data-testid="portfolio-detail"]');
    expect(centerDiffering.text()).toContain('1 Groups');
    expect(centerDiffering.text()).toContain('REJECT'); // Rendering the key as is
    expect(centerDiffering.text()).not.toContain('CAP-WEB-01'); // It uses key from group, not ev mapping
    expect(centerDiffering.text()).toContain('إسقاط محكوم');
    expect(centerDiffering.text()).not.toContain('PORTFOLIO_CAPABILITY_PROJECTION');
    expect(centerDiffering.text()).not.toContain('PORTFOLIO_PROJECTION');
  });

  it('keeps mobile surface navigation labels intact and exposes native keyboard controls', async () => {
    const props = propsFor('portfolio');
    props.portfolios = [
      {
        ...portfolio,
        groups: [
          {
            grouping: 'CAPABILITY',
            key: 'CAP-WEB-01',
            items: [
              {
                id: 'portfolio-item-1',
                evidence_id: 'evidence-1',
                current_revision_id: 'revision-1',
                title: 'تحليل المصادقة المحكوم',
              },
              {
                id: 'portfolio-item-2',
                evidence_id: 'evidence-1',
                current_revision_id: 'revision-1',
                title: 'مرجع ثانٍ',
              },
            ],
          },
        ],
        items: [
          ...portfolio.items,
          {
            ...portfolio.items[0],
            id: 'portfolio-item-2',
            title: 'مرجع ثانٍ',
          },
        ],
      },
    ];
    const wrapper = mount(Workspace, { props });
    const tabs = wrapper.findAll('.surface-tab');

    expect(tabs.map((tab) => tab.attributes('href'))).toEqual([
      '/progress',
      '/progress/reviews',
      '/progress/mastery',
      '/progress/portfolio',
    ]);
    expect(tabs.every((tab) => tab.element.tagName === 'A')).toBe(true);
    expect(tabs.every((tab) => tab.find('.tab-ar').text().length > 1)).toBe(true);

    const selectors = wrapper.findAll('.reference-select-button');
    expect(selectors).toHaveLength(2);
    expect(selectors.every((button) => button.attributes('type') === 'button')).toBe(true);
    expect(selectors[1].attributes('aria-pressed')).toBe('false');
    await selectors[1].trigger('click');
    expect(selectors[1].attributes('aria-pressed')).toBe('true');

    const settings = wrapper.get('.context-action-btn');
    expect(settings.attributes('type')).toBe('button');
    await settings.trigger('click');
    expect(wrapper.get('[data-cep-region="bottom"]').find('form').exists()).toBe(true);
  });

  it('provides the correct supported grouping options in the create form and rail navigation', async () => {
    const wrapper = mount(Workspace, { props: propsFor('portfolio') });

    // Check left rail
    const left = wrapper.get('[data-cep-region="left"]');
    expect(left.text()).toContain('By Capability');
    expect(left.text()).toContain('By Review Decision');
    expect(left.text()).toContain('By Evidence Type');
    expect(left.text()).toContain('By Time');
    expect(left.text()).toContain('By Mastery Judgment');
    expect(left.text()).toContain('By Freshness Status');
    expect(left.text()).not.toContain('By Project');
    expect(left.text()).not.toContain('By Learning Objective');

    // Open bottom form
    const settings = wrapper.get('.context-action-btn');
    await settings.trigger('click');
    const form = wrapper.get('[data-cep-region="bottom"] form');
    expect(form.text()).toContain('REVIEW_DECISION');
    expect(form.text()).toContain('FRESHNESS_STATUS');
    expect(form.text()).not.toContain('PROJECT');
    expect(form.text()).not.toContain('OBJECTIVE');
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
