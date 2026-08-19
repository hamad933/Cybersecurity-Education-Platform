import { mount } from '@vue/test-utils';

import Today from '../pages/Today/Index.vue';

describe('Today workspace', () => {
  it('renders truthful orchestration handoffs and keeps diagnostics temporary', async () => {
    const wrapper = mount(Today, {
      props: {
        orchestration: {
          registeredDomainEntries: 3,
          expectedDomainEntries: 4,
        },
      },
    });

    expect(wrapper.text()).toContain('اليوم');
    expect(wrapper.text()).toContain('لا توجد بنود تشغيلية موثوقة مربوطة بسطح اليوم');
    expect(wrapper.find('.cep-action-bar').exists()).toBe(true);

    const workspaceLinks = wrapper.findAll('[data-today-workspace]');
    expect(workspaceLinks).toHaveLength(4);
    expect(workspaceLinks.map((link) => link.attributes('href'))).toEqual([
      '/knowledge',
      '/simulation',
      '/progress',
      '/system',
    ]);

    const right = wrapper.find('[data-cep-region="right"]');
    expect(right.text()).toContain('التوجيه والتنسيق بين مساحات العمل');
    expect(right.text()).not.toContain('3/4');
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);

    await wrapper.get('[data-testid="today-diagnostics-toggle"]').trigger('click');

    const bottom = wrapper.get('[data-cep-region="bottom"]');
    expect(bottom.text()).toContain('ربط مساحات العمل');
    expect(bottom.text()).toContain('3/4');

    expect(wrapper.text()).not.toContain('VS-001');
    expect(wrapper.text()).not.toContain('VS-002');
    expect(wrapper.text()).not.toContain('VS-003');
  });
});
