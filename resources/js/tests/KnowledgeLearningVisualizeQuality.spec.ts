import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Visualize from '../pages/KnowledgeLearning/Visualize.vue';
import Learn from '../pages/KnowledgeLearning/Learn.vue';
import Library from '../pages/KnowledgeLearning/Library.vue';
import ResearchQuality from '../pages/KnowledgeLearning/ResearchQuality.vue';

// Minimal mock component for Inertia Link and Head
const stubComponents = {
  Link: { template: '<a><slot></slot></a>' },
  Head: { template: '<div><slot></slot></div>' },
  KnowledgeTabs: { template: '<nav data-testid="gateways"><slot></slot></nav>' }
};

describe('Knowledge & Learning Phase 1 Layouts', () => {
  it('Learn.vue enforces exact semantic layout: Left Journey, Center Content (No Lesson), Right Context', () => {
    const wrapper = mount(Learn as any, {
      props: {
        catalog: [],
        active: { id: 'u1', title_ar: 'الوحدة 1', title_en: 'Unit 1', revision: null },
        journey: {
          items: [{
            id: 'j1',
            practice_id: 'p1',
            revision: 1,
            capability_id: 'c1',
            attempt_count: 5,
            successful_attempt_count: 2,
            latest_outcome: 'correct',
            latest_activity_at: '2023-01-01',
            activity_state: 'COMPLETED',
            definition: { lab_reference: { id: 'lab-1' } }
          }],
          labs: [],
          assessments: { state: 'NO_ASSESSMENT' },
          activity: { attempt_count: 0, completed_practice_count: 0, latest_activity_at: null, semantic_scope: '' }
        },
        semantic_boundary: { progress: '', mastery: '' }
      },
      global: { stubs: stubComponents }
    });
    
    const html = wrapper.html();
    
    // LEFT Learning Journey
    expect(html).toContain('رحلة التعلّم');
    expect(html).toContain('p1');
    
    // CENTER Lesson surface with truthful no-lesson state
    expect(html).toContain('سطح الدرس والمحتوى التعليمي');
    expect(html).toContain('لا يوجد درس تعليمي مخصص (No Lesson State)');
    expect(html).toContain('NO_ASSESSMENT');
    
    // RIGHT Context & Lab Readiness
    expect(html).toContain('سياق الخطوة المحددة');
    expect(html).toContain('جاهزية المعمل (Lab Readiness)');
    expect(html).toContain('lab-1');
    
    // KU != Lesson; Completion != Mastery
    expect(html).toContain('إكمال النشاط');
    expect(html).toContain('لا يمثل الإتقان');
    
    // Center Gateways
    expect(wrapper.find('[data-testid="gateways"]').exists()).toBe(true);
  });

  it('Visualize.vue enforces view modes, overlay panel, and temporary trace', () => {
    const wrapper = mount(Visualize as any, {
      props: {
        catalog: [],
        active: { id: 'u1', title_ar: 'Title', title_en: 'Title' },
        map: { state: 'saved', id: 'm1', scope: { id: 's1' }, visual_positions: [], saved: true },
        view: { implemented: ['Graph'], not_implemented: [] },
        overlay: { active: null, available: [], layers: {} },
        graph: { nodes: [], edges: [{ id: 'e1', from: 'a', to: 'b', type: 'rel', revision: 1 }], source: 'db' }
      },
      global: { stubs: stubComponents }
    });
    
    const html = wrapper.html();
    
    // View modes
    expect(html).toContain('Graph');
    
    // Center Gateways
    expect(wrapper.find('[data-testid="gateways"]').exists()).toBe(true);
    
    // BOTTOM Trace
    expect(html).toContain('أثر العلاقات canonical — مساحة مؤقتة');
    expect(html).toContain('a → rel → b');
  });

  it('ResearchQuality.vue explicitly differentiates Review from Evidence', () => {
    const wrapper = mount(ResearchQuality as any, {
      props: {
        catalog: [],
        active: null,
        quality: { sources: [], active_source: null, canonical_claim_ids: [], review_semantics: 'test' },
        semantic_boundary: { review: '', evidence_review: '', mastery_judgment: '' }
      },
      global: { stubs: stubComponents }
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

  it('Library.vue provides history compare in BOTTOM and context in RIGHT', () => {
    const wrapper = mount(Library as any, {
      props: {
        catalog: [],
        context: {
          hierarchy: [],
          placements: [],
          sources: [],
          unresolved_citation_count: 0
        },
        revisions: [{ id: 'rev1', revision: 1, state: 'published', updated_at: '2023-01-01', published_at: '2023-01-01', content_digest: 'abc', editable: false }],
        active: { id: 'u1', title_ar: 'Title', title_en: 'Title', revision: null },
        compareResult: null,
        compareRevisionIdProp: null
      },
      global: { stubs: stubComponents }
    });

    const html = wrapper.html();
    
    // Right aside context
    expect(html).toContain('السياق');
    expect(html).toContain('موضع المنهج');
    
    // Bottom Trace / Compare (shelf)
    expect(html).toContain('مقارنة مراجعتين دون تعديل السجل المنشور');
    expect(html).toContain('تشخيص التزامن وحالة الاسترداد المحلي');
    
    // Center Gateways
    expect(wrapper.find('[data-testid="gateways"]').exists()).toBe(true);
  });
});

