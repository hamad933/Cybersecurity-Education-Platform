import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import CepWorkspaceLayout from '../layouts/CepWorkspaceLayout.vue';
import TodayIndex from '../pages/Today/Index.vue';
import TodayAttentionItems from '../components/today/TodayAttentionItems.vue';
import TodayContinueSession from '../components/today/TodayContinueSession.vue';
import TodayRecommendation from '../components/today/TodayRecommendation.vue';
import TodayProgressProjection from '../components/today/TodayProgressProjection.vue';
import TodayRecentContext from '../components/today/TodayRecentContext.vue';

// Define a simple stub for CepEmptyState that renders the default slot
const stubEmptyState = {
  props: ['title', 'description'],
  template: '<div class="stub-empty-state">{{ title }} {{ description }}<slot></slot></div>',
};

const globalStubs = {
  Link: { template: '<a><slot></slot></a>' },
  TechnicalText: { template: '<span>{{ $attrs.value }}</span>' },
  CepEmptyState: stubEmptyState,
};

describe('Today Orchestration Level Tests (W01 Contract Lineage)', () => {

  describe('Level 1: TodayContinueSession', () => {
    it('renders AVAILABLE state with data correctly', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          node: {
            status: 'AVAILABLE',
            data: {
              id: 'sess-1',
              title: 'جلسة محاكاة الشبكة',
              domain: 'simulation',
              domainLabel: 'المحاكاة والمؤسسات',
              href: '/simulation/1',
              actionLabel: 'استئناف',
            },
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-session-active"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('جلسة محاكاة الشبكة');
      expect(wrapper.text()).toContain('المحاكاة والمؤسسات');
      expect(wrapper.text()).toContain('استئناف');
    });

    it('renders UNAVAILABLE state correctly', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          node: {
            status: 'UNAVAILABLE',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-session-unavailable"]').exists()).toBe(true);
    });

    it('renders EMPTY state correctly', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          node: {
            status: 'EMPTY',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-session-empty"]').exists()).toBe(true);
    });
  });

  describe('Level 2: TodayRecommendation (replaces NextAction/Rationale)', () => {
    it('renders AVAILABLE state with data correctly and includes rationale metadata', () => {
      const wrapper = mount(TodayRecommendation, {
        props: {
          node: {
            status: 'AVAILABLE',
            data: {
              recommendationId: 'rec-001',
              id: 'act-1',
              title: 'مراجعة أساسيات التشفير',
              domain: 'knowledge',
              domainLabel: 'المعرفة والتعلّم',
              href: '/knowledge/1',
              description: 'وحدة تمهيدية',
              rationaleText: 'مطلوبة لفهم العمليات التالية',
              targetCompetency: 'SEC-CRYPTO',
              unlockedCapabilities: ['اختبار التشفير المتقدم'],
            },
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-recommendation-active"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('مراجعة أساسيات التشفير');
      expect(wrapper.text()).toContain('المعرفة والتعلّم');
      expect(wrapper.text()).toContain('مطلوبة لفهم العمليات التالية');
      expect(wrapper.text()).toContain('اختبار التشفير المتقدم');
      expect(wrapper.find('.today-rationale-section').exists()).toBe(true);
    });

    it('renders UNAVAILABLE state correctly', () => {
      const wrapper = mount(TodayRecommendation, {
        props: {
          node: {
            status: 'UNAVAILABLE',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-recommendation-unavailable"]').exists()).toBe(true);
    });

    it('renders EMPTY state correctly', () => {
      const wrapper = mount(TodayRecommendation, {
        props: {
          node: {
            status: 'EMPTY',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-recommendation-empty"]').exists()).toBe(true);
    });

    it('renders ERROR state correctly and exposes diagnostic id through slot forwarding', () => {
      const wrapper = mount(TodayRecommendation, {
        props: {
          node: {
            status: 'ERROR',
            data: null,
            message: 'خطأ معالجة',
            diagnosticId: 'ERR-XYZ',
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-error-state"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('ERR-XYZ');
    });

    it('renders STALE state correctly and consumes both observedAt and freshUntil', () => {
      const wrapper = mount(TodayRecommendation, {
        props: {
          node: {
            status: 'STALE',
            data: null,
            message: 'البيانات قديمة',
            observedAt: '2026-08-01 10:00',
            freshUntil: '2026-08-01 11:00',
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid=\"today-stale-empty-state\"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('2026-08-01 10:00');
      expect(wrapper.text()).toContain('2026-08-01 11:00');
    });
  });


  describe('Level 3: TodayAttentionItems', () => {
    it('renders AVAILABLE state with items correctly', () => {
      const wrapper = mount(TodayAttentionItems, {
        props: {
          items: {
            status: 'AVAILABLE',
            data: [
              {
                id: 'att-1',
                title: 'انتهاء صلاحية شهادة التقييم',
                domain: 'progress',
                domainLabel: 'التقدم والأدلة',
                href: '/progress/eval/1',
                severity: 'urgent',
                reason: 'يتطلب إعادة التحقق من الدليل العملي.',
              },
            ],
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-attention-list"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('انتهاء صلاحية شهادة التقييم');
      expect(wrapper.text()).toContain('عاجل');
    });

    it('renders UNAVAILABLE state correctly', () => {
      const wrapper = mount(TodayAttentionItems, {
        props: {
          items: {
            status: 'UNAVAILABLE',
            data: [],
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-attention-unavailable"]').exists()).toBe(true);
    });

    it('renders EMPTY state correctly', () => {
      const wrapper = mount(TodayAttentionItems, {
        props: {
          items: {
            status: 'EMPTY',
            data: [],
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-attention-empty"]').exists()).toBe(true);
    });
    it('renders STALE state correctly and consumes both observedAt and freshUntil', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          node: {
            status: 'STALE',
            data: null,
            message: 'قديم',
            observedAt: '2026-08-01 10:00',
            freshUntil: '2026-08-01 11:00',
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-stale-empty-state"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('2026-08-01 10:00');
      expect(wrapper.text()).toContain('2026-08-01 11:00');
    });

  });

  describe('Level 4: TodayRecentContext', () => {
    it('renders AVAILABLE state with items correctly', () => {
      const wrapper = mount(TodayRecentContext, {
        props: {
          items: {
            status: 'AVAILABLE',
            data: [
              {
                id: 'rec-1',
                title: 'إتمام جلسة اختبار الاختراق',
                domain: 'simulation',
                domainLabel: 'المحاكاة والمؤسسات',
                href: '/simulation/1',
                timestamp: '2026-08-28 14:00',
                summary: 'تم توثيق 3 أدلة على نجاح الاختراق المحاكي.',
              },
            ],
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-recent-list"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('إتمام جلسة اختبار الاختراق');
      expect(wrapper.text()).toContain('المحاكاة والمؤسسات');
    });

    it('renders UNAVAILABLE state correctly', () => {
      const wrapper = mount(TodayRecentContext, {
        props: {
          items: {
            status: 'UNAVAILABLE',
            data: [],
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-recent-unavailable"]').exists()).toBe(true);
    });

    it('renders EMPTY state correctly', () => {
      const wrapper = mount(TodayRecentContext, {
        props: {
          items: {
            status: 'EMPTY',
            data: [],
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-recent-empty"]').exists()).toBe(true);
    });
    it('renders STALE state correctly and consumes both observedAt and freshUntil', () => {
      const wrapper = mount(TodayRecentContext, {
        props: {
          items: {
            status: 'STALE',
            data: null,
            message: 'قديم',
            observedAt: '2026-08-01 10:00',
            freshUntil: '2026-08-01 11:00',
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-stale-empty-state"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('2026-08-01 10:00');
      expect(wrapper.text()).toContain('2026-08-01 11:00');
    });

  });

  describe('Level 5: TodayProgressProjection & Anti-Gamification', () => {
    it('renders AVAILABLE state without fake percentages and emphasizes Completion != Mastery', () => {
      const wrapper = mount(TodayProgressProjection, {
        props: {
          projection: {
            status: 'AVAILABLE',
            data: {
              milestoneTitle: 'المرحلة 1: المحقق الجنائي المبتدئ',
              verifiedCount: 4,
              totalCount: 6,
              statusSummary: 'تم إثبات 4 وحدات بأدلة قطعية من أصل 6.',
              targetHorizon: 'الربع الثالث 2026',
              evidenceRequirement: 'تقرير فحص الذاكرة العشوائية',
            },
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-projection-active"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('المرحلة 1: المحقق الجنائي المبتدئ');
      expect(wrapper.text()).toContain('4 / 6');
      expect(wrapper.text()).toContain('الإنجاز لا يعني الإتقان');
      expect(wrapper.text()).not.toContain('XP');
      expect(wrapper.text()).not.toContain('نقاط');
    });

    it('renders UNAVAILABLE state correctly', () => {
      const wrapper = mount(TodayProgressProjection, {
        props: {
          projection: {
            status: 'UNAVAILABLE',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-projection-unavailable"]').exists()).toBe(true);
    });

    it('renders EMPTY state with anti-gamification rule note', () => {
      const wrapper = mount(TodayProgressProjection, {
        props: {
          projection: {
            status: 'EMPTY',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-projection-empty"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('الإنجاز لا يعني الإتقان');
    });
    it('renders STALE state correctly and consumes both observedAt and freshUntil', () => {
      const wrapper = mount(TodayAttentionItems, {
        props: {
          items: {
            status: 'STALE',
            data: null,
            message: 'قديم',
            observedAt: '2026-08-01 10:00',
            freshUntil: '2026-08-01 11:00',
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-stale-empty-state"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('2026-08-01 10:00');
      expect(wrapper.text()).toContain('2026-08-01 11:00');
    });

  });

  describe('Shell Assembly & Integrity (Today/Index.vue)', () => {
    it('ensures CepWorkspaceLayout enforces LTR physical grid isolation to prevent column inversion and handles correct math', async () => {
      const wrapper = mount(CepWorkspaceLayout, {
        props: {
          activeDestination: 'today',
          initialLeftWidth: 300,
          initialRightWidth: 350,
        },
        global: {
          stubs: {
            CepGlobalNavigation: true,
            CepActionBar: true,
            CepContextPanel: true,
            CepTemporaryWorkspace: true,
          }
        },
        slots: {
          left: '<div class="left-content">Left</div>',
          right: '<div class="right-content">Right</div>',
        }
      });

      // Layout root should be RTL, but grid should be LTR to preserve LEFT -> CENTER -> RIGHT physical arrangement
      const layoutRoot = wrapper.find('.cep-app-shell');
      expect(layoutRoot.attributes('dir')).toBe('rtl');

      const grid = wrapper.find('.cep-workspace-grid');
      expect(grid.attributes('dir')).toBe('ltr');

      // Test resize math and keys
      const leftHandle = wrapper.find('.cep-resize-handle--left');
      
      // pressing ArrowRight on LEFT handle should increase width
      await leftHandle.trigger('keydown', { key: 'ArrowRight' });
      expect(grid.attributes('style')).toContain('--cep-left-panel-width: 310px');
      
      const rightHandle = wrapper.find('.cep-resize-handle--right');

      // pressing ArrowLeft on RIGHT handle should increase width
      await rightHandle.trigger('keydown', { key: 'ArrowLeft' });
      expect(grid.attributes('style')).toContain('--cep-right-panel-width: 360px');
    });

    it('mounts Today workspace without duplicate generic directory cards', () => {
      const wrapper = mount(TodayIndex, {
        props: {
          orchestration: {
            registeredDomainEntries: 0,
            expectedDomainEntries: 4,
            continueSession: { status: 'UNAVAILABLE', data: null },
            recommendation: { status: 'UNAVAILABLE', data: null },
            attentionItems: { status: 'UNAVAILABLE', data: null },
            recentContext: { status: 'UNAVAILABLE', data: null },
            progressProjection: { status: 'UNAVAILABLE', data: null },
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.findComponent(TodayContinueSession).exists()).toBe(true);
      expect(wrapper.findComponent(TodayRecommendation).exists()).toBe(true);
      expect(wrapper.findComponent(TodayAttentionItems).exists()).toBe(true);
      expect(wrapper.findComponent(TodayRecentContext).exists()).toBe(true);
      expect(wrapper.findComponent(TodayProgressProjection).exists()).toBe(true);

      // Ensure no duplicated workspace handoff grid exists in Today
      expect(wrapper.find('#workspace-handoffs').exists()).toBe(false);
      expect(wrapper.find('.today-canonical-links').exists()).toBe(false);
      
      // Ensure left structure nav item #07 doesn't exist
      const links = wrapper.findAll('.cep-structure-nav__link');
      const hasHandoffLink = links.some(w => w.attributes('href') === '#workspace-handoffs');
      expect(hasHandoffLink).toBe(false);
    });
    it('renders STALE state correctly and consumes both observedAt and freshUntil', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          node: {
            status: 'STALE',
            data: null,
            message: 'قديم',
            observedAt: '2026-08-01 10:00',
            freshUntil: '2026-08-01 11:00',
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-stale-empty-state"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('2026-08-01 10:00');
      expect(wrapper.text()).toContain('2026-08-01 11:00');
    });

  });
});
