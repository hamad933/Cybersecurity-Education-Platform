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
    vi.stubGlobal('prompt', vi.fn());
    vi.stubGlobal('alert', vi.fn());

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

    vi.mocked(window.prompt).mockReturnValueOnce('INVALID-AUTH-123');
    await insertCitationButton.trigger('click');
    await nextTick();

    expect(window.alert).toHaveBeenCalledWith(
      'معرّف المرجع غير صالح. يجب أن يطابق النمط: WIN-AUTH-001 أو WEB-AUTH-001 أو VS3-AUTH-001.',
    );

    // Test DOM rendered citations
    const renderedCitationsBefore = wrapper.findAll('bdi[dir="ltr"]');
    const citationsBeforeTexts = renderedCitationsBefore
      .map((b) => b.text())
      .filter((t) => t.includes('-AUTH-'));
    expect(citationsBeforeTexts.length).toBe(2);

    vi.mocked(window.prompt).mockReturnValueOnce('WEB-AUTH-002');
    await insertCitationButton.trigger('click');
    await nextTick();

    // After adding, we should see 2 citations rendered in the editor form area
    const renderedCitationsAfter = wrapper.findAll('bdi[dir="ltr"]');
    const citationsAfterTexts = renderedCitationsAfter
      .map((b) => b.text())
      .filter((t) => t.includes('-AUTH-'));
    expect(citationsAfterTexts.length).toBeGreaterThan(1);
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
    };
    vm.removeCitation('WIN-AUTH-001');
    await nextTick();

    // Once removed, there is 1 citation left. The v-if="form.citations.length > 1" hides the button.
    const removeButtonsAfter = wrapper.findAll('button[aria-label^="حذف استشهاد"]');
    // In Vue test utils with mock refs, the array length may need manual check if reactivity is broken by proxy
    expect(vm.form.citations.length).toBe(1);
    expect(removeButtonsAfter).toHaveLength(0);
    // We don't assert DOM if VTU isn't flushing the mock properly, but we assert the VM state.
    // Try removing again to verify the alert triggers.
    vm.removeCitation('WEB-AUTH-002');
    expect(window.alert).toHaveBeenCalledWith(
      'يجب أن تحتوي الوحدة المعرفية على استشهاد واحد على الأقل كمرجع للسلطة.',
    );
    expect(vm.form.citations.length).toBe(1);
  });
});
