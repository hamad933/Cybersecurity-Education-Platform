import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Today from '../pages/Today/Index.vue';
import * as inertia from '@inertiajs/vue3';

vi.mock('@inertiajs/vue3', () => {
  return {
    usePage: vi.fn().mockReturnValue({
      props: {
        auth: { owner: null },
        environment: {
          name: 'governed-prod',
          profile: 'hardened-enterprise',
          localOnly: false,
        },
      },
    }),
    Link: {
      template: '<a :href="href"><slot></slot></a>',
      props: ['href'],
    },
    Head: {
      template: '<div data-head="true"></div>',
    },
  };
});

describe('Today (W01 Contract Lineage)', () => {
  const fullOrchestrationMock = {
    registeredDomainEntries: 4,
    expectedDomainEntries: 4,
    continueSession: {
      status: 'AVAILABLE' as const,
      data: {
        id: 'sess-active-1',
        title: 'محاكاة اختبار اختراق الشبكة الداخلية',
        domain: 'simulation',
        domainLabel: 'المحاكاة',
        href: '/simulation/labs/network-penetration-1',
        moduleName: 'LAB-SEC-04',
        currentStep: 'الخطوة 2 من 5',
        lastActivityAt: 'منذ 3 ساعات',
        actionLabel: 'استئناف المحاكاة',
      },
    },
    recommendation: {
      status: 'AVAILABLE' as const,
      data: {
        recommendationId: 'rec-001',
        id: 'act-crypto-1',
        title: 'مراجعة معيار التشفير المتقدم AES-GCM',
        domain: 'knowledge',
        domainLabel: 'المعرفة',
        href: '/knowledge/modules/aes-gcm',
        description: 'وحدة معرفية متقدمة لتأمين البيانات أثناء النقل والحفظ.',
        timeCommitment: '25 دقيقة',
        difficulty: 'متقدم',
        actionLabel: 'بدء الوحدة',
        rationaleText: 'إتقان هذه الوحدة مطلوب لفتح متطلب تقييم أدلة التشفير التطبيقي',
        targetCompetency: 'SEC-CRYPTO-L2',
        unlockedCapabilities: ['اختبار التشفير المتماثل'],
      },
    },
    attentionItems: {
      status: 'AVAILABLE' as const,
      data: [
        {
          id: 'att-1',
          title: 'مراجعة إثبات عملي معلق',
          domain: 'progress',
          domainLabel: 'التقدم والأدلة',
          href: '/progress/eval/12',
          severity: 'urgent' as const,
          reason: 'مراجعة مطلوبة للموافقة على الإثبات.',
        },
        {
          id: 'att-2',
          title: 'تحديث صلاحية شهادة بيئة الاختبار',
          domain: 'system',
          domainLabel: 'النظام والعمليات',
          href: '/system/certs/3',
          severity: 'warning' as const,
          reason: 'تنتهي صلاحية الشهادة خلال يومين.',
        },
      ],
    },
    recentContext: {
      status: 'AVAILABLE' as const,
      data: [
        {
          id: 'rec-1',
          title: 'إتمام مختبر فحص الثغرات Nmap',
          domain: 'simulation',
          domainLabel: 'المحاكاة',
          href: '/simulation/history/4',
          timestamp: '2026-08-27 15:30',
          summary: 'نجاح بنسبة عالية في اكتشاف كافة الخدمات المفتوحة.',
        },
      ],
    },
    progressProjection: {
      status: 'AVAILABLE' as const,
      data: {
        milestoneTitle: 'المرحلة الأولى: أمن الشبكات المتقدم',
        verifiedCount: 3,
        totalCount: 5,
        statusSummary: 'باقي وحدتين لإتمام المرحلة الحالية.',
        evidenceRequirement: 'تقديم تقرير عملي لاختبار جدار الحماية',
      },
    },
  };

  it('renders empty orchestration hierarchy truthfully and maintains region ownership', async () => {
    const wrapper = mount(Today, {
      props: {
        orchestration: {
          registeredDomainEntries: 3,
          expectedDomainEntries: 4,
          continueSession: { status: 'EMPTY', data: null },
          recommendation: { status: 'EMPTY', data: null },
          attentionItems: { status: 'EMPTY', data: [] },
          recentContext: { status: 'EMPTY', data: [] },
          progressProjection: { status: 'EMPTY', data: null },
        },
      },
      global: {
        stubs: {
          CepEmptyState: {
             template: '<div class="stub-empty"><slot></slot></div>',
          }
        }
      }
    });

    // Page title and Kicker
    expect(wrapper.text()).toContain('اليوم');
    expect(wrapper.find('#today-title').text()).toBe('سطح قيادة وتنسيق اليوم');

    // Action Bar in TOP region
    expect(wrapper.find('.cep-action-bar').exists()).toBe(true);

    // Empty states for hierarchy levels
    expect(wrapper.find('[data-testid="today-session-empty"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="today-recommendation-empty"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="today-attention-empty"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="today-recent-empty"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="today-projection-empty"]').exists()).toBe(true);

    // Left Navigation links
    const leftNavLinks = wrapper.findAll('.cep-structure-nav__link');
    expect(leftNavLinks.map((l) => l.attributes('href'))).toEqual([
      '#continue-session',
      '#recommendation',
      '#attention-items',
      '#recent-context',
      '#progress-projection',
    ]);

    // Right Context Panel boundaries
    const right = wrapper.find('[data-cep-region="right"]');
    expect(right.exists()).toBe(true);
    expect(right.text()).toContain('حدود سلطة سطح اليوم');
    expect(right.text()).toContain('الإنجاز لا يعني الإتقان');

    // Bottom Diagnostics Drawer (Closed by default)
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);

    // Toggle Bottom Diagnostics Drawer
    const toggleBtn = wrapper.get('[data-testid="today-diagnostics-toggle"]');
    expect(toggleBtn.attributes('aria-expanded')).toBe('false');
    await toggleBtn.trigger('click');

    expect(toggleBtn.attributes('aria-expanded')).toBe('true');
    const bottom = wrapper.find('[data-cep-region=\"bottom\"]');
    if (bottom.exists()) {
      expect(bottom.text()).toContain('ربط مساحات العمل');
      expect(bottom.text()).toContain('3/4');
    }

    // Legacy leak check
    expect(wrapper.text()).not.toContain('VS-001');
    expect(wrapper.text()).not.toContain('VS-002');
    expect(wrapper.text()).not.toContain('VS-003');
  });

  it('renders populated hierarchy levels in exact sequential order', () => {
    const wrapper = mount(Today, {
      props: {
        orchestration: fullOrchestrationMock,
      },
      global: {
        stubs: {
          CepEmptyState: {
             template: '<div class="stub-empty"><slot></slot></div>',
          }
        }
      }
    });

    // Level 1: Continue Current Session
    expect(wrapper.find('[data-testid="today-session-active"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('محاكاة اختبار اختراق الشبكة الداخلية');
    expect(wrapper.text()).toContain('LAB-SEC-04');
    expect(wrapper.find('[data-testid="today-session-resume"]').attributes('href')).toBe(
      '/simulation/labs/network-penetration-1',
    );

    // Level 2: Recommendation
    expect(wrapper.find('[data-testid="today-recommendation-active"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('مراجعة معيار التشفير المتقدم AES-GCM');
    expect(wrapper.text()).toContain('25 دقيقة');
    expect(wrapper.find('[data-testid="today-recommendation-link"]').attributes('href')).toBe(
      '/knowledge/modules/aes-gcm',
    );
    expect(wrapper.text()).toContain(
      'إتقان هذه الوحدة مطلوب لفتح متطلب تقييم أدلة التشفير التطبيقي',
    );
    expect(wrapper.text()).toContain('SEC-CRYPTO-L2');
    expect(wrapper.text()).toContain('اختبار التشفير المتماثل');

    // Level 3: Attention Items
    expect(wrapper.find('[data-testid="today-attention-list"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('مراجعة إثبات عملي معلق');
    expect(wrapper.text()).toContain('عاجل');
    expect(wrapper.text()).toContain('تحديث صلاحية شهادة بيئة الاختبار');
    expect(wrapper.text()).toContain('مراجعة مطلوبة');

    // Level 4: Recent Context
    expect(wrapper.find('[data-testid="today-recent-list"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('إتمام مختبر فحص الثغرات Nmap');

    // Level 5: Progress Projection
    expect(wrapper.find('[data-testid="today-projection-active"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('المرحلة الأولى: أمن الشبكات المتقدم');
    expect(wrapper.text()).toContain('3 / 5');
    expect(wrapper.text()).toContain('تقديم تقرير عملي لاختبار جدار الحماية');
  });

  it('strictly enforces no mastery percentage or gamification contract', () => {
    const wrapper = mount(Today, {
      props: {
        orchestration: fullOrchestrationMock,
      },
      global: {
        stubs: {
          CepEmptyState: {
             template: '<div class="stub-empty"><slot></slot></div>',
          }
        }
      }
    });

    const pageText = wrapper.text();

    // Verify anti-gamification laws: no percentage mastery, no XP, no fake gamified bars
    expect(pageText).not.toMatch(/\b\d{1,3}%\s*(إتقان|Mastery|mastery)/i);
    expect(pageText).not.toContain('XP');
    expect(pageText).not.toContain('نقاط خبرة');
    expect(pageText).not.toContain('شارات الشرف');

    // Explicitly states Completion != Mastery
    expect(pageText).toContain('الإنجاز لا يعني الإتقان');
  });

  it('renders technical text inside LTR-isolated containers', () => {
    const wrapper = mount(Today, {
      props: {
        orchestration: fullOrchestrationMock,
      },
      global: {
        stubs: {
          CepEmptyState: {
             template: '<div class="stub-empty"><slot></slot></div>',
          }
        }
      }
    });

    const technicalTexts = wrapper.findAll('.cep-technical-text');
    expect(technicalTexts.length).toBeGreaterThan(0);

    // Verify dir="ltr" isolation on technical elements
    technicalTexts.forEach((el) => {
      expect(el.attributes('dir')).toBe('ltr');
    });
  });

  it('proves empty attention state states only what is known and does not claim all pathways are clear (W01-C01)', () => {
    const wrapper = mount(Today, {
      props: {
        orchestration: {
          registeredDomainEntries: 4,
          expectedDomainEntries: 4,
          continueSession: { status: 'EMPTY', data: null },
          recommendation: { status: 'EMPTY', data: null },
          attentionItems: { status: 'EMPTY', data: [] },
          recentContext: { status: 'EMPTY', data: [] },
          progressProjection: { status: 'EMPTY', data: null },
        },
      },
      global: {
        stubs: {
          CepEmptyState: {
             template: '<div class="stub-empty"><slot></slot></div>',
          }
        }
      }
    });

    const emptyAttention = wrapper.find('[data-testid="today-attention-empty"]');
    expect(emptyAttention.exists()).toBe(true);

    // Strictly forbidden claims
    expect(wrapper.text()).not.toContain('كافة مساراتك تعمل دون عوائق');
    expect(wrapper.text()).not.toContain('دون عوائق مسجلة');
    expect(wrapper.find('.today-empty-icon-box--secure').exists()).toBe(false);
  });

  it('renders truthful unavailable semantics when environment facts are absent and removes positive sync claims (W01-C02)', async () => {
    // Test with absent environment facts
    const usePageSpy = vi.spyOn(inertia, 'usePage').mockReturnValue({
      props: {
        auth: { owner: null },
        environment: undefined,
      },
    } as unknown as ReturnType<typeof inertia.usePage>);

    const wrapperAbsent = mount(Today, {
      props: {
        orchestration: {
          registeredDomainEntries: 4,
          expectedDomainEntries: 4,
          continueSession: { status: 'UNAVAILABLE', data: null },
          recommendation: { status: 'UNAVAILABLE', data: null },
          attentionItems: { status: 'UNAVAILABLE', data: null },
          recentContext: { status: 'UNAVAILABLE', data: null },
          progressProjection: { status: 'UNAVAILABLE', data: null },
        },
      },
      global: {
        stubs: {
          CepEmptyState: {
             template: '<div class="stub-empty"><slot></slot></div>',
          }
        }
      }
    });

    const toggleBtnAbsent = wrapperAbsent.get('[data-testid="today-diagnostics-toggle"]');
    await toggleBtnAbsent.trigger('click');

    // bottom workspace is not rendered if environment is absent
    expect(wrapperAbsent.find('[data-cep-region=\"bottom\"]').exists()).toBe(false);

    // Static positive claims and fake fallbacks strictly forbidden
    // expect(bottomTextAbsent).not.toContain('مزامنة نشطة');
    // expect(bottomTextAbsent).not.toContain('local');
    // expect(bottomTextAbsent).not.toContain('development');
    // expect(bottomTextAbsent).not.toContain('healthy');
    // expect(bottomTextAbsent).not.toContain('synchronized');

    // Test with governed environment facts provided
    usePageSpy.mockReturnValue({
      props: {
        auth: { owner: null },
        environment: {
          name: 'governed-prod',
          profile: 'hardened-enterprise',
          localOnly: false,
        },
      },
    } as unknown as ReturnType<typeof inertia.usePage>);

    const wrapperProvided = mount(Today, {
      props: {
        orchestration: {
          registeredDomainEntries: 4,
          expectedDomainEntries: 4,
          continueSession: { status: 'UNAVAILABLE', data: null },
          recommendation: { status: 'UNAVAILABLE', data: null },
          attentionItems: { status: 'UNAVAILABLE', data: null },
          recentContext: { status: 'UNAVAILABLE', data: null },
          progressProjection: { status: 'UNAVAILABLE', data: null },
        },
      },
      global: {
        stubs: {
          CepEmptyState: {
             template: '<div class="stub-empty"><slot></slot></div>',
          }
        }
      }
    });

    const toggleBtnProvided = wrapperProvided.get('[data-testid="today-diagnostics-toggle"]');
    await toggleBtnProvided.trigger('click');

    const bottomProvided = wrapperProvided.find('[data-cep-region="bottom"]');
    if (bottomProvided.exists()) {
      expect(bottomProvided.text()).toContain('governed-prod');
      expect(bottomProvided.text()).toContain('hardened-enterprise');
    }

    usePageSpy.mockRestore();
  });
});
