import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import Learn from '../pages/KnowledgeLearning/Learn.vue';
import {
  areValidLessonCitations,
  citationMatchesContract,
  isValidLessonContent,
  isValidLessonHierarchy,
  normalizeLessonBlocks,
} from '../pages/KnowledgeLearning/components/content/lessonContent';
import { lessonContentContractFixture } from './fixtures/lessonContentContract';

const revision = {
  id: 'revision-published-2',
  revision: 2,
  state: 'published',
  lock_version: 4,
  blocks: [
    { type: 'heading', body: 'مدخل إلى حدود الثقة', depth: 0 },
    { type: 'paragraph', body: 'محتوى عربي قانوني من مراجعة Knowledge المنشورة.', depth: 1 },
    { type: 'code', body: 'GET /objects/42', depth: 1 },
  ],
  citations: ['WEB-AUTH-001'],
  authority_baseline_id: 'AUTHORITY-TEST',
  content_digest: 'a'.repeat(64),
  derived_from_revision_id: null,
  published_at: '2026-08-30T00:00:00+00:00',
  updated_at: '2026-08-30T00:00:00+00:00',
  editable: false,
};

const props = {
  catalog: [],
  active: {
    id: 'KU-W02-CONTINUITY',
    canonical_ref: { kind: 'knowledge_unit' as const, id: 'KU-W02-CONTINUITY' },
    title_ar: 'حدود الثقة',
    title_en: 'Trust boundaries',
  },
  lesson: {
    availability: 'AVAILABLE_PUBLISHED_REVISION',
    selection_policy: 'latest_published_revision_only',
    revision,
    unavailable_reason: null,
  },
  selection: {
    requested_id: 'KU-W02-CONTINUITY',
    resolved_id: 'KU-W02-CONTINUITY',
    state: 'REQUESTED_CANONICAL_UNIT',
  },
  content_contract: lessonContentContractFixture,
  journey: {
    state: 'NO_PRACTICE_ACTIVITY_DEFINED',
    items: [],
    labs: [],
    assessments: {
      state: 'NO_CANONICAL_ASSESSMENT_PERSISTENCE_IN_CURRENT_ARCHITECTURE',
      integration_state: 'AUTHORITATIVE_ASSESSMENT_CONTRACT_REQUIRED',
      semantic_owner: null,
      fake_fallback_allowed: false,
      executable: false,
      href: null,
      definitions: [],
      results: [],
    },
    next: { state: 'NO_PRACTICE_AVAILABLE', practice_id: null },
    activity: {
      practice_count: 0,
      attempt_count: 0,
      completed_practice_count: 0,
      started_practice_count: 0,
      completion_is_mastery: false,
      latest_activity_at: null,
      semantic_scope: 'journey_activity_only',
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
    navigation: {
      library: '/knowledge?object=KU-W02-CONTINUITY',
      visualize: '/knowledge/visualize?object=KU-W02-CONTINUITY',
      research_quality: '/knowledge/research-quality?object=KU-W02-CONTINUITY',
    },
    resume: {
      storage: 'browser_local',
      server_persisted: false,
      semantic_scope: 'reading_position_only_not_completion_or_mastery',
    },
  },
  semantic_boundary: {
    progress: 'journey_activity_context',
    completion: 'lesson_navigation_and_practice_activity_only',
    mastery: 'owned_by_progress_evidence',
  },
};

const global = {
  stubs: {
    CepWorkspaceLayout: {
      template: '<div><slot name="primaryNavigation" /><slot /></div>',
    },
    KnowledgeTabs: { template: '<nav data-testid="gateways"></nav>' },
    Head: { template: '<div><slot /></div>' },
    Link: {
      props: ['href'],
      template: '<a :href="href"><slot /></a>',
    },
  },
};

describe('Knowledge lesson/content continuity', () => {
  beforeEach(() => window.localStorage.clear());

  it('uses the server contract for block hierarchy and citation validation', () => {
    expect(
      isValidLessonHierarchy(normalizeLessonBlocks(revision.blocks), lessonContentContractFixture),
    ).toBe(true);
    expect(citationMatchesContract('WEB-AUTH-001', lessonContentContractFixture)).toBe(true);
    expect(citationMatchesContract('INVENTED-CLAIM', lessonContentContractFixture)).toBe(false);
    expect(areValidLessonCitations(['WEB-AUTH-001'], lessonContentContractFixture)).toBe(true);
    expect(
      areValidLessonCitations(['WEB-AUTH-001', 'WEB-AUTH-001'], lessonContentContractFixture),
    ).toBe(false);
    expect(
      isValidLessonContent(
        normalizeLessonBlocks(revision.blocks),
        revision.citations,
        lessonContentContractFixture,
      ),
    ).toBe(true);
  });

  it('renders published Knowledge revision content in Learn without creating a duplicate lesson object', async () => {
    const wrapper = mount(Learn, { props, global });

    expect(wrapper.text()).toContain('محتوى الدرس القانوني');
    expect(wrapper.text()).toContain('مدخل إلى حدود الثقة');
    expect(wrapper.text()).toContain('محتوى عربي قانوني من مراجعة Knowledge المنشورة.');
    expect(wrapper.text()).toContain('lesson_revision:revision-published-2');
    expect(wrapper.text()).not.toContain('لا توجد مراجعة منشورة للتعلّم');

    await wrapper.find('button[aria-label="طي أو توسيع المساحة السفلية"]').trigger('click');
    expect(wrapper.text()).toContain('Completion != Mastery');

    const positionButtons = wrapper.findAll('button[aria-label^="تعيين الكتلة"]');
    await positionButtons[1]?.trigger('click');
    expect(window.localStorage.getItem('cep:knowledge-learn:position:revision-published-2')).toBe(
      '1',
    );
  });
});
