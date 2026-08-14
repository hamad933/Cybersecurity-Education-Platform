import { mount } from '@vue/test-utils';

import CepGlobalNavigation from '../components/cep/CepGlobalNavigation.vue';
import { CEP_GLOBAL_DESTINATIONS } from '../components/cep/navigation';
import CepTemporaryWorkspace from '../components/shared/CepTemporaryWorkspace.vue';
import TechnicalText from '../components/shared/TechnicalText.vue';
import CepWorkspaceLayout from '../layouts/CepWorkspaceLayout.vue';

describe('CEP shared foundation', () => {
  it('publishes only the approved five global destinations with stable hrefs', () => {
    expect(CEP_GLOBAL_DESTINATIONS).toEqual([
      { key: 'today', label: 'اليوم', href: '/' },
      { key: 'knowledge', label: 'المعرفة والتعلّم', href: '/knowledge' },
      { key: 'simulation', label: 'المحاكاة والمؤسسات', href: '/simulation' },
      { key: 'progress', label: 'التقدم والأدلة', href: '/progress' },
      { key: 'system', label: 'النظام والعمليات', href: '/system' },
    ]);

    const serialized = JSON.stringify(CEP_GLOBAL_DESTINATIONS);
    expect(serialized).not.toContain('/vs001');
    expect(serialized).not.toContain('/vs002');
    expect(serialized).not.toContain('/vs003');
  });

  it('marks exactly one active global destination for navigation', () => {
    const wrapper = mount(CepGlobalNavigation, {
      props: { activeDestination: 'knowledge' },
    });

    const links = wrapper.findAll('.cep-global-nav__link');
    expect(links).toHaveLength(5);
    expect(links.map((link) => link.attributes('href'))).toEqual([
      '/',
      '/knowledge',
      '/simulation',
      '/progress',
      '/system',
    ]);
    expect(wrapper.findAll('[aria-current="page"]')).toHaveLength(1);
    expect(wrapper.find('[aria-current="page"]').text()).toBe('المعرفة والتعلّم');
  });

  it('keeps the shell RTL and does not clone center identity into permanent regions', () => {
    const wrapper = mount(CepWorkspaceLayout, {
      props: { activeDestination: 'today' },
      slots: {
        left: '<p>بنية فريدة</p>',
        default: '<h1>عنوان العمل الفريد</h1>',
        right: '<p>سياق فريد</p>',
      },
    });

    expect(wrapper.attributes('dir')).toBe('rtl');
    expect(wrapper.find('.skip-link').attributes('href')).toBe('#main-content');
    expect(wrapper.find('#main-content').attributes('tabindex')).toBe('-1');
    expect(wrapper.find('[data-cep-region="left"]').text()).toBe('بنية فريدة');
    expect(wrapper.find('[data-cep-region="center"]').text()).toBe('عنوان العمل الفريد');
    expect(wrapper.find('[data-cep-region="right"]').text()).toBe('سياق فريد');
    expect(wrapper.text().match(/عنوان العمل الفريد/g) ?? []).toHaveLength(1);
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);
  });

  it('isolates technical identifiers explicitly as LTR text', () => {
    const wrapper = mount(TechnicalText, {
      props: { value: 'CEP-BUILD-001-W01' },
    });

    expect(wrapper.element.tagName).toBe('BDI');
    expect(wrapper.attributes('dir')).toBe('ltr');
    expect(wrapper.text()).toBe('CEP-BUILD-001-W01');
  });

  it('keeps the temporary workspace closed until explicitly opened', async () => {
    const wrapper = mount(CepTemporaryWorkspace, {
      props: { open: false },
      slots: { default: '<p>تشخيص مؤقت</p>' },
    });

    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);

    await wrapper.setProps({ open: true });

    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('تشخيص مؤقت');
  });
});
