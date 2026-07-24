import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import LessonReader from '../pages/Vs002/LessonReader.vue';
import MicroPractice from '../pages/Vs002/MicroPractice.vue';
import WebDecisionTrace from '../components/WebDecisionTrace.vue';

const marker = `<img src=x onerror="document.documentElement.dataset.xssProbe='executed'">`;

describe('VS-002 safe rendering and structured workspaces', () => {
  it('renders the stored active-content marker as inert text', () => {
    delete document.documentElement.dataset.xssProbe;
    const wrapper = mount(LessonReader, {
      props: {
        lesson: {
          revision: 1,
          blocks: [{ type: 'code', body: marker }],
          authority_baseline_id: 'WEB-API-AUTHORITY-2026-07-22-V1',
          content_digest: 'a'.repeat(64),
        },
      },
    });

    expect(wrapper.find('img').exists()).toBe(false);
    expect(wrapper.text()).toContain(marker);
    expect(document.documentElement.dataset.xssProbe).toBeUndefined();
    expect(wrapper.html()).not.toContain('v-html');
  });

  it('renders all nine structured practice fields with a bounded rationale', () => {
    const wrapper = mount(MicroPractice, {
      props: {
        practice: {
          definition: { prompt_ar: 'حلل الطلب', case_id: 'CASE-WEB-002', answer_key_version: 1 },
        },
        latestAttempt: null,
      },
    });

    expect(wrapper.findAll('select')).toHaveLength(7);
    expect(wrapper.findAll('input')).toHaveLength(1);
    expect(wrapper.find('textarea').attributes('maxlength')).toBe('1000');
    expect(wrapper.find('textarea').attributes('minlength')).toBe('12');
  });

  it('shows bounded trace provenance without secret rendering', () => {
    const wrapper = mount(WebDecisionTrace, {
      props: {
        trace: {
          request_id: 'req-safe',
          correlation_id: 'corr-safe',
          decision: 'DENY',
          actor_id: 'SIM-BOB',
          target_resource_id: 'CF-ALICE-001',
          authentication_result: 'AUTHENTICATED',
          decisive_rule_id: 'WEB-RULE-CROSS-OWNER-DENY',
          response_status: 403,
          response_shape_id: 'CASEFILE-SAFE-V1',
          redaction_result: {
            included_fields: ['id'],
            excluded_fields: ['session_token'],
            secrets_stored: false,
          },
          trace_digest: 'b'.repeat(64),
          trust_boundary_steps: [
            { boundary_id: 'TB-WEB-005', boundary: 'authorization_policy', result: 'deny' },
          ],
        },
      },
    });
    expect(wrapper.text()).toContain('SIMULATED');
    expect(wrapper.text()).toContain('secrets=false');
    expect(wrapper.text()).not.toContain('Bearer ');
  });
});
