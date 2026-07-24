import { router } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it, vi } from 'vitest';

import AuthenticationInvestigation from '../pages/Vs003/AuthenticationInvestigation.vue';

const marker = `<img src=x onerror="document.documentElement.dataset.vs003Probe='executed'">`;
const actorRunId = '01900000-0000-7000-8000-000000000301';
const proposalId = '01900000-0000-7000-8000-000000000302';
const controlId = '01900000-0000-7000-8000-000000000303';

function props() {
  return {
    cases: ['VS3-BENIGN', 'VS3-SUSPICIOUS', 'VS3-INCIDENT', 'VS3-INSUFFICIENT', 'VS3-UNSUPPORTED'],
    outcomes: [
      'BENIGN_EXPLAINED',
      'SUSPICIOUS',
      'INCIDENT_CONFIRMED',
      'INSUFFICIENT_TELEMETRY',
      'UNSUPPORTED_STATE',
    ],
    telemetryHealthValues: ['HEALTHY', 'DEGRADED', 'UNSUPPORTED'],
    alternativeHypotheses: [
      'legitimate_user_error',
      'legitimate_success_after_failures',
      'telemetry_gap',
    ],
    evidenceOrigin: 'SIMULATED',
    baseline: 'WINDOWS-AUTH-TELEMETRY-IR-2026-07-24-V1',
    requestKeys: {
      run: 'vs003:ui:run:01900000-0000-7000-8000-000000000310',
      verification: 'unused-server-context',
    },
    workspace: {
      simulation: {
        runs: [
          {
            id: actorRunId,
            case_id: 'VS3-INCIDENT',
            outcome: 'INCIDENT_CONFIRMED',
            status: 'completed',
            trace_digest: 'a'.repeat(64),
            completed_at: '2026-07-24T08:05:00Z',
            alert: { state: 'OPEN', severity: 'HIGH' },
            triage: {
              id: '01900000-0000-7000-8000-000000000311',
              scenario_run_id: actorRunId,
              outcome: 'INCIDENT_CONFIRMED',
              severity: 'HIGH',
              scope: 'one_synthetic_device',
              confidence: 'MEDIUM',
              rationale: 'Bounded synthetic rationale.',
            },
            trace: {
              run_id: actorRunId,
              outcome: 'INCIDENT_CONFIRMED',
              alert_state: 'OPEN',
              severity: 'HIGH',
              scope: 'one_synthetic_device',
              confidence: 'MEDIUM',
              telemetry_health: 'DEGRADED',
              alternative_hypotheses: ['legitimate_user_error', 'telemetry_gap'],
              missing_data: [],
              timeline_digest: 'a'.repeat(64),
              quality: {
                duplicate_count: 0,
                late_count: 1,
                missing_count: 0,
                contradictory_count: 0,
                unsupported_count: 0,
                ordering: 'occurred_at_then_event_id_ascending_UTC',
              },
              events: [
                {
                  id: 'EVT-004-LATE',
                  event_id: 4625,
                  occurred_at: '2026-07-24T07:50:00Z',
                  computer: 'WS-07',
                  account_sid: 'S-1-5-21-SIM-ADMIN',
                  logon_type: 3,
                  source_address: '10.20.30.40',
                  duplicate_of: null,
                  late: true,
                  contradicts: null,
                },
              ],
            },
          },
        ],
      },
      evidence: {
        evidence: [
          {
            id: '01900000-0000-7000-8000-000000000320',
            run_id: actorRunId,
            case_id: 'VS3-INCIDENT',
            result: 'INCIDENT_CONFIRMED',
            origin: 'SIMULATED',
            trace_digest: 'a'.repeat(64),
            locked: true,
          },
        ],
        custody: [
          {
            id: '01900000-0000-7000-8000-000000000321',
            scenario_run_id: actorRunId,
            origin: 'SIMULATED',
            source_digest: 'a'.repeat(64),
            copy_kind: 'PRESERVED_ORIGINAL',
            storage_reference: `simulated://vs003/${actorRunId}/preserved-original`,
          },
        ],
        proposals: [
          {
            id: proposalId,
            scenario_run_id: actorRunId,
            state: 'APPROVED',
            expected_effect: 'Bounded synthetic effect.',
            risk: 'Bounded synthetic risk.',
            rollback_condition: 'Bounded synthetic rollback.',
          },
        ],
        controls: [
          {
            id: controlId,
            control_id: 'CTRL-VS003-AUTH-PATH',
            revision: 1,
            state: 'published',
            remediates_run_id: actorRunId,
            digest: 'b'.repeat(64),
          },
        ],
        verification_replays: [
          {
            id: '01900000-0000-7000-8000-000000000330',
            original_run_id: actorRunId,
            verification_run_id: '01900000-0000-7000-8000-000000000331',
            control_revision_id: controlId,
            passed: true,
            digest: 'c'.repeat(64),
          },
        ],
      },
      learning: {
        practice: {
          id: '01900000-0000-7000-8000-000000000340',
          revision: 1,
          definition: { case_id: 'VS3-SUSPICIOUS' },
        },
        attempts: [
          {
            id: '01900000-0000-7000-8000-000000000341',
            outcome: 'incorrect',
            failure_class: 'wrong_triage',
          },
        ],
        mastery: { status: 'IN_PROGRESS', evaluation_digest: 'd'.repeat(64) },
        review_triggers: [
          {
            id: '01900000-0000-7000-8000-000000000342',
            failure_class: 'wrong_triage',
            status: 'scheduled',
            schedule_reason: marker,
          },
        ],
      },
    },
  };
}

