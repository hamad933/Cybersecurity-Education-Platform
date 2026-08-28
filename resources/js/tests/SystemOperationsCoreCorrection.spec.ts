import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';

import HealthSurface from '../pages/SystemOperations/components/health/HealthSurface.vue';
import ProcessingSurface from '../pages/SystemOperations/components/processing/ProcessingSurface.vue';
import ReleasesSurface from '../pages/SystemOperations/components/releases/ReleasesSurface.vue';
import StatusPill from '../pages/SystemOperations/components/StatusPill.vue';
import type { ProcessingRun, WorkspaceState } from '../pages/SystemOperations/types';

vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn(),
  },
  useForm: vi.fn(() => ({
    source: null,
    processing: false,
    errors: {},
    post: vi.fn(),
    reset: vi.fn(),
  })),
}));

describe('SystemOperationsCoreCorrection Vitest Suite', () => {
  describe('Processing lifecycle, attempt, liveness, and retry semantics', () => {
    const mockRun: ProcessingRun = {
      id: 'proc-run-001',
      type: 'content.ingest',
      input_digest: 'a'.repeat(64),
      status: 'failed',
      attempt_count: 2,
      max_attempts: 3,
      worker_identifier: 'worker-node-alpha',
      started_at: '2026-08-28T20:00:00Z',
      completed_at: '2026-08-28T20:05:00Z',
      cancelled_at: null,
      error_category: 'WORKER_TIMEOUT',
      safe_error_message: 'Worker execution exceeded time lease limit.',
      leased_until: '2026-08-28T20:04:00Z',
      next_attempt_at: '2026-08-28T20:10:00Z',
      created_at: '2026-08-28T19:59:00Z',
    };

    const mockState: WorkspaceState = {
      processing: {
        counts: { pending: 1, running: 0, completed: 5, failed: 1 },
        runs: [mockRun],
      },
      outbox: {
        counts: { pending: 0, dispatched: 10 },
        messages: [],
      },
      policy: {
        cancellation: 'PENDING_OR_RUNNING_ONLY',
        retry_route_available: true,
        knowledge_decisions: false,
      },
    };

    it('renders processing surface with run details and enables retry for failed run', async () => {
      const wrapper = mount(ProcessingSurface, {
        props: { state: mockState },
      });

      expect(wrapper.text()).toContain('proc-run-001');
      expect(wrapper.text()).toContain('content.ingest');
      expect(wrapper.text()).toContain('worker-node-alpha');
      expect(wrapper.text()).toContain('2 / 3');

      // Retry button should be visible for failed run
      const retryBtn = wrapper.find('.btn-inspect + .btn-inspect');
      expect(retryBtn.exists()).toBe(true);
      expect(retryBtn.text()).toContain('إعادة التشغيل');

      await retryBtn.trigger('click');
      expect(router.post).toHaveBeenCalledWith(
        '/system/processing/runs/proc-run-001/retry',
        {},
        { preserveScroll: true },
      );
    });

    it('emits openDeep with liveness lease and next attempt timestamps on inspect', async () => {
      const wrapper = mount(ProcessingSurface, {
        props: { state: mockState },
      });

      const inspectBtn = wrapper.find('.btn-inspect');
      await inspectBtn.trigger('click');

      expect(wrapper.emitted('openDeep')).toBeTruthy();
      const [title, sections] = wrapper.emitted('openDeep')![0] as [string, Array<{ label: string; value: unknown }>];
      expect(title).toContain('content.ingest');

      const labels = sections.map((s) => s.label);
      expect(labels).toContain('تاريخ المحاولة التالية (Next Attempt)');
      expect(labels).toContain('حجز العامل (Leased Until)');
      expect(labels).toContain('رسالة الخطأ الآمنة (Safe Error Message)');

      const leaseSection = sections.find((s) => s.label === 'حجز العامل (Leased Until)');
      expect(leaseSection?.value).not.toBe('—');
    });

    it('allows cancellation only for pending or running runs', async () => {
      const runningRun: ProcessingRun = {
        ...mockRun,
        id: 'proc-run-002',
        status: 'running',
      };

      const completedRun: ProcessingRun = {
        ...mockRun,
        id: 'proc-run-003',
        status: 'completed',
      };

      const wrapper = mount(ProcessingSurface, {
        props: {
          state: {
            processing: {
              counts: { running: 1, completed: 1 },
              runs: [runningRun, completedRun],
            },
          },
        },
      });

      const cards = wrapper.findAll('.trace-card');
      expect(cards).toHaveLength(2);

      // First card is running -> has cancel button
      expect(cards[0].find('.btn-cancel').exists()).toBe(true);
      await cards[0].find('.btn-cancel').trigger('click');
      expect(router.post).toHaveBeenCalledWith(
        '/system/processing/runs/proc-run-002/cancel',
        {},
        { preserveScroll: true },
      );

      // Second card is completed -> no cancel button
      expect(cards[1].find('.btn-cancel').exists()).toBe(false);
    });
  });

  describe('Health and Release readiness rendering', () => {
    it('renders health surface with foundation checks and release status', () => {
      const state: WorkspaceState = {
        foundation: {
          checks: { database: 'ok', queue: 'ok', storage: 'ok' },
          healthy: true,
          failed_checks: [],
        },
        release_gate: {
          ready: true,
          checks: {
            environment: { status: 'PASS', detail: 'Environment ok.' },
            database: { status: 'PASS', detail: 'PostgreSQL ok.' },
            evidence_acceptance: { status: 'PASS', detail: 'All evidence records accepted.' },
          },
        },
      };

      const wrapper = mount(HealthSurface, {
        props: { state, selectedSubsystem: 'releases' },
      });

      expect(wrapper.text()).toContain('المكوّنات الأساسية اجتازت فحوص الصحة');
      expect(wrapper.text()).toContain('اجتياز كامل لفحوص الجاهزية');
    });

    it('renders releases surface checklist items correctly', () => {
      const state: WorkspaceState = {
        readiness: {
          ready: false,
          checks: {
            database: { status: 'PASS', detail: 'Database connection ok.' },
            evidence_acceptance: { status: 'WARN', detail: 'Pending evidence records require Owner acceptance.' },
          },
        },
      };

      const wrapper = mount(ReleasesSurface, {
        props: { state },
      });

      expect(wrapper.text()).toContain('evidence_acceptance');
      expect(wrapper.text()).toContain('Pending evidence records require Owner acceptance.');
  describe('StatusPill normalization', () => {
    it('normalizes pass, valid_chain, degraded, and warn statuses accurately', () => {
      const wrapperOk = mount(StatusPill, { props: { status: 'VALID_CHAIN' } });
      expect(wrapperOk.classes()).toContain('state-pill--ok');

      const wrapperPass = mount(StatusPill, { props: { status: 'PASS' } });
      expect(wrapperPass.classes()).toContain('state-pill--ok');

      const wrapperFail = mount(StatusPill, { props: { status: 'FAIL' } });
      expect(wrapperFail.classes()).toContain('state-pill--danger');

      const wrapperDegraded = mount(StatusPill, { props: { status: 'DEGRADED' } });
      expect(wrapperDegraded.classes()).toContain('state-pill--danger');

      const wrapperWarn = mount(StatusPill, { props: { status: 'WARN' } });
      expect(wrapperWarn.classes()).toContain('state-pill--warning');
    });
  });
});

