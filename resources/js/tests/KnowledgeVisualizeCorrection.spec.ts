import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { vi } from 'vitest';
import Visualize from '../pages/KnowledgeLearning/Visualize.vue';
import CanvasView from '../pages/KnowledgeLearning/components/visualize/views/CanvasView.vue';
import {
  parseVisualizeLocation,
  serializeVisualizeState,
} from '../pages/KnowledgeLearning/components/visualize/routeState';
import {
  derivePathStages,
  edgesForView,
} from '../pages/KnowledgeLearning/components/visualize/viewModels';
import type {
  OverlayState,
  VisualEdge,
  VisualNode,
} from '../pages/KnowledgeLearning/components/visualize/types';

const nodes: VisualNode[] = [
  { id: 'domain:D05', kind: 'domain', label: 'أمن التطبيقات', technical_label: 'D05' },
  { id: 'cluster:APPSEC', kind: 'capability_cluster', label: 'APPSEC', technical_label: 'APPSEC' },
  {
    id: 'capability:SQLI',
    kind: 'capability',
    label: 'Injection Defense',
    technical_label: 'CAP-SQLI',
  },
  {
    id: 'ku:SQLI-BASE',
    kind: 'knowledge_unit',
    label: 'أساسيات SQL',
    technical_label: 'KU-SQLI-BASE',
  },
  { id: 'ku:SQLI', kind: 'knowledge_unit', label: 'SQL Injection', technical_label: 'KU-SQLI' },
];
const edges: VisualEdge[] = [
  {
    id: 'e-domain',
    from: 'domain:D05',
    to: 'cluster:APPSEC',
    type: 'contains',
    semantic: 'containment',
    revision: 1,
    lifecycle: {},
    supported_views: ['Tree', 'Graph', 'Canvas'],
  },
  {
    id: 'e-cluster',
    from: 'cluster:APPSEC',
    to: 'capability:SQLI',
    type: 'contains',
    semantic: 'containment',
    revision: 1,
    lifecycle: {},
    supported_views: ['Tree', 'Graph', 'Canvas'],
  },
  {
    id: 'e-unit',
    from: 'capability:SQLI',
    to: 'ku:SQLI',
    type: 'contains',
    semantic: 'containment',
    revision: 1,
    lifecycle: {},
    supported_views: ['Tree', 'Graph', 'Canvas'],
  },
  {
    id: 'e-prerequisite',
    from: 'ku:SQLI-BASE',
    to: 'ku:SQLI',
    type: 'prerequisite',
    semantic: 'prerequisite',
    revision: 1,
    lifecycle: {},
    supported_views: ['Path', 'Graph', 'Canvas'],
  },
];
const overlay: OverlayState = {
  active: null,
  available: ['prerequisite'],
  layers: {
    prerequisite: {
      available: true,
      source: 'curriculum.lifecycle.prerequisite_ku_ids',
      supported_views: ['Tree', 'Path', 'Graph', 'Canvas'],
      observations: [
        {
          id: 'obs-prerequisite',
          target: { kind: 'edge', id: 'e-prerequisite' },
          state: 'required_before',
          label: 'متطلب سابق مسجل',
          supported_views: ['Tree', 'Path', 'Graph', 'Canvas'],
          provenance: { source: 'curriculum.lifecycle.prerequisite_ku_ids' },
        },
      ],
    },
  },
};

const defaultProps = {
  catalog: [
    { id: 'KU-SQLI', title_ar: 'SQL Injection', title_en: 'SQL Injection' },
    { id: 'KU-OTHER', title_ar: 'وحدة خارج العالم', title_en: 'Outside world' },
  ],
  active: { id: 'KU-SQLI', title_ar: 'SQL Injection', title_en: 'SQL Injection' },
  map: {
    saved: false,
    id: null,
    state: 'UNSAVED_PROJECTION',
    state_label: 'عرض مشتق غير محفوظ',
    scope: { kind: 'knowledge_unit', id: 'KU-SQLI' },
    world: {
      recipe: 'bounded_curriculum_neighborhood_v1',
      membership: nodes.map((node) => node.id),
    },
    canonical_node_ids: nodes.map((node) => node.id),
    visual_positions: {},
    default_view: 'Tree' as const,
  },
  view: { implemented: ['Tree', 'Path', 'Graph', 'Canvas'], not_implemented: [], default: 'Tree' },
  overlay,
  graph: {
    source: 'canonical_curriculum_typed_projection',
    recipe: 'bounded_curriculum_neighborhood_v1',
    nodes,
    edges,
  },
  state: {
    map: null,
    view: 'Tree' as const,
    overlay: null,
    filter: 'all' as const,
    selection: null,
  },
};

