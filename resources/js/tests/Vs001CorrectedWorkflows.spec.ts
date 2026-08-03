import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import LessonEditor from '../pages/Vs001/LessonEditor.vue';
import MicroPractice from '../pages/Vs001/MicroPractice.vue';

const revision = (state: string) => ({
  id: '019f-test-revision',
  revision: 2,
  state,
  lock_version: 3,
  blocks: [{ type: 'paragraph', body: '<img src=x onerror=alert(1)>' }],
  citations: ['WIN-AUTH-002'],
  content_digest: 'a'.repeat(64),
  review_rationale: null,
  published_at: state === 'published' ? '2026-07-22T00:00:00Z' : null,
});

describe('corrected VS-001 workflows', () => {
  beforeEach(() => vi.mocked(router.post).mockClear());

  it.each([
    ['published', '/restore'],
    ['under_review', '/approve'],
    ['reviewed', '/publish'],
  ])('exposes the explicit %s lesson transition', async (state, suffix) => {
    const wrapper = mount(LessonEditor, {
      props: { revisions: [revision(state)], baseline: 'WIN-AUTH-MSFT-2026-07-22' },
    });

    const action = wrapper.findAll('button').at(-1);
    expect(action).toBeDefined();
    await action!.trigger('click');
    expect(router.post).toHaveBeenCalled();
    expect(vi.mocked(router.post).mock.calls[0][0]).toBe(
      `/vs001/lesson/019f-test-revision${suffix}`,
    );
    expect(wrapper.find('img').exists()).toBe(false);
    expect(wrapper.text()).toContain('<img src=x onerror=alert(1)>');
  });

  it('renders bounded draft controls with the optimistic lock payload', () => {
    const wrapper = mount(LessonEditor, {
      props: { revisions: [revision('draft')], baseline: 'WIN-AUTH-MSFT-2026-07-22' },
    });

    expect(wrapper.find('form').exists()).toBe(true);
    expect(wrapper.find('textarea[maxlength="4000"]').exists()).toBe(true);
    expect(wrapper.find('section form').findAll('button')).toHaveLength(2);
    expect(wrapper.html()).not.toContain('v-html');
  });

  it('renders every structured micro-practice field and bounded rationale', () => {
    const wrapper = mount(MicroPractice, {
      props: {
        practice: {
          definition: {
            prompt_ar: 'Structured decision',
            case_id: 'CASE-003-DENY-BEFORE-ALLOW',
            choices: ['ALLOW', 'DENY', 'INSUFFICIENT_STATE', 'UNSUPPORTED_STATE'],
            requires_rationale: true,
          },
        },
        latestAttempt: null,
      },
    });

    expect(wrapper.findAll('input[type="radio"]')).toHaveLength(4);
    expect(wrapper.find('#step').attributes('maxlength')).toBe('80');
    expect(wrapper.find('#ace').attributes('maxlength')).toBe('80');
    expect(wrapper.find('#requested-mask').exists()).toBe(true);
    expect(wrapper.find('#remaining-mask').exists()).toBe(true);
    expect(wrapper.find('#rationale').attributes('maxlength')).toBe('1000');
  });
});
