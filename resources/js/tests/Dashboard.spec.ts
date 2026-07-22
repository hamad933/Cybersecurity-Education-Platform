import { mount } from '@vue/test-utils';

import Dashboard from '../pages/Dashboard.vue';

describe('Dashboard', () => {
  it('renders bounded foundation health without fake workflows', () => {
    const wrapper = mount(Dashboard, {
      props: { health: { database: 'ok', queue: 'ok', storage: 'ok', migrations: 'ok' } },
    });
    expect(wrapper.text()).toContain('حالة المنصة المحلية');
    expect(wrapper.text()).toContain('MOD-IAM, MOD-PLT');
    expect(wrapper.text()).toContain('VS-001');
    expect(wrapper.text()).not.toContain('Scenario Studio');
  });
});
