import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Learn from '../pages/KnowledgeLearning/Learn.vue';
import Library from '../pages/KnowledgeLearning/Library.vue';
import ResearchQuality from '../pages/KnowledgeLearning/ResearchQuality.vue';
import Visualize from '../pages/KnowledgeLearning/Visualize.vue';

// Minimal mock component for Inertia Link and Head
const stubComponents = {
  Link: { template: '<a><slot></slot></a>' },
  Head: { template: '<div><slot></slot></div>' },
  KnowledgeTabs: { template: '<nav data-testid="gateways"><slot></slot></nav>' },
};

describe('Knowledge & Learning Phase 1 Layouts & Governance (W02-C01 through W02-C06)', () => {
  it('Learn.vue enforces exact semantic layout: Left Journey, Center Content (Truthful No Lesson), Right Context', () => {
    const wrapper = mount(Learn, {
      props: {
        catalog: [],
        active: { id: 'u1', title_ar: 'الوحدة 1', title_en: 'Unit 1', revision: null },
        journey: {
          items: [
            {
              id: 'j1',
              practice_id: 'p1',
              revision: 1,
              capability_id: 'c1',
              attempt_count: 5,
              successful_attempt_count: 2,
              latest_outcome: 'correct',
              latest_activity_at: '2023-01-01',
              activity_state: 'COMPLETED',
              definition: { lab_reference: { id: 'lab-1' } },
            },
          ],
          labs: [],
          assessments: { state: 'NO_ASSESSMENT' },
          activity: {
            attempt_count: 0,
            completed_practice_count: 0,
            latest_activity_at: null,
            semantic_scope: '',
          },
        },
        semantic_boundary: { progress: '', mastery: '' },
      },
      global: { stubs: stubComponents },
    });

    const html = wrapper.html();

    // LEFT Learning Journey
    expect(html).toContain('رحلة التعلّم');
    expect(html).toContain('p1');

    // CENTER Lesson surface with truthful no-lesson state and clear Arabic strings (W02-C06)
    expect(html).toContain('سطح الدرس والمحتوى التعليمي');
    expect(html).toContain('الدرس غير متوفر');
    expect(html).toContain('لا يتوفر درس مسجل لهذه الوحدة');
    expect(html).toContain('NO_ASSESSMENT');

    // No fake SQL Injection or fabricated 28% completion data
    expect(html).not.toContain('28%');
    expect(html).not.toContain('3/7');

    // RIGHT Context & Lab Readiness
    expect(html).toContain('سياق النشاط المحدد');
    expect(html).toContain('مرجع المختبر (Lab Reference)');
    expect(html).toContain('lab-1');

    // KU != Lesson; Completion != Mastery
    expect(html).toContain('حدود المعنى');

    // Center Gateways
    expect(wrapper.find('[data-testid="gateways"]').exists()).toBe(true);
  });

  it('Visualize.vue enforces view modes, overlay panel, physical orientation, and temporary trace', () => {
    const wrapper = mount(Visualize, {
      props: {
        catalog: [],
        active: { id: 'u1', title_ar: 'Title', title_en: 'Title' },
        map: {
          state: 'saved',
          id: 'm1',
          scope: { kind: 'knowledge_unit', id: 's1' },
          visual_positions: {},
          saved: true,
        },
        view: { implemented: ['Graph', 'Tree'], not_implemented: [] },
        overlay: { active: null, available: [], layers: {} },
        graph: {
          nodes: [],
          edges: [
            {
              id: 'e1',
              from: 'a',
              to: 'b',
              type: 'rel',
              revision: 1,
              lifecycle: { state: 'active' },
            },
          ],
          source: 'db',
        },
      },
      global: { stubs: stubComponents },
    });

    const html = wrapper.html();

    // View modes with responsive tabs
    expect(html).toContain('Graph');
    expect(html).toContain('Tree');

    // Center Gateways
    expect(wrapper.find('[data-testid="gateways"]').exists()).toBe(true);

    // BOTTOM Trace
    expect(html).toContain('أثر العلاقات القانونية — مساحة مؤقتة');
    expect(html).toContain('a → rel → b');
  });

  it('ResearchQuality.vue explicitly differentiates Review from Evidence', () => {
    const wrapper = mount(ResearchQuality, {
      props: {
        catalog: [],
        active: null,
        quality: {
          sources: [],
          active_source: null,
          canonical_claim_ids: [],
          review_semantics: 'test',
        },
        semantic_boundary: { review: '', evidence_review: '', mastery_judgment: '' },
      },
      global: { stubs: stubComponents },
    });

    const html = wrapper.html();

    expect(html).toContain('الادعاءات');
    expect(html).toContain('المقارنة');
    expect(html).toContain('التعارضات');

    expect(html).toContain('Research &amp; Quality Review != Evidence Review');
    expect(html).toContain('النظام لا يقرر حقيقة المعرفة');

    // Center Gateways
    expect(wrapper.find('[data-testid="gateways"]').exists()).toBe(true);
  });

  it('Library.vue provides history compare in BOTTOM, context in RIGHT, and removes hardcoded SQL Injection', async () => {
    const wrapper = mount(Library, {
      props: {
        catalog: [],
        structure: {
          domains: [],
          unresolved_capabilities: [],
          unplaced: [],
        },
        context: {
          placements: [],
          sources: [],
          unresolved_citation_count: 0,
        },
        active: {
          id: 'u1',
          title_ar: 'Title',
          title_en: 'Title',
          revision: {
            id: 'rev1',
            revision: 1,
            state: 'published',
            lock_version: 1,
            blocks: [],
            citations: [],
            authority_baseline_id: null,
            content_digest: 'abc',
            derived_from_revision_id: null,
            published_at: '2023-01-01',
            updated_at: '2023-01-01',
            editable: false,
          },
          revisions: [
            {
              id: 'rev1',
              revision: 1,
              state: 'published',
              lock_version: 1,
              derived_from_revision_id: null,
              published_at: '2023-01-01',
              updated_at: '2023-01-01',
            },
          ],
        },
      },
      global: { stubs: stubComponents },
    });

    const html = wrapper.html();

    // W02-C01: Verified absence of fixed SQL Injection mock content
    expect(html).not.toContain('CWE-89');
    expect(html).not.toContain("SELECT * FROM users WHERE username = '' OR '1'='1'");
    expect(html).not.toContain('18 مايو 2025');

    // Right aside context
    expect(html).toContain('السياق');

    // Bottom Trace / Compare (shelf header)
    expect(html).toContain('المساحة السفلية للسياق والتشخيص');
    expect(html).toContain('مقارنة المراجعات');
    expect(html).toContain('تشخيص التزامن');

    // Expand bottom shelf and verify diagnostics / compare content
    const toggleButton = wrapper.find('button[aria-label="طي أو توسيع المساحة السفلية"]');
    expect(toggleButton.attributes('aria-expanded')).toBe('false');
    await toggleButton.trigger('click');
    expect(toggleButton.attributes('aria-expanded')).toBe('true');

    const expandedHtml = wrapper.html();
    expect(expandedHtml).toContain('مقارنة مراجعتين دون تعديل السجل المنشور');

    // Center Gateways
    expect(wrapper.find('[data-testid="gateways"]').exists()).toBe(true);
  });
});