const mountVisualize = () =>
  mount(Visualize, {
    props: defaultProps,
    global: {
      stubs: {
        CepWorkspaceLayout: { template: '<div><slot/><slot name="primaryNavigation"/></div>' },
        KnowledgeTabs: true,
        Link: { template: '<a><slot /></a>' },
        Head: true,
      },
    },
  });

describe('Knowledge Visualize Work #2', () => {
  it('uses Tree as the governed default and keeps catalog navigation separate from map membership', async () => {
    const wrapper = mountVisualize();

    expect(wrapper.text()).toContain('الهيكل القانوني المتدرج');
    expect(wrapper.text()).toContain('أعضاء عالم العرض');
    expect(wrapper.text()).toContain('استكشاف المكتبة');
    expect(wrapper.text()).toContain('عناصر للتنقل وليست أعضاءً ضمن الخريطة الحالية');

    const sqlNode = wrapper
      .findAll('button')
      .find((button) => button.text().includes('SQL Injection'));
    expect(sqlNode).toBeTruthy();
    await sqlNode!.trigger('click');
    expect(wrapper.text()).toContain('سياق التحديد');
    expect(wrapper.text()).toContain('KU-SQLI');
  });

  it('renders distinct Path and unique-node spatial Graph semantics', async () => {
    const wrapper = mountVisualize();
    const pathTab = wrapper.findAll('button').find((button) => button.text().trim() === 'Path');
    await pathTab!.trigger('click');
    expect(wrapper.text()).toContain('المسار المشتق من المتطلبات السابقة');
    expect(wrapper.text()).toContain('STAGE 1');
    expect(wrapper.text()).toContain('STAGE 2');

    const graphTab = wrapper.findAll('button').find((button) => button.text().trim() === 'Graph');
    await graphTab!.trigger('click');
    expect(wrapper.text()).toContain('عالم العلاقات المركّز');
    expect(wrapper.findAll('foreignObject')).toHaveLength(nodes.length);
    expect(wrapper.findAll('svg button')).toHaveLength(nodes.length);
  });

  it('activates the real prerequisite overlay and gives it a semantic Tree effect', async () => {
    const wrapper = mountVisualize();
    const prerequisite = wrapper
      .findAll('button')
      .find((button) => button.text().includes('المتطلبات السابقة'));
    await prerequisite!.trigger('click');

    expect(prerequisite!.attributes('aria-pressed')).toBe('true');
    expect(wrapper.text()).toContain('متطلبات سابقة');
    expect(wrapper.text()).toContain('طبقة تحليلية نشطة');
  });

  it('derives Path only from eligible prerequisite edges', () => {
    expect(edgesForView(edges, 'Tree').map((edge) => edge.id)).not.toContain('e-prerequisite');
    expect(edgesForView(edges, 'Path').map((edge) => edge.id)).toEqual(['e-prerequisite']);
    expect(derivePathStages(nodes, edges)).toHaveLength(2);
  });

  it('round-trips lawful URL state and rejects stale or unsupported context', () => {
    const state = {
      map: null,
      view: 'Graph' as const,
      overlay: 'prerequisite' as const,
      filter: 'all' as const,
      selection: { kind: 'edge' as const, id: 'e-prerequisite' },
    };
    const href = serializeVisualizeState('KU-SQLI', state);
    expect(href).toContain('view=Graph');
    expect(href).toContain('overlay=prerequisite');
    expect(href).toContain('selection=edge%3Ae-prerequisite');

    expect(parseVisualizeLocation(href.split('?')[1] ?? '', state, nodes, edges, overlay)).toEqual(
      expect.objectContaining(state),
    );
    const stale = parseVisualizeLocation(
      '?view=Tree&overlay=mastery&selection=node%3Aku%3AMISSING&map=foreign',
      state,
      nodes,
      edges,
      overlay,
    );
    expect(stale.view).toBe('Tree');
    expect(stale.overlay).toBeNull();
    expect(stale.selection).toBeNull();
    expect(stale.map).toBeNull();

    const ineligibleEdge = parseVisualizeLocation(
      '?view=Tree&selection=edge%3Ae-prerequisite',
      state,
      nodes,
      edges,
      overlay,
    );
    expect(ineligibleEdge.selection).toBeNull();
  });

  it('moves Canvas positions with a keyboard alternative without changing edges', async () => {
    const wrapper = mount(CanvasView, {
      props: {
        nodes,
        edges,
        selection: null,
        overlayLayer: overlay.layers.prerequisite ?? null,
        visualPositions: { 'ku:SQLI': { x: 480, y: 280 } },
      },
    });
    const sqlNode = wrapper
      .findAll('svg button')
      .find((button) => button.text().includes('SQL Injection'));
    await sqlNode!.trigger('keydown', { key: 'ArrowRight' });

    const move = wrapper.emitted('moveNode')?.[0]?.[0] as {
      id: string;
      x: number;
      y: number;
      method: string;
    };
    expect(move).toEqual({ id: 'ku:SQLI', x: 496, y: 280, method: 'keyboard' });
    expect(edges.find((edge) => edge.id === 'e-prerequisite')?.from).toBe('ku:SQLI-BASE');
  });

  it('keeps CENTER ownership at medium desktop and moves unique RIGHT context into a drawer', () => {
    const wrapper = mountVisualize();
    const html = wrapper.html();

    expect(html).toContain('md:grid-cols-[220px_minmax(0,1fr)]');
    expect(html).toContain('xl:grid-cols-[260px_minmax(0,1fr)_300px]');
    expect(html).toContain('xl:static');
    expect(wrapper.findAll('bdi[dir="ltr"]').length).toBeGreaterThan(6);
  });

  it('proves native Graph-edge DataClone repair via clone-safe History state and correct RIGHT context', async () => {
    const pushStateSpy = vi.spyOn(window.history, 'pushState');

    const wrapper = mount(Visualize, {
      props: {
        ...defaultProps,
        state: { ...defaultProps.state, view: 'Graph' },
      },
      global: {
        stubs: {
          CepWorkspaceLayout: { template: '<div><slot/><slot name="primaryNavigation"/></div>' },
          KnowledgeTabs: true,
          Link: { template: '<a><slot /></a>' },
          Head: true,
        },
      },
    });

    expect(wrapper.text()).toContain('لم يُحدّد رابط مرجعي');

    // 1. Click Edge to trigger History and Selection
    const edgePaths = wrapper.findAll('svg path[role="button"]');
    expect(edgePaths.length).toBeGreaterThan(0);
    const prerequisiteEdge = edgePaths.find(p => p.attributes('aria-label')?.includes('prerequisite'));
    expect(prerequisiteEdge).toBeDefined();

    await prerequisiteEdge!.trigger('click');

    // 2. Prove DataClone-safe primitive `pushState` exactly equals `edge:<id>`
    expect(pushStateSpy).toHaveBeenCalled();
    const lastCall = pushStateSpy.mock.calls[pushStateSpy.mock.calls.length - 1];
    expect(lastCall[0]).toEqual({ selection: 'edge:e-prerequisite' });
    expect(lastCall[2]).toContain('selection=edge%3Ae-prerequisite');

    // 3. Prove correct RIGHT Context panel updates
    expect(wrapper.text()).toContain('سياق الرابط المعرفي المرجعي');
    expect(wrapper.text()).toContain('e-prerequisite');

    pushStateSpy.mockRestore();
  });
});

