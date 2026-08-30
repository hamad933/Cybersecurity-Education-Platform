import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import TodayContinueSession from '../components/today/TodayContinueSession.vue';
import TodayNextAction from '../components/today/TodayNextAction.vue';
import TodayRationale from '../components/today/TodayRationale.vue';
import TodayAttentionItems from '../components/today/TodayAttentionItems.vue';
import TodayRecentContext from '../components/today/TodayRecentContext.vue';
import TodayProgressProjection from '../components/today/TodayProgressProjection.vue';
import TodayIndex from '../pages/Today/Index.vue';
import CepWorkspaceLayout from '../layouts/CepWorkspaceLayout.vue';

const globalStubs = {
  Link: {
    template: '<a :href="href"><slot /></a>',
    props: ['href'],
  },
  TechnicalText: {
    template: '<span>{{ value }}</span>',
    props: ['value'],
  },
  Head: {
    template: '<head><slot /></head>',
  },
  CepWorkspaceLayout: {
    template:
      '<div class="mock-workspace-layout"><slot name="left" /><slot /><slot name="right" /><slot name="bottom" /></div>',
  },
  CepCommandBeacon: {
    template: '<div class="mock-command-beacon"><slot /></div>',
  },
  CepEmptyState: {
    template:
      '<div class="mock-empty-state"><div class="title">{{ title }}</div><div class="description">{{ description }}</div></div>',
    props: ['title', 'description'],
  },
};

