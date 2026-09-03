import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import ResearchQuality from '../pages/KnowledgeLearning/ResearchQuality.vue';
import type { KnowledgeUnitSelection } from '../pages/KnowledgeLearning/components/content/lessonContent';
import type { ResearchAnalysis } from '../pages/KnowledgeLearning/components/research-quality/types';

describe('W02 Static Research Quality Surface', () => {
  it('renders the initial UI correctly without mocking global evidence decisions', () => {
    const wrapper = mount(ResearchQuality, {
      props: {
        catalog: [],
        active: {
          id: 'TEST-01',
          title_ar: 'اختبار الجودة',
          title_en: 'Quality Test',
          revision: null,
        },
        selection: {
          state: 'ACTIVE_UNIT_SELECTED',
          selected_unit_id: 'TEST-01',
          context_mode: 'focus',
        } as unknown as KnowledgeUnitSelection,
        quality: {
          sources: [],
          active_source: null,
          source_selection: {
            requested_id: null,
            resolved_id: null,
            state: 'NO_ACTIVE_SELECTION',
          },
          canonical_claim_ids: [],
          review_semantics: 'READ_ONLY_REVIEW',
        },
        semantic_boundary: {
          review: 'Review Boundary',
          evidence_review: 'Evidence Review',
          mastery_judgment: 'Mastery Judgment',
        },
      },
      global: {
        stubs: ['Head', 'Link'],
      },
    });

    expect(wrapper.text()).toContain('اختبار الجودة');
    expect(wrapper.text()).toContain('Research & Quality Review != Evidence Review');
  });

  it('renders Compare mode and matching reference assertions', async () => {
    const wrapper = mount(ResearchQuality, {
      props: {
        catalog: [],
        active: {
          id: 'TEST-01',
          title_ar: 'اختبار الجودة',
          title_en: 'Quality Test',
          revision: null,
        },
        selection: {
          state: 'ACTIVE_UNIT_SELECTED',
          selected_unit_id: 'TEST-01',
          context_mode: 'focus',
        } as unknown as KnowledgeUnitSelection,
        quality: {
          sources: [],
          active_source: null,
          source_selection: {
            requested_id: null,
            resolved_id: null,
            state: 'NO_ACTIVE_SELECTION',
          },
          canonical_claim_ids: [],
          review_semantics: 'READ_ONLY_REVIEW',
          analysis: {
            review: { decision_authority: 'RQ-AUTH' },
            comparison: { rows: [] },
            provenance: { sources: [] },
            reconciliation: {
              persistence_boundary: { state: 'RQ_PERSISTENT_RECONCILIATION_OWNER_REQUIRED' },
              allowed_next_tools: [],
            },
            revision_reasoning: {
              canonical_claim_ids: [],
              resolved_claim_ids: [],
              unresolved_claim_ids: [],
              claim_sources: {},
            },
          } as unknown as ResearchAnalysis,
        },
        semantic_boundary: {
          review: 'Review Boundary',
          evidence_review: 'Evidence Review',
          mastery_judgment: 'Mastery Judgment',
        },
      },
      global: {
        stubs: ['Head', 'Link'],
      },
    });

    // Check if the source mode switcher switches to compare
    const buttons = wrapper.findAll('button');
    const compareButton = buttons.find(
      (b) => b.text().includes('Compare') || b.text().includes('مقارنة'),
    );
    expect(compareButton).toBeDefined();
    await compareButton?.trigger('click');
    expect(wrapper.text()).toContain('مقارنة المصادر');
  });

  it('renders Conflicts mode and matching reference assertions', async () => {
    const wrapper = mount(ResearchQuality, {
      props: {
        catalog: [],
        active: {
          id: 'TEST-01',
          title_ar: 'اختبار الجودة',
          title_en: 'Quality Test',
          revision: null,
        },
        selection: {
          state: 'ACTIVE_UNIT_SELECTED',
          selected_unit_id: 'TEST-01',
          context_mode: 'focus',
        } as unknown as KnowledgeUnitSelection,
        quality: {
          sources: [],
          active_source: null,
          source_selection: {
            requested_id: null,
            resolved_id: null,
            state: 'NO_ACTIVE_SELECTION',
          },
          canonical_claim_ids: [],
          review_semantics: 'READ_ONLY_REVIEW',
          analysis: {
            review: { decision_authority: 'RQ-AUTH' },
            comparison: { rows: [] },
            provenance: { sources: [] },
            reconciliation: {
              persistence_boundary: { state: 'RQ_PERSISTENT_RECONCILIATION_OWNER_REQUIRED' },
              allowed_next_tools: [],
            },
            revision_reasoning: {
              canonical_claim_ids: [],
              resolved_claim_ids: [],
              unresolved_claim_ids: [],
              claim_sources: {},
            },
            conflicts: [
              {
                claim_id: 'C-01',
                status: 'CONFLICT',
                variants: [
                  {
                    source_id: 'S-1',
                    source_title: 'Source 1',
                    segment_ref: 'seg-1',
                    supported_scope: 'scope 1',
                    excluded_semantics: 'none',
                    assessment: 'VALID',
                  },
                ],
              },
            ],
          } as unknown as ResearchAnalysis,
        },
        semantic_boundary: {
          review: 'Review Boundary',
          evidence_review: 'Evidence Review',
          mastery_judgment: 'Mastery Judgment',
        },
      },
      global: {
        stubs: ['Head', 'Link'],
      },
    });

    const buttons = wrapper.findAll('button');
    const conflictsBtn = buttons.find(
      (b) => b.text().includes('Conflicts') || b.text().includes('تعارض'),
    );
    expect(conflictsBtn).toBeDefined();
    await conflictsBtn?.trigger('click');
    expect(wrapper.text()).toContain('التعارض وإعادة التوفيق');
    expect(wrapper.text()).toContain('C-01');
    expect(wrapper.text()).toContain('Source 1');
  });

  it('verifies dark mode bidi styles in UI', () => {
    const wrapper = mount(ResearchQuality, {
      props: {
        catalog: [],
        active: {
          id: 'TEST-01',
          title_ar: 'اختبار الجودة',
          title_en: 'Quality Test',
          revision: null,
        },
        selection: { state: 'ACTIVE_UNIT_SELECTED' } as unknown as KnowledgeUnitSelection,
        quality: {
          sources: [],
          active_source: null,
          source_selection: { requested_id: null, resolved_id: null, state: 'NO_ACTIVE_SELECTION' },
          canonical_claim_ids: [],
          review_semantics: 'READ_ONLY_REVIEW',
        },
        semantic_boundary: {
          review: 'Review Boundary',
          evidence_review: 'Evidence Review',
          mastery_judgment: 'Mastery Judgment',
        },
      },
      global: {
        stubs: ['Head', 'Link', 'CepWorkspaceLayout'],
      },
    });

    const main = wrapper.find('main');
    expect(main.attributes('dir')).toBe('rtl');
    expect(main.classes()).toContain('dark:bg-[#0b1322]/90');

    const aside = wrapper.find('aside[aria-label="تتبّع المنشأ وحدود المراجعة"]');
    expect(aside.attributes('dir')).toBe('rtl');
  });
});
