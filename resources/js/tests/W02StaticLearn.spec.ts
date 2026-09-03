import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import ProgressIndicator from '../pages/KnowledgeLearning/components/learn/ProgressIndicator.vue';
import LearningPathNode from '../pages/KnowledgeLearning/components/learn/LearningPathNode.vue';

describe('W02 Learn Component Corrections', () => {
  it('ProgressIndicator renders correctly in RTL', () => {
    const wrapper = mount(ProgressIndicator, {
      props: {
        total: 10,
        current: 5,
        rtl: true,
      },
    });

    expect(wrapper.attributes('dir')).toBe('rtl');
    expect(wrapper.find('div.bg-blue-600').attributes('style')).toContain('width: 50%');
  });

  it('ProgressIndicator bounds check', () => {
    const wrapper = mount(ProgressIndicator, {
      props: {
        total: 0,
        current: 5,
      },
    });
    expect(wrapper.find('div.bg-blue-600').attributes('style')).toContain('width: 0%');
  });

  it('LearningPathNode renders completed state with correct classes', () => {
    const wrapper = mount(LearningPathNode, {
      props: {
        title: 'درس تجريبي',
        state: 'completed',
      },
    });
    expect(wrapper.text()).toContain('درس تجريبي');
    expect(wrapper.html()).toContain('✓');
    expect(wrapper.classes()).toContain('bg-green-100');
  });

  it('LearningPathNode renders locked state correctly', () => {
    const wrapper = mount(LearningPathNode, {
      props: {
        title: 'درس تجريبي',
        state: 'locked',
      },
    });
    expect(wrapper.html()).toContain('🔒');
    expect(wrapper.classes()).toContain('bg-gray-100');
  });
});
