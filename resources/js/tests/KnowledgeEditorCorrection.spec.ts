import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

import Library from '../pages/KnowledgeLearning/Library.vue';
import { lessonContentContractFixture } from './fixtures/lessonContentContract';

vi.mock('@inertiajs/vue3', () => ({
  Head: { template: '<div><slot /></div>' },
  Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
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
    selection: {
      requested_id: 'unit-1',
      resolved_id: 'unit-1',
      state: 'REQUESTED_CANONICAL_UNIT',
    },
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
      revisions: [
        {
          id: 'rev-1',
          revision: 1,
          state: 'draft',
          lock_version: 1,
          derived_from_revision_id: null,
          published_at: null,
          updated_at: null,
        },
      ],
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

  const mountOptions = {
    global: {
      stubs: {
        CepWorkspaceLayout: {
          template:
            '<div><slot name="top" /><slot name="left" /><slot /><slot name="right" /><slot name="bottom" /></div>',
        },
        KnowledgeTabs: { template: '<div />' },
        LibraryHierarchyTree: { template: '<div />' },
        LessonContentRenderer: { template: '<div />' },
      },
    },
  };

  beforeEach(() => {
    vi.restoreAllMocks();
    window.localStorage.clear();
    if (!window.performance.getEntriesByType) {
      window.performance.getEntriesByType = vi.fn(() => []);
    }
  });

  it('validates citations interactively against the backend contract mirror', async () => {
    const wrapper = mount(Library, { props: createProps(), ...mountOptions });
    await nextTick();

    await wrapper.find('button[title="إدراج استشهاد"]').trigger('click');
    const dialogInput = wrapper.find('#editor-dialog-value');
    await dialogInput.setValue('INVALID-AUTH-123');
    await wrapper.find('[role="dialog"] form').trigger('submit');

    expect(wrapper.find('[role="dialog"] [role="alert"]').text()).toBe(
      'معرّف المرجع غير صالح. استخدم معرّفًا محكومًا مثل KU-D05-0021-CLM-0001 أو WEB-AUTH-001.',
    );

    await dialogInput.setValue('WEB-AUTH-002');
    await wrapper.find('[role="dialog"] form').trigger('submit');
    expect((wrapper.vm as unknown as { form: { citations: string[] } }).form.citations).toContain(
      'WEB-AUTH-002',
    );
  });

  it('rejects non-HTTPS and nested URL schemes in the link dialog', async () => {
    const wrapper = mount(Library, {
      props: createProps(['KU-D05-0021-CLM-0001']),
      ...mountOptions,
    });
    await nextTick();

    await wrapper.find('button[title="إدراج رابط مرجعي"]').trigger('click');
    const dialogInput = wrapper.find('#editor-dialog-value');
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

  it('owns formatting in one selection-aware toolbar', async () => {
    const props = createProps(['KU-D05-0021-CLM-0001']);
    props.active.revision.blocks = [
      { type: 'paragraph', body: 'هذا السطر يختبر resource داخل العربية.', depth: 0 },
      { type: 'paragraph', body: 'English prefix — عبارة عربية — suffix.', depth: 0 },
    ];
    const wrapper = mount(Library, { props, ...mountOptions, attachTo: document.body });
    await nextTick();

    expect(wrapper.findAll('[role="toolbar"]')).toHaveLength(1);
    expect(wrapper.findAll('textarea[dir="auto"]')).toHaveLength(2);

    const second = wrapper.find('#knowledge-block-1');
    await second.trigger('focus');
    const vm = wrapper.vm as unknown as {
      replaceSelection: (index: number, before: string, after: string, fallback: string) => void;
      form: { blocks: Array<{ body: string }> };
    };
    const input = second.element as HTMLTextAreaElement;
    input.setSelectionRange(input.value.length, input.value.length);
    vm.replaceSelection(1, '**', '**', 'نص بارز');
    await nextTick();

    expect(vm.form.blocks[1]?.body).toBe('English prefix — عبارة عربية — suffix.**نص بارز**');
    wrapper.unmount();
  });

  it('inserts after the active subtree and preserves stable block identities while moving', async () => {
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
      form: { blocks: Array<{ id: string; body: string; depth: number }> };
    };

    vm.activeBlockIndex = 0;
    vm.addBlock();
    const insertedId = vm.form.blocks[2]?.id;
    expect(insertedId).toMatch(/^[0-9A-Za-z_-]{24}$/);
    vm.form.blocks[2]!.body = 'Inserted';
    vm.moveBlock(2, 1);

    expect(vm.form.blocks.map((block) => block.body)).toEqual([
      'Parent',
      'Child',
      'Sibling',
      'Inserted',
    ]);
    expect(vm.form.blocks[3]?.id).toBe(insertedId);
  });

  it('queues edits made during an in-flight autosave and never acknowledges the newer snapshot', async () => {
    vi.useFakeTimers();
    const props = createProps();
    props.active.revision.blocks = [{ type: 'paragraph', body: 'Original', depth: 0 }];
    const wrapper = mount(Library, { props, ...mountOptions });
    await nextTick();
    const vm = wrapper.vm as unknown as {
      form: {
        blocks: Array<{ body: string }>;
        patch: ReturnType<typeof vi.fn>;
        processing: boolean;
      };
      autosaveState: string;
      autosaveQueued: boolean;
      savedSnapshot: { blocks: Array<{ body: string }> };
      persistRecovery: (snapshot: unknown) => void;
      currentSnapshot: () => unknown;
      submitRevision: (mode: 'auto') => void;
    };

    vm.form.blocks[0]!.body = 'Edit A';
    await nextTick();
    vm.persistRecovery(vm.currentSnapshot());
    vm.submitRevision('auto');
    const firstCallbacks = vm.form.patch.mock.calls[0]?.[1];

    vm.form.processing = true;
    vm.form.blocks[0]!.body = 'Edit B';
    await nextTick();
    vm.persistRecovery(vm.currentSnapshot());
    vm.submitRevision('auto');

    firstCallbacks.onSuccess();
    expect(vm.autosaveState).toBe('pending');
    expect(vm.savedSnapshot.blocks[0]?.body).toBe('Original');
    expect(
      JSON.parse(window.localStorage.getItem('cep:knowledge-editor:rev-1') ?? '{}').snapshot
        .blocks[0].body,
    ).toBe('Edit B');

    vm.form.processing = false;
    firstCallbacks.onFinish();
    vi.advanceTimersByTime(1100);
    expect(vm.form.patch).toHaveBeenCalledTimes(2);
    vi.useRealTimers();
  });

  it('presents revision history as a first-class task and protects the last citation', async () => {
    const wrapper = mount(Library, {
      props: createProps(['WIN-AUTH-001', 'WEB-AUTH-002']),
      ...mountOptions,
    });
    await nextTick();

    expect(wrapper.find('[aria-label="سجل المراجعات"]').exists()).toBe(true);
    const vm = wrapper.vm as unknown as {
      removeCitation: (citation: string) => void;
      form: { citations: string[] };
      contractValidationError: string;
    };
    vm.removeCitation('WIN-AUTH-001');
    vm.removeCitation('WEB-AUTH-002');
    expect(vm.form.citations).toEqual(['WEB-AUTH-002']);
    expect(vm.contractValidationError).toContain('استشهاد واحد على الأقل');
  });
});
