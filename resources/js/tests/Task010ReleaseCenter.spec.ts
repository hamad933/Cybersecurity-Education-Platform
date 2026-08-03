import { mount } from '@vue/test-utils';

import ReleaseCenter from '../pages/Release/Center.vue';

const props = {
  readiness: { ready: true, checks: { audit_chain: { status: 'PASS', detail: 'verified' } } },
  dailyQueue: [
    {
      kind: 'review',
      knowledge_unit_id: 'KU-D09-002',
      case_id: null,
      score: 100,
      reason_code: 'OPEN_FAILURE_REVIEW',
      reason: 'Failure-specific review is due.',
      source_reference: 'review_triggers',
    },
  ],
  query: '',
  searchResults: [],
  sourceImports: [],
  aiResults: [],
  backups: [],
  manualAiPolicy: { execution: 'MANUAL_ONLY', automatic_provider: false, automatic_publish: false },
};

describe('Task-010 release center', () => {
  it('renders explicit release gates and manual-only AI boundaries', () => {
    const wrapper = mount(ReleaseCenter, { props });
    expect(wrapper.text()).toContain('مركز التكامل والتشغيل المحلي');
    expect(wrapper.text()).toContain('MANUAL_ONLY');
    expect(wrapper.text()).toContain('OPEN_FAILURE_REVIEW');
    expect(wrapper.text()).toContain('SIMULATED');
    expect(wrapper.html()).not.toContain('v-html');
  });

  it('provides labels for uploads and bounded restore staging', () => {
    const wrapper = mount(ReleaseCenter, { props });
    expect(wrapper.find('input[accept=".txt,.md,.json,.pdf"]').exists()).toBe(true);
    expect(wrapper.findAll('input[accept=".zip"]').length).toBeGreaterThanOrEqual(3);
    expect(wrapper.text()).toContain('*_restore_drill');
  });
});
