import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Visualize from '../pages/KnowledgeLearning/Visualize.vue';

describe('KnowledgeVisualizeCorrection', () => {
  const defaultProps = {
    catalog: [],
    active: { id: 'ku:123', title_ar: 'اختبار', title_en: 'Test' },
    map: {
      saved: false,
      id: null,
      state: 'UNSAVED_PROJECTION',
      scope: { kind: 'knowledge_unit', id: 'ku:123' },
      canonical_node_ids: ['ku:123', 'cap:456', 'cap:789'],
      visual_positions: {},
    },
    view: { implemented: ['Tree', 'Path', 'Graph', 'Canvas'], not_implemented: [] },
    overlay: { active: null, available: [], layers: {} },
    graph: {
      source: 'canonical',
      nodes: [
        { id: 'ku:123', kind: 'knowledge_unit', label: 'Unit', technical_label: 'ku:123' },
        { id: 'cap:456', kind: 'capability', label: 'Cap 1', technical_label: 'cap:456' },
        { id: 'cap:789', kind: 'capability', label: 'Cap 2', technical_label: 'cap:789' },
      ],
      edges: [
        {
          id: 'edge1',
          from: 'cap:456',
          to: 'ku:123',
          type: 'placement',
          revision: 1,
          lifecycle: {},
        },
        {
          id: 'edge2',
          from: 'cap:789',
          to: 'ku:123',
          type: 'placement',
          revision: 1,
          lifecycle: {},
        },
      ],
    },
  };

  it('proves VIS-CONTEXT-01 and VIS-GRAPH-01 end-to-end selection interactions, clearing, and graph dimming', async () => {
    // Mount the full tree for Visualize to test actual component interactions
    const wrapper = mount(Visualize, {
      props: defaultProps,
      global: {
        stubs: {
          CepWorkspaceLayout: { template: '<div><slot/><slot name="primaryNavigation"/></div>' },
          KnowledgeTabs: true,
          Link: true,
          OverlayPanel: true,
          Head: true,
        },
      },
    });

    // We start with Graph view because active View resolves to Graph if available
    expect(wrapper.text()).toContain('التمثيل البصري للعقد والعلاقات');

    // 1. Initially, no canonical node is selected
    expect(wrapper.text()).toContain('لم تُحدّد عقدة معرفية مرجعية');

    // 2. Both edges should be visible (opacity-100) and not dimmed
    const articles = wrapper.findAll('article');
    expect(articles.length).toBe(2);
    expect(articles[0].classes()).toContain('opacity-100');
    expect(articles[1].classes()).toContain('opacity-100');

    // 3. User clicks the source canonical node button in the first edge (cap:456)
    const cap1FromButton = articles[0].findAll('button')[0];
    await cap1FromButton.trigger('click');

    // Prove Selected Aria state
    expect(cap1FromButton.attributes('aria-pressed')).toBe('true');

    // 4. Prove Context label and technical id update
    expect(wrapper.text()).toContain('Cap 1');
    expect(wrapper.text()).toContain('cap:456');

    // 5. Prove unrelated edge dimming (edge 2 should be dimmed)
    expect(articles[0].classes()).toContain('opacity-100');
    expect(articles[1].classes()).toContain('opacity-30');
    expect(articles[1].classes()).toContain('grayscale');

    // 6. User clicks the same canonical node button again to clear selection
    await cap1FromButton.trigger('click');

    // 7. Prove selection clears and everything resets
    expect(wrapper.text()).toContain('لم تُحدّد عقدة معرفية مرجعية');
    expect(cap1FromButton.attributes('aria-pressed')).toBe('false');
    expect(articles[0].classes()).toContain('opacity-100');
    expect(articles[1].classes()).toContain('opacity-100');
  });
});
