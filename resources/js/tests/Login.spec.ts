import { mount } from '@vue/test-utils';

import Login from '../pages/Auth/Login.vue';

describe('Login', () => {
  it('renders Arabic-first secure local-owner guidance', () => {
    const wrapper = mount(Login, { props: { ownerExists: false } });
    expect(wrapper.text()).toContain('دخول مالك المنصة');
    expect(wrapper.text()).toContain('php artisan owner:create');
    expect(wrapper.find('input[type="password"]').attributes('autocomplete')).toBe(
      'current-password',
    );
    expect(wrapper.find('form').exists()).toBe(true);
  });
});
