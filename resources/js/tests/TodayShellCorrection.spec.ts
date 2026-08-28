import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import TodayContinueSession from '../components/today/TodayContinueSession.vue';

describe('TodayShellCorrection', () => {
  it('renders UNAVAILABLE empty state correctly', () => {
    const wrapper = mount(TodayContinueSession, {
      props: {
        session: {
          status: 'UNAVAILABLE',
          data: null,
        }
      }
    });

    expect(wrapper.find('[data-testid="today-session-unavailable"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('حالة الجلسة غير متوفرة');
  });

  it('renders AVAILABLE active state correctly', () => {
    const wrapper = mount(TodayContinueSession, {
      props: {
        session: {
          status: 'AVAILABLE',
          data: {
            title: 'Test Session',
            domainLabel: 'Vs001',
            href: '/test',
            currentStep: 'Step 1'
          }
        }
      },
      global: {
        stubs: {
          Link: {
            template: '<a><slot /></a>'
          },
          TechnicalText: {
            template: '<span>{{ value }}</span>',
            props: ['value']
          }
        }
      }
    });

    expect(wrapper.find('[data-testid="today-session-active"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Test Session');
    expect(wrapper.text()).toContain('Vs001');
  });

  it('renders EMPTY state correctly', () => {
    const wrapper = mount(TodayContinueSession, {
      props: {
        session: {
          status: 'EMPTY',
          data: null
        }
      }
    });

    expect(wrapper.find('[data-testid="today-session-empty"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('لا توجد جلسة عمل نشطة حاليًا');
  });
});
