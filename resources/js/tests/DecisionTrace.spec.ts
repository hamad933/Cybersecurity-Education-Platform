import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import DecisionTrace from '../components/DecisionTrace.vue';

describe('DecisionTrace', () => {
  it('isolates technical trace data in LTR and exposes deterministic fields', () => {
    const wrapper = mount(DecisionTrace, {
      props: {
        trace: {
          final_outcome: 'DENY',
          decisive_rule_id: 'RULE-ACE-DENY',
          remaining_unresolved_mask: '0x00000001',
          output_digest: 'a'.repeat(64),
          evidence_origin: 'SIMULATED',
          ordered_ace_steps: [
            {
              index: 0,
              type: 'ACCESS_DENIED',
              trustee_sid: 'S-1-5-21-1000',
              reason: 'decisive_deny',
              mask_before: '0x00000001',
              mask_effect: '0x00000001',
              mask_after: '0x00000001',
            },
          ],
        },
      },
    });

    expect(wrapper.find('.direction-ltr').exists()).toBe(true);
    expect(wrapper.text()).toContain('RULE-ACE-DENY');
    expect(wrapper.text()).toContain('SIMULATED');
    expect(wrapper.text()).toContain('S-1-5-21-1000');
  });
});
