import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Learn from '../pages/KnowledgeLearning/Learn.vue';
import Library from '../pages/KnowledgeLearning/Library.vue';
import ResearchQuality from '../pages/KnowledgeLearning/ResearchQuality.vue';
import Visualize from '../pages/KnowledgeLearning/Visualize.vue';
import { lessonContentContractFixture } from './fixtures/lessonContentContract';

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
        active: {
          id: 'u1',
          canonical_ref: { kind: 'knowledge_unit', id: 'u1' },
          title_ar: 'الوحدة 1',
          title_en: 'Unit 1',
        },
        lesson: {
          availability: 'UNAVAILABLE_NO_PUBLISHED_REVISION',
          selection_policy: 'latest_published_revision_only',
          revision: null,
          unavailable_reason: 'No published lesson revision.',
        },
        selection: { requested_id: 'u1', resolved_id: 'u1', state: 'REQUESTED_CANONICAL_UNIT' },
        content_contract: lessonContentContractFixture,
        journey: {
          state: 'PRACTICE_ACTIVITY_AVAILABLE',
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
              activity_completed: true,
              completion_semantics: 'practice_activity_only_not_mastery',
              definition: { lab_reference: { id: 'lab-1' } },
            },
          ],
          labs: [
            {
              id: 'lab-1',
              preview_state: 'REFERENCE_ONLY_FROM_LEARNING_DEFINITION',
              canonical_owner: 'simulation_enterprise',
              prepare_run_handoff: {
                state: 'PARENT_INTEGRATION_REQUIRED',
                executable: false,
                href: null,
              },
            },
          ],
          assessments: {
            state: 'NO_ASSESSMENT',
            integration_state: 'AUTHORITATIVE_ASSESSMENT_CONTRACT_REQUIRED',
          },
          next: {
            state: 'PRACTICE_ACTIVITY_COMPLETE',
            practice_id: null,
            completion_is_mastery: false,
          },
          activity: {
            practice_count: 1,
            attempt_count: 0,
            completed_practice_count: 0,
            started_practice_count: 0,
            completion_is_mastery: false,
            latest_activity_at: null,
            semantic_scope: '',
          },
        },
        context: {
          placements: [],
          sources: [],
          prerequisites: {
            state: 'AUTHORITATIVE_PREREQUISITE_CONTRACT_UNAVAILABLE',
            items: [],
            availability_may_be_inferred: false,
          },
          navigation: {},
          resume: {
            storage: 'browser_local',
            server_persisted: false,
            semantic_scope: 'reading_position_only_not_completion_or_mastery',
          },
        },
        semantic_boundary: { progress: '', completion: '', mastery: '' },
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
    expect(html).toContain('لا يتوفر درس منشور لهذه الوحدة');
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
        selection: { requested_id: null, resolved_id: null, state: 'EMPTY_CANONICAL_LIBRARY' },
        quality: {
          sources: [],
          active_source: null,
          source_selection: {
            requested_id: null,
            resolved_id: null,
            state: 'NO_SOURCES_AVAILABLE',
          },
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
        selection: { requested_id: 'u1', resolved_id: 'u1', state: 'REQUESTED_CANONICAL_UNIT' },
        content_contract: lessonContentContractFixture,
        capability_manifest: {
          canonical_store: {},
          hierarchy: { available: [], requires_parent_context: [] },
          canonical_object_families_requiring_schema_or_parent_integration: [],
          projection_policy: 'reference_canonical_objects_without_silent_copy',
        },
        context: {
          placements: [],
          sources: [],
          unresolved_citation_count: 0,
          hierarchy_state: 'NO_CURRICULUM_PLACEMENT',
          navigation: {},
        },
        active: {
          id: 'u1',
          canonical_ref: { kind: 'knowledge_unit', id: 'u1' },
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
          revision_selection: {
            requested_id: null,
            selected_id: 'rev1',
            state: 'LATEST_REVISION',
            policy: 'explicit_revision_or_latest_revision',
          },
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
