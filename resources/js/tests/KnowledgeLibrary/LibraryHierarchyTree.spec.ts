import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import LibraryHierarchyTree from '../../pages/KnowledgeLearning/components/library/LibraryHierarchyTree.vue';
import type { LibraryHierarchyProjection } from '../../pages/KnowledgeLearning/components/library/libraryHierarchy';

const projection: LibraryHierarchyProjection = {
  domains: [
    {
      id: 'DOM-SEC',
      title_ar: 'الأمن السيبراني',
      title_en: 'Cybersecurity',
      clusters: [
        {
          id: 'CL-IAM',
          title_ar: 'الهوية والوصول',
          title_en: 'Identity and Access',
          capabilities: [
            {
              id: 'CAP-AUTH',
              title_ar: 'المصادقة',
              title_en: 'Authentication',
              items: [
                {
                  canonical_ref: { kind: 'knowledge_unit', id: 'KU-AUTH-001' },
                  title_ar: 'أساسيات المصادقة',
                  title_en: 'Authentication Fundamentals',
                  latest_revision: 4,
                  latest_state: 'published',
                  revision_count: 4,
                  published_revision: 4,
                  lesson_availability: 'PUBLISHED_LESSON_AVAILABLE',
                  projection_reason: 'curriculum_placement',
                  placement: { id: 'P-1', revision: 2, lifecycle: { state: 'active' } },
                },
              ],
            },
          ],
        },
      ],
    },
  ],
  unresolved_capabilities: [
    {
      capability_id: 'CAP-NO-CONTEXT',
      integration_state: 'missing_hierarchy_context',
      items: [
        {
          canonical_ref: { kind: 'knowledge_unit', id: 'KU-ORPHAN-001' },
          title_ar: 'وحدة بانتظار السياق',
          title_en: 'Awaiting Context',
          latest_revision: null,
          latest_state: null,
          revision_count: 0,
          published_revision: null,
          lesson_availability: 'NO_PUBLISHED_LESSON',
          projection_reason: 'curriculum_placement',
          placement: { id: 'P-2', revision: 1, lifecycle: {} },
        },
      ],
    },
  ],
  unplaced: [],
};

describe('LibraryHierarchyTree', () => {
  it('renders the four-level structural path and keeps technical identifiers LTR', () => {
    const wrapper = mount(LibraryHierarchyTree, {
      props: { projection, activeId: 'KU-AUTH-001' },
      global: {
        stubs: {
          Link: {
            props: ['href'],
            template: '<a :href="href"><slot /></a>',
          },
        },
      },
    });

    expect(wrapper.text()).toContain('الأمن السيبراني');
    expect(wrapper.text()).toContain('الهوية والوصول');
    expect(wrapper.text()).toContain('المصادقة');
    expect(wrapper.text()).toContain('أساسيات المصادقة');
    expect(wrapper.text()).toContain('CAP-NO-CONTEXT');

    const ltrText = wrapper
      .findAll('[dir="ltr"]')
      .map((node) => node.text())
      .join(' ');
    expect(ltrText).toContain('DOM-SEC');
    expect(ltrText).toContain('CL-IAM');
    expect(ltrText).toContain('CAP-AUTH');
    expect(ltrText).toContain('KU-AUTH-001');
  });

  it('links every projection to the same canonical object route instead of copying content', () => {
    const wrapper = mount(LibraryHierarchyTree, {
      props: { projection },
      global: {
        stubs: {
          Link: {
            props: ['href'],
            template: '<a :href="href"><slot /></a>',
          },
        },
      },
    });

    const hrefs = wrapper.findAll('[href]').map((anchor) => anchor.attributes('href'));
    expect(hrefs).toContain('/knowledge?object=KU-AUTH-001');
    expect(hrefs).toContain('/knowledge?object=KU-ORPHAN-001');
  });
});
