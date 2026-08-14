import { mount } from '@vue/test-utils';

import Workspace from '../pages/SystemOperations/Workspace.vue';

describe('System & Operations workspace', () => {
  it('renders real health inputs without invented uptime or deployment claims', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'health',
        state: {
          foundation: { checks: { database: 'ok', queue: 'failed' }, healthy: false, failed_checks: ['queue'] },
          processing: { counts: { running: 2, failed: 1 } },
          outbox: { counts: { failed: 3 } },
          packages: { counts: { rejected: 1 }, records: [] },
          release_gate: { ready: false, checks: {} },
        },
      },
    });

    expect(wrapper.text()).toContain('توجد فحوص أساسية تتطلب الانتباه');
    expect(wrapper.text()).toContain('رسائل Outbox فاشلة');
    expect(wrapper.text()).not.toContain('99.9%');
    expect(wrapper.text()).not.toContain('تم النشر');
  });

  it('keeps Manual AI explicitly manual-only', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'ai-bridge',
        state: {
          policy: {
            execution: 'MANUAL_ONLY', automatic_provider_enabled: false, automatic_publish: false,
            polling: false, embeddings: false,
          },
          prompts: [], results: [], decisions: [],
        },
      },
    });

    expect(wrapper.text()).toContain('MANUAL_ONLY');
    expect(wrapper.text()).toContain('لا API provider');
    expect(wrapper.text()).toContain('لا polling');
  });

  it('keeps restore staging behind closed progressive disclosure', () => {
    const wrapper = mount(Workspace, {
      props: {
        surface: 'backups',
        state: {
          backups: [], restores: [],
          safety: { web_restore_mode: 'STAGE_AND_VERIFY_ONLY', activation_route_available: false },
        },
      },
    });

    const restoreDisclosure = wrapper.find('details.danger-zone');
    expect(restoreDisclosure.exists()).toBe(true);
    expect(restoreDisclosure.attributes('open')).toBeUndefined();
    expect(wrapper.text()).toContain('لا يوجد تفعيل Restore عبر HTTP');
  });
});
