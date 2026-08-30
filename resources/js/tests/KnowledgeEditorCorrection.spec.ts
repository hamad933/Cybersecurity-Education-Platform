import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import Library from '../pages/KnowledgeLearning/Library.vue';
import { nextTick } from 'vue';
import { lessonContentContractFixture } from './fixtures/lessonContentContract';

vi.mock('@inertiajs/vue3', () => ({
  Head: { template: '<div><slot /></div>' },
  Link: { template: '<a><slot /></a>' },
  usePage: () => ({ props: { flash: {}, errors: {} } }),
  useForm: (initialValues: Record<string, unknown>) => ({
    ...initialValues,
    errors: {},
    processing: false,
    clearErrors: vi.fn(),
    patch: vi.fn(),
    post: vi.fn(),
  }),
  router: { post: vi.fn() },
}));

describe('KnowledgeEditorCorrection', () => {
  const createProps = (citations = ['WIN-AUTH-001']) => ({
    catalog: [],
    structure: { domains: [], unresolved_capabilities: [], unplaced: [] },
    selection: { requested_id: 'unit-1', resolved_id: 'unit-1', state: 'REQUESTED_CANONICAL_UNIT' },
    content_contract: lessonContentContractFixture,
    capability_manifest: {
      canonical_store: {},
      hierarchy: { available: [], requires_parent_context: [] },
      canonical_object_families_requiring_schema_or_parent_integration: [],
      projection_policy: 'reference_canonical_objects_without_silent_copy',
    },
    active: {
      id: 'unit-1',
      canonical_ref: { kind: 'knowledge_unit' as const, id: 'unit-1' },
      title_ar: 'الوحدة 1',
      title_en: 'Unit 1',
      revisions: [],
      revision_selection: {
        requested_id: null,
        selected_id: 'rev-1',
        state: 'LATEST_REVISION',
        policy: 'explicit_revision_or_latest_revision',
      },
      revision: {
        id: 'rev-1',
        revision: 1,
        state: 'draft',
        lock_version: 1,
        editable: true,
        blocks: [{ type: 'paragraph', body: 'Test content', depth: 0 }],
        citations,
        content_digest: 'digest',
        authority_baseline_id: null,
        derived_from_revision_id: null,
        published_at: null,
        updated_at: null,
      },
    },
    context: {
      placements: [],
      sources: [],
      unresolved_citation_count: 0,
      hierarchy_state: 'NO_CURRICULUM_PLACEMENT',
      navigation: {},
    },
  });

  beforeEach(() => {
    vi.restoreAllMocks();

    if (typeof window !== 'undefined' && !window.performance) {
      Object.defineProperty(window, 'performance', { configurable: true, value: {} });
    }
    if (
      typeof window !== 'undefined' &&
      window.performance &&
      !window.performance.getEntriesByType
    ) {
      window.performance.getEntriesByType = vi.fn(() => []);
    }
  });

  const mountOptions = {
    global: {
      stubs: {
        CepWorkspaceLayout: { template: '<div><slot /></div>' },
        KnowledgeTabs: { template: '<div></div>' },
        LibraryHierarchyTree: { template: '<div></div>' },
      },
    },
  };

  it('validates citations interactively against backend contract (UX mirror)', async () => {
    const wrapper = mount(Library, { props: createProps(['WIN-AUTH-001']), ...mountOptions });
    await nextTick();

    const insertCitationButton = wrapper.find('button[title="إدراج استشهاد"]');
    expect(insertCitationButton.exists()).toBe(true);

    await insertCitationButton.trigger('click');
    await nextTick();
    const dialogInput = wrapper.find('#editor-dialog-value');
    expect(dialogInput.exists()).toBe(true);
    await dialogInput.setValue('INVALID-AUTH-123');
    await wrapper.find('[role="dialog"] form').trigger('submit');
    await nextTick();

    expect(wrapper.find('[role="dialog"] [role="alert"]').text()).toBe(
      'معرّف المرجع غير صالح. استخدم معرّفًا محكومًا مثل KU-D05-0021-CLM-0001 أو WEB-AUTH-001.',
    );

    // Test DOM rendered citations
    const renderedCitationsBefore = wrapper.findAll('bdi[dir="ltr"]');
    const citationsBeforeTexts = renderedCitationsBefore
      .map((b) => b.text())
      .filter((t) => t.includes('-AUTH-'));
    expect(citationsBeforeTexts.length).toBe(1);

    await dialogInput.setValue('WEB-AUTH-002');
    await wrapper.find('[role="dialog"] form').trigger('submit');
    await nextTick();

    expect((wrapper.vm as unknown as { form: { citations: string[] } }).form.citations).toContain(
      'WEB-AUTH-002',
    );
  });

  it('rejects non-HTTPS and nested URL schemes in the in-app link dialog', async () => {
    const wrapper = mount(Library, {
      props: createProps(['KU-D05-0021-CLM-0001']),
      ...mountOptions,
    });
    await nextTick();

    await wrapper.find('button[title="إدراج رابط مرجعي"]').trigger('click');
    const dialogInput = wrapper.find('#editor-dialog-value');
    expect((dialogInput.element as HTMLInputElement).value).toBe('');

    await dialogInput.setValue('http://example.test');
    await wrapper.find('[role="dialog"] form').trigger('submit');
    expect(wrapper.find('[role="dialog"] [role="alert"]').text()).toBe(
      'يُسمح فقط بروابط HTTPS صحيحة.',
    );

    await dialogInput.setValue('https://http://example.test');
    await wrapper.find('[role="dialog"] form').trigger('submit');
    expect(wrapper.find('[role="dialog"] [role="alert"]').text()).toBe(
      'يُسمح فقط بروابط HTTPS صحيحة.',
    );
  });

  it('owns formatting in one selection-aware toolbar instead of repeating a toolbar per block', async () => {
    const props = createProps(['KU-D05-0021-CLM-0001']);
    props.active.revision.blocks = [
      { type: 'paragraph', body: 'هذا السطر يختبر resource داخل العربية.', depth: 0 },
      { type: 'paragraph', body: 'English prefix — عبارة عربية — suffix.', depth: 0 },
    ];
    const wrapper = mount(Library, { props, ...mountOptions });
    await nextTick();

    expect(wrapper.findAll('[role="toolbar"]')).toHaveLength(1);
    expect(wrapper.findAll('textarea[dir="auto"]')).toHaveLength(2);

    const second = wrapper.find('#knowledge-block-1');
    await second.trigger('focus');
    expect((wrapper.vm as unknown as { activeBlockIndex: number }).activeBlockIndex).toBe(1);

    const secondInput = second.element as HTMLTextAreaElement;
    secondInput.setSelectionRange(secondInput.value.length, secondInput.value.length);
    await wrapper.find('button[title="خط عريض"]').trigger('click');
    expect(
      (wrapper.vm as unknown as { form: { blocks: Array<{ body: string }> } }).form.blocks[1]?.body,
    ).toBe('English prefix — عبارة عربية — suffix.**نص بارز**');
  });

  it('inserts after the active subtree and keeps selection attached while moving it', async () => {
    const props = createProps(['KU-D05-0021-CLM-0001']);
    props.active.revision.blocks = [
      { type: 'heading', body: 'Parent', depth: 0 },
      { type: 'paragraph', body: 'Child', depth: 1 },
      { type: 'heading', body: 'Sibling', depth: 0 },
    ];
    const wrapper = mount(Library, { props, ...mountOptions });
    await nextTick();
    const vm = wrapper.vm as unknown as {
      activeBlockIndex: number;
      addBlock: () => void;
      moveBlock: (index: number, delta: number) => void;
      form: { blocks: Array<{ body: string; depth: number }> };
    };

    vm.activeBlockIndex = 0;
    vm.addBlock();
    expect(vm.form.blocks.map((block) => block.body)).toEqual(['Parent', 'Child', '', 'Sibling']);
    expect(vm.form.blocks[2]?.depth).toBe(0);

    vm.form.blocks[2]!.body = 'Inserted';
    vm.moveBlock(2, 1);
    expect(vm.form.blocks.map((block) => block.body)).toEqual([
      'Parent',
      'Child',
      'Sibling',
      'Inserted',
    ]);
    expect(vm.activeBlockIndex).toBe(3);
  });

  it('prevents removing the last citation (minimum citation invariant)', async () => {
    const wrapper = mount(Library, {
      props: createProps(['WIN-AUTH-001', 'WEB-AUTH-002']),
      ...mountOptions,
    });
    await nextTick();

    // With 2 citations initially, both remove buttons should be visible
    const removeButtons = wrapper.findAll('button[aria-label^="حذف استشهاد"]');
    expect(removeButtons.length).toBe(2);

    // Click the first one to remove it
    const vm = wrapper.vm as unknown as {
      removeCitation: (citation: string) => void;
      form: { citations: string[] };
      contractValidationError: string;
    };
    vm.removeCitation('WIN-AUTH-001');
    await nextTick();

    // Once removed, there is 1 citation left. The v-if="form.citations.length > 1" hides the button.
    // In Vue test utils with mock refs, the array length may need manual check if reactivity is broken by proxy
    expect(vm.form.citations.length).toBe(1);
    // Try removing again to verify the invariant at the component boundary.
    vm.removeCitation('WEB-AUTH-002');
    expect(vm.contractValidationError).toBe(
      'يجب أن تحتوي الوحدة المعرفية على استشهاد واحد على الأقل كمرجع للسلطة.',
    );
    expect(vm.form.citations.length).toBe(1);
  });
});