it('drops selection and synchronized URL state when an edge is filter-pruned', async () => {
  const pushStateSpy = vi.spyOn(window.history, 'pushState');
  const wrapper = mount(Visualize, {
    props: {
      ...defaultProps,
      state: {
        map: null,
        view: 'Graph',
        overlay: null,
        filter: 'all',
        selection: { kind: 'edge', id: 'e-prerequisite' },
      },
    },
    global: {
      stubs: {
        CepWorkspaceLayout: { template: '<div><slot/><slot name="primaryNavigation"/></div>' },
        KnowledgeTabs: true,
        Link: { template: '<a><slot /></a>' },
        Head: true,
      },
    },
  });

  // 1. Enter/select a Graph edge.
  // Ensure the edge is initially selected and context is shown
  expect(wrapper.text()).toContain('سياق الرابط المعرفي المرجعي');

  // 2. Choose a filter that makes the edge invisible because one/both endpoints are filtered.
  // The structure filter hides knowledge_unit nodes. Since e-prerequisite connects two KUs, it will be hidden.
  const structureFilterButton = wrapper.findAll('button').find((b) => b.text().includes('الهيكل'));
  await structureFilterButton!.trigger('click');

  // 3. Selection becomes null and RIGHT edge context disappears.
  expect(wrapper.text()).not.toContain('سياق الرابط المعرفي المرجعي');

  // 4. Synchronized URL/history no longer carries stale selection.
  expect(pushStateSpy).toHaveBeenCalled();
  const lastCall = pushStateSpy.mock.calls[pushStateSpy.mock.calls.length - 1];
  expect(lastCall[2]).not.toContain('selection=edge');

  pushStateSpy.mockRestore();
});
