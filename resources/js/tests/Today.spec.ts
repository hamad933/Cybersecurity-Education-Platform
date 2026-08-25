import { mount } from '@vue/test-utils';
import * as inertia from '@inertiajs/vue3';
import { vi } from 'vitest';

import Today from '../pages/Today/Index.vue';
import type { TodayOrchestrationPayload } from '../components/today/types';

describe('Today workspace', () => {
  const fullOrchestrationMock: TodayOrchestrationPayload = {
    registeredDomainEntries: 4,
    expectedDomainEntries: 4,
    continueSession: {
      id: 'sess-101',
      title: 'محاكاة اختبار اختراق الشبكة الداخلية',
      domain: 'simulation',
      domainLabel: 'المحاكاة والمؤسسات',
      href: '/simulation/labs/network-penetration-1',
      moduleName: 'LAB-SEC-04',
      currentStep: 'المرحلة 3: كسر تجزئة كلمات المرور',
      lastActivityAt: '2026-08-25 04:30',
      actionLabel: 'استئناف الجلسة الآن',
    },
    nextAction: {
      id: 'act-201',
      title: 'مراجعة معيار التشفير المتقدم AES-GCM',
      domain: 'knowledge',
      domainLabel: 'المعرفة والتعلّم',
      href: '/knowledge/modules/aes-gcm',
      description: 'استكمال قراءة الوحدة المعرفية قبل الانتقال للتقييم العملي.',
      timeCommitment: '25 دقيقة',
      difficulty: 'متوسط',
      actionLabel: 'بدء قراءة الوحدة',
    },
    rationale: {
      id: 'rat-301',
      text: 'إتقان هذه الوحدة مطلوب لفتح متطلب تقييم أدلة التشفير التطبيقي.',
      targetCompetency: 'SEC-CRYPTO-L2',
      unlockedCapabilities: ['اختبار التشفير المتماثل', 'تحليل الهجمات الجانبية'],
      prerequisiteChain: ['SEC-MATH-01', 'SEC-NET-02'],
    },
    attentionItems: [
      {
        id: 'att-401',
        title: 'مراجعة إثبات عملي معلق',
        domain: 'progress',
        domainLabel: 'التقدم والأدلة',
        href: '/progress/evidence/ev-992',
        severity: 'urgent',
        reason: 'يتطلب المشرف إعادة تقديم تقرير تحليل الحزم بصيغة PCAP.',
        actionLabel: 'تحديث تقرير الإثبات',
      },
      {
        id: 'att-402',
        title: 'تحديث صلاحية شهادة بيئة الاختبار',
        domain: 'system',
        domainLabel: 'النظام والعمليات',
        href: '/system/keys/cert-renew',
        severity: 'warning',
        reason: 'ستنتهي صلاحية شهادة بيئة المحاكاة المحلية خلال 48 ساعة.',
        actionLabel: 'تجديد الشهادة',
      },
    ],
    recentContext: [
      {
        id: 'rec-501',
        title: 'إتمام مختبر فحص الثغرات Nmap',
        domain: 'simulation',
        domainLabel: 'المحاكاة والمؤسسات',
        href: '/simulation/labs/nmap-completed',
        timestamp: '2026-08-24 18:00',
        summary: 'تم التحقق من كافة منافذ الهدف وتوثيق سجل النتائج.',
      },
    ],
    progressProjection: {
      milestoneTitle: 'المرحلة الأولى: أمن الشبكات المتقدم',
      verifiedCount: 3,
      totalCount: 5,
      statusSummary: '3 من أصل 5 وحدات معرفية محققة بأدلة معتمدة.',
      targetHorizon: 'نهاية الأسبوع الجاري',
      evidenceRequirement: 'تقديم تقرير عملي لاختبار جدار الحماية',
    },
  };

  it('renders empty orchestration hierarchy truthfully and maintains region ownership', async () => {
    const wrapper = mount(Today, {
      props: {
        orchestration: {
          registeredDomainEntries: 3,
          expectedDomainEntries: 4,
          continueSession: null,
          nextAction: null,
          rationale: null,
          attentionItems: [],
          recentContext: [],
          progressProjection: null,
        },
      },
    });

    // Page title and Kicker
    expect(wrapper.text()).toContain('اليوم');
    expect(wrapper.find('#today-title').text()).toBe('اليوم');

    // Action Bar in TOP region
    expect(wrapper.find('.cep-action-bar').exists()).toBe(true);
    expect(wrapper.text()).toContain('سطح قيادة وتنسيق اليوم');

    // Empty states for hierarchy levels
    expect(wrapper.find('[data-testid="today-session-empty"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="today-next-action-empty"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="today-rationale-empty"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="today-attention-empty"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="today-recent-empty"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="today-projection-empty"]').exists()).toBe(true);

    // Left Navigation links
    const leftNavLinks = wrapper.findAll('.cep-structure-nav__link');
    expect(leftNavLinks.map((l) => l.attributes('href'))).toEqual([
      '#continue-session',
      '#next-action',
      '#rationale',
      '#attention-items',
      '#recent-context',
      '#progress-projection',
      '#workspace-handoffs',
    ]);

    // Right Context Panel boundaries
    const right = wrapper.find('[data-cep-region="right"]');
    expect(right.exists()).toBe(true);
    expect(right.text()).toContain('حدود سلطة سطح اليوم');
    expect(right.text()).toContain('الإنجاز لا يعني الإتقان');
    expect(right.text()).toContain('استقلالية المجالات');

    // Bottom Diagnostics Drawer (Closed by default)
    expect(wrapper.find('[data-cep-region="bottom"]').exists()).toBe(false);

    // Toggle Bottom Diagnostics Drawer
    const toggleBtn = wrapper.get('[data-testid="today-diagnostics-toggle"]');
    expect(toggleBtn.attributes('aria-expanded')).toBe('false');
    await toggleBtn.trigger('click');

    expect(toggleBtn.attributes('aria-expanded')).toBe('true');
    const bottom = wrapper.get('[data-cep-region="bottom"]');
    expect(bottom.text()).toContain('ربط مساحات العمل');
    expect(bottom.text()).toContain('3/4');

    // Canonical Workspace Handoffs
    const workspaceLinks = wrapper.findAll('[data-today-workspace]');
    expect(workspaceLinks).toHaveLength(4);
    expect(workspaceLinks.map((link) => link.attributes('href'))).toEqual([
      '/knowledge',
      '/simulation',
      '/progress',
      '/system',
    ]);

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
    });

    // Verify all 6 hierarchical levels exist and are ordered 1 to 6
    const levels = wrapper.findAll('[data-today-level]');
    expect(levels).toHaveLength(6);
    expect(levels.map((el) => el.attributes('data-today-level'))).toEqual([
      '1',
      '2',
      '3',
      '4',
      '5',
      '6',
    ]);

    // Level 1: Continue Current Session
    expect(wrapper.find('[data-testid="today-session-active"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('محاكاة اختبار اختراق الشبكة الداخلية');
    expect(wrapper.text()).toContain('LAB-SEC-04');
    expect(wrapper.find('[data-testid="today-session-resume"]').attributes('href')).toBe(
      '/simulation/labs/network-penetration-1',
    );

    // Level 2: Next Recommended Action
    expect(wrapper.find('[data-testid="today-next-action-active"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('مراجعة معيار التشفير المتقدم AES-GCM');
    expect(wrapper.text()).toContain('25 دقيقة');
    expect(wrapper.find('[data-testid="today-next-action-link"]').attributes('href')).toBe(
      '/knowledge/modules/aes-gcm',
    );

    // Level 3: Rationale
    expect(wrapper.find('[data-testid="today-rationale-active"]').exists()).toBe(true);
    expect(wrapper.text()).toContain(
      'إتقان هذه الوحدة مطلوب لفتح متطلب تقييم أدلة التشفير التطبيقي',
    );
    expect(wrapper.text()).toContain('SEC-CRYPTO-L2');
    expect(wrapper.text()).toContain('اختبار التشفير المتماثل');

    // Level 4: Attention Items
    expect(wrapper.find('[data-testid="today-attention-list"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('مراجعة إثبات عملي معلق');
    expect(wrapper.text()).toContain('عاجل');
    expect(wrapper.text()).toContain('تحديث صلاحية شهادة بيئة الاختبار');
    expect(wrapper.text()).toContain('مراجعة مطلوبة');

    // Level 5: Recent Context
    expect(wrapper.find('[data-testid="today-recent-list"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('إتمام مختبر فحص الثغرات Nmap');

    // Level 6: Progress Projection
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
    });

    const pageText = wrapper.text();

    // Verify anti-gamification laws: no percentage mastery, no XP, no fake gamified bars
    expect(pageText).not.toMatch(/\b\d{1,3}%\s*(إتقان|Mastery|mastery)/i);
    expect(pageText).not.toContain('XP');
    expect(pageText).not.toContain('نقاط خبرة');
    expect(pageText).not.toContain('شارات الشرف');

    // Explicitly states Completion != Mastery
    expect(pageText).toContain('Completion != Mastery');
  });

  it('renders technical text inside LTR-isolated containers', () => {
    const wrapper = mount(Today, {
      props: {
        orchestration: fullOrchestrationMock,
      },
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
          attentionItems: [],
        },
      },
    });

    const emptyAttention = wrapper.find('[data-testid="today-attention-empty"]');
    expect(emptyAttention.exists()).toBe(true);
    expect(emptyAttention.text()).toContain('لا توجد بنود انتباه واردة حاليًا');
    expect(emptyAttention.text()).toContain(
      'لا يتلقى سطح اليوم حاليًا أي بنود انتباه أو متطلبات مراجعة معلقة من مساحات العمل',
    );

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
        },
      },
    });

    const toggleBtnAbsent = wrapperAbsent.get('[data-testid="today-diagnostics-toggle"]');
    await toggleBtnAbsent.trigger('click');

    const bottomAbsent = wrapperAbsent.get('[data-cep-region="bottom"]');
    const bottomTextAbsent = bottomAbsent.text();

    // Truthful unavailable semantic
    expect(bottomTextAbsent).toContain('غير مرصود');
    expect(bottomTextAbsent).toContain('تشخيص محلي');

    // Static positive claims and fake fallbacks strictly forbidden
    expect(bottomTextAbsent).not.toContain('مزامنة نشطة');
    expect(bottomTextAbsent).not.toContain('local');
    expect(bottomTextAbsent).not.toContain('development');
    expect(bottomTextAbsent).not.toContain('healthy');
    expect(bottomTextAbsent).not.toContain('synchronized');

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
        },
      },
    });

    const toggleBtnProvided = wrapperProvided.get('[data-testid="today-diagnostics-toggle"]');
    await toggleBtnProvided.trigger('click');

    const bottomProvided = wrapperProvided.get('[data-cep-region="bottom"]');
    expect(bottomProvided.text()).toContain('governed-prod');
    expect(bottomProvided.text()).toContain('hardened-enterprise');

    usePageSpy.mockRestore();
  });
});