describe('TodayShellCorrection — Orchestration Components and States', () => {
  describe('Level 1: TodayContinueSession', () => {
    it('renders AVAILABLE active state correctly', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          session: {
            status: 'AVAILABLE',
            data: {
              id: 'sess-1',
              title: 'اختبار محاكاة الهجوم',
              domain: 'simulation',
              domainLabel: 'المحاكاة والمؤسسات',
              href: '/simulation/labs/1',
              currentStep: 'الخطوة 2: تحليل الثغرة',
              actionLabel: 'استئناف الجلسة الآن',
            },
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-session-active"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('اختبار محاكاة الهجوم');
      expect(wrapper.text()).toContain('المحاكاة والمؤسسات');
      expect(wrapper.text()).toContain('الخطوة 2: تحليل الثغرة');
    });

    it('renders UNAVAILABLE state correctly', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          session: {
            status: 'UNAVAILABLE',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-session-unavailable"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('حالة الجلسة غير متوفرة');
    });

    it('renders EMPTY state correctly', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          session: {
            status: 'EMPTY',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-session-empty"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('لا توجد جلسة عمل نشطة حاليًا');
    });
  });


    it('renders ERROR state with explicit provider message', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          session: {
            status: 'ERROR',
            data: null,
            message: 'Timeout contacting simulation service.',
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-error-state"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('حدث خطأ في جلب البيانات');
      expect(wrapper.text()).toContain('Timeout contacting simulation service.');
    });

    it('renders STALE state correctly when data is present', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          session: {
            status: 'STALE',
            data: {
              id: 'sess-stale',
              title: 'اختبار محاكاة قديم',
              domain: 'simulation',
              domainLabel: 'المحاكاة',
              href: '/simulation/labs/1',
            },
          },
        },
        global: { stubs: globalStubs },
      });

      // Should show the data block
      expect(wrapper.find('[data-testid="today-session-active"]').exists()).toBe(true);
      // And the stale badge
      expect(wrapper.find('[data-testid="today-stale-badge"]').exists()).toBe(true);
    });

    it('renders explicit STALE empty state when no data is present', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          session: {
            status: 'STALE',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-stale-empty-state"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('البيانات غير محدّثة (قديمة)');
    });


    it('renders STALE state without live pulse wording', () => {
      const wrapper = mount(TodayContinueSession, {
        props: {
          session: {
            status: 'STALE',
            data: { id: 's1', title: 'T1', domain: 'd', domainLabel: 'd', href: '/h' }
          }
        },
        global: { stubs: globalStubs }
      });
      expect(wrapper.text()).not.toContain('جلسة نشطة قيد التشغيل');
      expect(wrapper.text()).toContain('آخر جلسة مرصودة');
    });

  describe('Level 2: TodayNextAction', () => {
    it('renders AVAILABLE state correctly', () => {
      const wrapper = mount(TodayNextAction, {
        props: {
          action: {
            status: 'AVAILABLE',
            data: {
              id: 'act-1',
              title: 'مراجعة معايير التشفير المتماثل',
              domain: 'knowledge',
              domainLabel: 'المعرفة والتعلّم',
              href: '/knowledge/crypto',
              description: 'إتمام الدرس النظري قبل الانتقال للمختبر التطبيقي.',
              timeCommitment: '20 دقيقة',
              difficulty: 'متوسط',
              actionLabel: 'بدء الإجراء الموصى به',
            },
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-next-action-active"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('مراجعة معايير التشفير المتماثل');
      expect(wrapper.text()).toContain('المعرفة والتعلّم');
    });

    it('renders UNAVAILABLE state correctly', () => {
      const wrapper = mount(TodayNextAction, {
        props: {
          action: {
            status: 'UNAVAILABLE',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-next-action-unavailable"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('التوصيات غير متوفرة');
    });

    it('renders EMPTY state correctly', () => {
      const wrapper = mount(TodayNextAction, {
        props: {
          action: {
            status: 'EMPTY',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-next-action-empty"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('لا توجد توصية مجدولة حاليًا');
    });
  });

  describe('Level 3: TodayRationale', () => {
    it('renders AVAILABLE state correctly', () => {
      const wrapper = mount(TodayRationale, {
        props: {
          rationale: {
            status: 'AVAILABLE',
            data: {
              id: 'rat-1',
              text: 'هذا الإجراء يرسخ متطلب الإتقان في النطاق الدفاعي الأول.',
              targetCompetency: 'SEC-DEF-101',
              unlockedCapabilities: ['التحليل المتقدم للسجلات'],
              prerequisiteChain: ['الأساسيات الأمنية'],
            },
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-rationale-active"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('SEC-DEF-101');
      expect(wrapper.text()).toContain('التحليل المتقدم للسجلات');
    });

    it('renders UNAVAILABLE state correctly', () => {
      const wrapper = mount(TodayRationale, {
        props: {
          rationale: {
            status: 'UNAVAILABLE',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-rationale-unavailable"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('المسوغات غير متوفرة');
    });

    it('renders EMPTY state correctly', () => {
      const wrapper = mount(TodayRationale, {
        props: {
          rationale: {
            status: 'EMPTY',
            data: null,
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-rationale-empty"]').exists()).toBe(true);
    });
  });

  describe('Level 4: TodayAttentionItems', () => {
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
  });


    it('renders STALE state correctly with data array and omits live wording', () => {
      const wrapper = mount(TodayAttentionItems, {
        props: {
          items: {
            status: 'STALE',
            data: [
              { id: 'att-1', title: 'Stale Attention', domain: 'progress', domainLabel: 'التقدم', href: '/1', severity: 'urgent', reason: 'R1' }
            ],
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-attention-list"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="today-stale-badge"]').exists()).toBe(true);
      expect(wrapper.find('.today-attention-count-badge').exists()).toBe(false); // Live wording omitted
    });

    it('renders STALE state correctly with explicit empty array', () => {
      const wrapper = mount(TodayAttentionItems, {
        props: {
          items: {
            status: 'STALE',
            data: [], // empty array
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-stale-empty-state"]').exists()).toBe(true);
    });

    it('renders STALE state correctly with explicit null', () => {
      const wrapper = mount(TodayAttentionItems, {
        props: {
          items: {
            status: 'STALE',
            data: null, // explicit null
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.find('[data-testid="today-stale-empty-state"]').exists()).toBe(true);
    });

  describe('Level 5: TodayRecentContext', () => {
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
  });

  describe('Level 6: TodayProgressProjection & Anti-Gamification', () => {
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
      expect(wrapper.text()).toContain('Completion != Mastery');
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
      expect(wrapper.text()).toContain('Completion != Mastery');
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
            nextAction: { status: 'UNAVAILABLE', data: null },
            rationale: { status: 'UNAVAILABLE', data: null },
            attentionItems: { status: 'UNAVAILABLE', data: [] },
            recentContext: { status: 'UNAVAILABLE', data: [] },
            progressProjection: { status: 'UNAVAILABLE', data: null },
          },
        },
        global: { stubs: globalStubs },
      });

      expect(wrapper.findComponent(TodayContinueSession).exists()).toBe(true);
      expect(wrapper.findComponent(TodayNextAction).exists()).toBe(true);
      expect(wrapper.findComponent(TodayRationale).exists()).toBe(true);
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
  });
});
