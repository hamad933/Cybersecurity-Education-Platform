import { mount } from '@vue/test-utils';

import Today from '../pages/Today/Index.vue';

describe('Today workspace', () => {
  it('renders truthful orchestration state without legacy or invented activity', () => {
    const wrapper = mount(Today, {
      props: {
        orchestration: {
          registeredDomainEntries: 3,
          expectedDomainEntries: 4,
        },
      },
    });

    expect(wrapper.text()).toContain('مساحة التنسيق');
    expect(wrapper.text()).toContain('لا توجد بيانات تشغيلية موثوقة لعرضها بعد');
    expect(wrapper.find('[data-cep-region="right"]').text()).toContain('3/4');
    expect(wrapper.text()).not.toContain('VS-001');
    expect(wrapper.text()).not.toContain('VS-002');
    expect(wrapper.text()).not.toContain('VS-003');
  });
});
