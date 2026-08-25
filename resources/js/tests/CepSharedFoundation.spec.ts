import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';

import CepGlobalNavigation from '../components/cep/CepGlobalNavigation.vue';
import { CEP_GLOBAL_DESTINATIONS } from '../components/cep/navigation';
import CepTemporaryWorkspace from '../components/shared/CepTemporaryWorkspace.vue';
import TechnicalText from '../components/shared/TechnicalText.vue';
import { useTheme } from '../composables/cep/useTheme';
import CepWorkspaceLayout from '../layouts/CepWorkspaceLayout.vue';

describe('CEP Shared Adaptive Shell Foundation', () => {
  beforeEach(() => {
    localStorage.clear();
    document.documentElement.removeAttribute('data-theme');
    document.documentElement.className = '';
  });

  it('publishes exactly the five approved global destinations with stable hrefs', () => {
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

  it('manages shared theme switching and persists owner preference in localStorage', async () => {
    const { theme, initTheme, toggleTheme, setTheme } = useTheme();

    initTheme();
    expect(theme.value).toBe('dark');
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark');

    toggleTheme();
    expect(theme.value).toBe('light');
    expect(document.documentElement.getAttribute('data-theme')).toBe('light');
    expect(localStorage.getItem('cep-theme')).toBe('light');

    setTheme('dark');
    expect(theme.value).toBe('dark');
    expect(localStorage.getItem('cep-theme')).toBe('dark');
  });

  it('keeps Arabic content RTL while fixing the physical workspace grid axis LTR', () => {
    const wrapper = mount(CepWorkspaceLayout, {
      props: { activeDestination: 'today' },
      slots: {
        left: '<p>بنية فريدة</p>',
        default: '<h1>عنوان العمل الفريد</h1>',
        right: '<p>سياق فريد</p>',
      },
    });

    const grid = wrapper.find('.cep-workspace-grid');
    const left = wrapper.find('[data-cep-region="left"]');
    const center = wrapper.find('[data-cep-region="center"]');
    const right = wrapper.find('[data-cep-region="right"]');

    expect(wrapper.attributes('dir')).toBe('rtl');
    expect(grid.attributes('dir')).toBe('ltr');
    expect(left.attributes('dir')).toBe('rtl');
    expect(center.attributes('dir')).toBe('rtl');
    expect(right.attributes('dir')).toBe('rtl');
    expect(wrapper.find('.skip-link').attributes('href')).toBe('#main-content');
    expect(center.attributes('tabindex')).toBe('-1');
    expect(left.text()).toBe('بنية فريدة');
    expect(center.text()).toBe('عنوان العمل الفريد');
    expect(right.text()).toBe('سياق فريد');
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);
  });

  it('supports independent left/right panel collapsing and restores state', async () => {
    const wrapper = mount(CepWorkspaceLayout, {
      props: {
        activeDestination: 'today',
        initialLeftCollapsed: false,
        initialRightCollapsed: false,
      },
      slots: {
        top: '<span>أدوات العمل</span>',
        left: '<p>لوحة البنية</p>',
        default: '<h1>السطح الرئيسي</h1>',
        right: '<p>لوحة السياق</p>',
      },
    });

    expect(wrapper.find('[data-cep-region="left"]').exists()).toBe(true);
    expect(wrapper.find('[data-cep-region="right"]').exists()).toBe(true);

    const toggleButtons = wrapper.findAll('.cep-panel-toggle');
    expect(toggleButtons.length).toBeGreaterThanOrEqual(2);

    // Toggle Left Panel Collapse
    await toggleButtons[0].trigger('click');
    expect(wrapper.find('[data-cep-region="left"]').exists()).toBe(false);
    expect(localStorage.getItem('cep-left-collapsed')).toBe('true');

    // Toggle Right Panel Collapse
    await toggleButtons[1].trigger('click');
    expect(wrapper.find('[data-cep-region="right"]').exists()).toBe(false);
    expect(localStorage.getItem('cep-right-collapsed')).toBe('true');
  });

  it('enforces min/max width bounds, reset, and persistence for side panels', async () => {
    const wrapper = mount(CepWorkspaceLayout, {
      props: {
        activeDestination: 'today',
        initialLeftWidth: 300,
        initialRightWidth: 350,
      },
      slots: {
        left: '<p>لوحة البنية</p>',
        default: '<h1>السطح الرئيسي</h1>',
        right: '<p>لوحة السياق</p>',
      },
    });

    const leftHandle = wrapper.find('.cep-resize-handle--left');
    expect(leftHandle.exists()).toBe(true);
    expect(leftHandle.attributes('aria-valuenow')).toBe('300');

    // Test Keyboard resize on Left Handle
    await leftHandle.trigger('keydown', { key: 'ArrowRight' });
    expect(leftHandle.attributes('aria-valuenow')).toBe('310');
    expect(localStorage.getItem('cep-left-width')).toBe('310');

    // Test Keyboard Home key (min bound: 200)
    await leftHandle.trigger('keydown', { key: 'Home' });
    expect(leftHandle.attributes('aria-valuenow')).toBe('200');

    // Test Keyboard End key (max bound: 480)
    await leftHandle.trigger('keydown', { key: 'End' });
    expect(leftHandle.attributes('aria-valuenow')).toBe('480');

    // Test Double Click Reset (default: 280)
    await leftHandle.trigger('dblclick');
    expect(leftHandle.attributes('aria-valuenow')).toBe('280');
    expect(localStorage.getItem('cep-left-width')).toBe('280');
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