function mountPage() {
  return mount(AuthenticationInvestigation, {
    props: props(),
    global: {
      stubs: {
        AppShell: { template: '<div data-test="app-shell"><slot /></div>' },
      },
    },
  });
}

describe('VS-003 Arabic authentication investigation workflow', () => {
  it('renders Arabic RTL while isolating identifiers, timestamps, and digests as LTR', () => {
    const wrapper = mountPage();

    expect(wrapper.find('article[dir="rtl"]').exists()).toBe(true);
    expect(wrapper.findAll('[dir="ltr"]').length).toBeGreaterThan(8);
    expect(wrapper.text()).toContain('تحقيق شذوذ المصادقة');
    expect(wrapper.text()).toContain('SIMULATED');
    expect(wrapper.text()).toContain('UTC');
    expect(wrapper.text()).toContain('late_count=1');
    expect(wrapper.text()).toContain('legitimate_user_error');
    expect(wrapper.text()).toContain('PRESERVED_ORIGINAL');
    expect(wrapper.text()).toContain('LOCKED');
    expect(wrapper.text()).toContain('Verification PASS');
  });

  it('renders untrusted review text inertly and has no active-content rendering path', () => {
    delete document.documentElement.dataset.vs003Probe;
    const wrapper = mountPage();
    const source = readFileSync(
      resolve('resources/js/pages/Vs003/AuthenticationInvestigation.vue'),
      'utf8',
    );

    expect(wrapper.find('img').exists()).toBe(false);
    expect(wrapper.text()).toContain(marker);
    expect(wrapper.html()).toContain('&lt;img');
    expect(document.documentElement.dataset.vs003Probe).toBeUndefined();
    expect(source).not.toContain('v-html');
    expect(source).not.toMatch(/innerHTML|outerHTML|document\.write/);
  });

  it('exposes keyboard focus styles, bounded timeline scrolling, and mobile-safe primary actions', () => {
    const wrapper = mountPage();
    const source = readFileSync(
      resolve('resources/js/pages/Vs003/AuthenticationInvestigation.vue'),
      'utf8',
    );

    expect(wrapper.findAll('button.focus-ring').length).toBeGreaterThanOrEqual(6);
    expect(source).toContain('max-h-80 overflow-auto');
    expect(source).toContain('min-w-[760px]');
    expect(source).toContain('w-full rounded-xl');
    expect(source).toContain('disabled:opacity-50');
    expect(wrapper.find('[role="status"]').exists()).toBe(false);
  });

  it('uses one deterministic semantic verification key per proposal', async () => {
    vi.mocked(router.post).mockClear();
    const wrapper = mountPage();
    const verify = wrapper
      .findAll('button')
      .find((button) => button.text().includes('نشر مراجعة ضبط وإعادة تحقق'));

    expect(verify).toBeDefined();
    await verify?.trigger('click');
    expect(router.post).toHaveBeenCalledWith(
      `/vs003/containment/${proposalId}/verify`,
      {
        original_run_id: actorRunId,
        idempotency_key: `vs003:verify:${proposalId}`,
      },
      { preserveScroll: true },
    );
  });
});
