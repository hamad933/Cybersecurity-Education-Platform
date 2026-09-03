import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import Library from '../pages/KnowledgeLearning/Library.vue';
import { nextTick } from 'vue';

vi.mock('@inertiajs/vue3', () => ({
  Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot/></a>' },
  router: { post: vi.fn(), put: vi.fn(), visit: vi.fn(), get: vi.fn() },
  useForm: <T extends Record<string, unknown>>(data: T) => ({ ...data, patch: vi.fn(), post: vi.fn(), put: vi.fn(), processing: false, errors: {}, clearErrors: vi.fn() }),
  usePage: () => ({ props: { flash: {}, errors: {} } }),
  Head: { template: '<slot/>' },
}));

const mockContentContract = {
  version: '1.0',
  block_registry: [
    { type: 'heading', label_ar: 'عنوان', label_en: 'Heading' },
    { type: 'paragraph', label_ar: 'فقرة', label_en: 'Paragraph' },
  ],
  type_constraints: { heading: { schema: 'text', technical_token: false }, paragraph: { schema: 'text', technical_token: false } },
  constraints: { max_depth: 3, allowed_types: ['heading', 'paragraph'] },
  canonical_owner: 'knowledge' as const, identity: { expected_prefix: 'b' }, citation: { exact_source_match: true }, revision_semantics: { strict_linear: true },
};

const defaultProps = {
  catalog: [], structure: { domains: [], unresolved_capabilities: [], unplaced: [] },
  active: {
    id: 'KU-001',
    canonical_ref: { kind: 'knowledge_unit' as const, id: 'KU-001' },
    title_ar: 'وحدة تجريبية', title_en: 'Test Unit',
    revision: {
      id: 'REV-001', revision: 1, state: 'draft', lock_version: 1, editable: true,
      blocks: [
        { id: 'b1', type: 'heading', depth: 0, body: 'Section 1' },
        { id: 'b2', type: 'paragraph', depth: 0, body: 'Content of section 1' },
        { id: 'b3', type: 'heading', depth: 0, body: 'Section 2' },
        { id: 'b4', type: 'paragraph', depth: 0, body: 'Content of section 2' },
      ],
      citations: [], content_digest: 'digest', authority_baseline_id: null,
      derived_from_revision_id: null, published_at: null, updated_at: null,
    },
    revisions: [{ id: 'REV-001', revision: 1, state: 'draft', lock_version: 1, derived_from_revision_id: null, published_at: null, updated_at: null }],
    revision_selection: { requested_id: null, selected_id: 'REV-001', state: 'OK', policy: 'LATEST' },
  },
  selection: { requested_id: 'KU-001', resolved_id: 'KU-001', state: 'OK' },
  content_contract: mockContentContract as unknown,
  capability_manifest: { canonical_store: {}, hierarchy: { available: [], requires_parent_context: [] }, canonical_object_families_requiring_schema_or_parent_integration: [], projection_policy: 'strict' },
  context: { placements: [], sources: [{ id: 'SRC-001', title: 'Test Source', authority_class: 'High', review_status: 'Approved', href: null, claims: [] }], unresolved_citation_count: 0, hierarchy_state: 'NO_CURRICULUM_PLACEMENT', navigation: { research_quality: '/knowledge/research-quality?object=KU-001' } },
};

describe('W02 Static Library Editor', () => {
  beforeEach(() => {
    Object.defineProperty(window, 'innerWidth', { writable: true, configurable: true, value: 1440 });
  });

  it('renders folding chevrons for headings and auto-expands on focus', async () => {
    const wrapper = mount(Library, { props: defaultProps });

    const textareas = wrapper.findAll('textarea');
    expect(textareas.length).toBe(4);

    let toggles = wrapper.findAll('button[aria-label="طي القسم"]');
    expect(toggles.length).toBe(2);

    await toggles[0].trigger('click');
    await nextTick();

    toggles = wrapper.findAll('button[aria-label="طي القسم"]');
    expect(toggles.length).toBe(1);

    await textareas[1].trigger('focus');
    await nextTick();

    toggles = wrapper.findAll('button[aria-label="طي القسم"]');
    expect(toggles.length).toBe(2);
  });

  it('renders Research Quality deep link in sources lens', async () => {
    const wrapper = mount(Library, { props: defaultProps });

    const buttons = wrapper.findAll('button');
    const sourcesBtn = buttons.find(b => b.text().includes('المصادر'));
    await sourcesBtn!.trigger('click');
    await nextTick();

    expect(wrapper.html()).toContain('جودة البحث للمصادر');
    expect(wrapper.html()).toContain('/knowledge/research-quality?object=KU-001');
  });

  it('renders separate History task with timeline and compare actions', async () => {
    const wrapper = mount(Library, { props: defaultProps });

    const buttons = wrapper.findAll('button');
    const historyBtn = buttons.find(b => b.text().includes('السجل'));
    await historyBtn!.trigger('click');
    await nextTick();

    expect(wrapper.html()).toContain('سجل المراجعات القانوني');
    expect(wrapper.html()).toContain('مراجعة 1');
    expect(wrapper.html()).toContain('الحالية');

    let compareBtn = wrapper.findAll('button').find(b => b.text().trim() === 'قارن');
    expect(compareBtn).toBeUndefined();

    const propsWithHistory = JSON.parse(JSON.stringify(defaultProps));
    propsWithHistory.active.revisions.push({
      id: 'REV-000', revision: 0, state: 'published', lock_version: 1, derived_from_revision_id: null, published_at: null, updated_at: null,
    });

    const wrapper2 = mount(Library, { props: propsWithHistory });
    const buttons2 = wrapper2.findAll('button');
    const historyBtn2 = buttons2.find(b => b.text().includes('السجل'));
    await historyBtn2!.trigger('click');
    await nextTick();

    compareBtn = wrapper2.findAll('button').find(b => b.text().trim() === 'قارن');
    expect(compareBtn).toBeDefined();

    await compareBtn!.trigger('click');
    await nextTick();

    expect(wrapper2.html()).toContain('مقارنة بنيوية بالهوية المستقرة');
  });
});
