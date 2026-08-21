import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import Workspace from '../pages/SimulationEnterprise/Workspace.vue';

const run = {
  id: '01900000-0000-7000-8000-000000000401',
  run_type: 'Scenario Run',
  lifecycle: 'RUNNING',
  definition_title_ar: 'تشغيل محاكاة داخلي',
  enterprise_id: '01900000-0000-7000-8000-000000000402',
  digital_twin_id: '01900000-0000-7000-8000-000000000403',
  digital_twin_revision_id: '01900000-0000-7000-8000-000000000404',
  baseline_id: '01900000-0000-7000-8000-000000000405',
  scenario_definition_id: '01900000-0000-7000-8000-000000000406',
  standalone_lab_definition_id: null,
  seed: 20260814,
  execution_policies: { mode: 'GUIDED' },
  runtime_state: { phase: 'RUNNING', provenance: 'SIMULATED' },
  input_digest: 'a'.repeat(64),
  provenance: 'SIMULATED',
  source_fixture: true,
  available_actions: ['operate', 'complete'],
  events: [],
  operations: [],
  snapshots: [],
  result_id: null,
};

function mountWorkspace() {
  return mount(Workspace, {
    props: {
      section: 'runs',
      navigation: [],
      enterprises: [],
      scenarios: [],
      labs: [],
      runs: [run],
      results: [],
      outcomes: ['NOT_EVALUATED'],
    },
  });
}

describe('Simulation Enterprise workspace assurance states', () => {
  beforeEach(() => vi.mocked(router.post).mockReset());

  it('submits the bounded in-run grammar and exposes pending and bounded error states', async () => {
    let visitOptions: Parameters<typeof router.post>[2] | undefined;
    vi.mocked(router.post).mockImplementation((_url, _data, options) => {
      visitOptions = options;
    });
    const wrapper = mountWorkspace();

    await wrapper.find('form.action-form').trigger('submit');

    expect(router.post).toHaveBeenCalledOnce();
    expect(vi.mocked(router.post).mock.calls[0][0]).toBe(`/simulation/runs/${run.id}/operations`);
    expect(vi.mocked(router.post).mock.calls[0][1]).toMatchObject({
      verb: 'SET_CONTROL_STATE',
      target: 'IDENTITY_MFA',
      value: false,
    });
    expect(wrapper.get('.workspace').attributes('aria-busy')).toBe('true');
    expect(wrapper.find('[role="status"]').exists()).toBe(true);

    visitOptions?.onError?.({ simulation: 'Operations can be applied only to a RUNNING Run.' });
    await nextTick();
    expect(wrapper.get('[role="alert"]').text()).toContain('RUNNING Run');

    visitOptions?.onFinish?.({} as never);
    await nextTick();
    expect(wrapper.get('.workspace').attributes('aria-busy')).toBe('false');
  });
});
