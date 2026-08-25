import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AiBridgeContext from '../pages/SystemOperations/components/ai-bridge/AiBridgeContext.vue';
import AiBridgeSurface from '../pages/SystemOperations/components/ai-bridge/AiBridgeSurface.vue';
import AuditContext from '../pages/SystemOperations/components/audit/AuditContext.vue';
import AuditSurface from '../pages/SystemOperations/components/audit/AuditSurface.vue';
import BackupsContext from '../pages/SystemOperations/components/backups/BackupsContext.vue';
import BackupsSurface from '../pages/SystemOperations/components/backups/BackupsSurface.vue';
import ConfigurationContext from '../pages/SystemOperations/components/configuration/ConfigurationContext.vue';
import ConfigurationSurface from '../pages/SystemOperations/components/configuration/ConfigurationSurface.vue';
import HealthContext from '../pages/SystemOperations/components/health/HealthContext.vue';
import HealthSurface from '../pages/SystemOperations/components/health/HealthSurface.vue';
import ProcessingContext from '../pages/SystemOperations/components/processing/ProcessingContext.vue';
import ProcessingSurface from '../pages/SystemOperations/components/processing/ProcessingSurface.vue';
import ReleasesContext from '../pages/SystemOperations/components/releases/ReleasesContext.vue';
import ReleasesSurface from '../pages/SystemOperations/components/releases/ReleasesSurface.vue';
import SystemNavRail from '../pages/SystemOperations/components/SystemNavRail.vue';
import ValidationContext from '../pages/SystemOperations/components/validation/ValidationContext.vue';
import ValidationSurface from '../pages/SystemOperations/components/validation/ValidationSurface.vue';
import Workspace from '../pages/SystemOperations/Workspace.vue';

describe('System Operations Components & Surfaces', () => {
  describe('SystemNavRail', () => {
    it('renders all eight operational surfaces with active state on current destination', () => {
      const wrapper = mount(SystemNavRail, {
        props: { activeSurface: 'health' },
      });

      expect(wrapper.findAll('.rail-link')).toHaveLength(8);
      expect(wrapper.find('.rail-link.active').text()).toContain('الصحة التشغيلية');
      expect(wrapper.text()).toContain('المعالجة والطوابير');
      expect(wrapper.text()).toContain('التحقق التقني');
      expect(wrapper.text()).toContain('جسر AI اليدوي');
      expect(wrapper.text()).toContain('النسخ والاستعادة');
      expect(wrapper.text()).toContain('سجل التدقيق');
      expect(wrapper.text()).toContain('التحقق من الإصدار');
      expect(wrapper.text()).toContain('التهيئة التشغيلية');
    });
  });

  describe('HealthSurface & HealthContext (Truthful State Derivation)', () => {
    const mockObservedZeroState = {
      foundation: {
        checks: { database: 'ok', queue: 'ok', storage: 'ok' },
        healthy: true,
        failed_checks: [],
      },
      processing: { counts: { running: 0, pending: 0, failed: 0 } },
      outbox: { counts: { failed: 0 } },
      packages: { counts: { rejected: 0, exported: 0 }, records: [] },
      release_gate: { ready: true, checks: { migrations: { status: 'ok', detail: '' } } },
      policy: { execution: 'MANUAL_ONLY' },
      backups: [],
    };

    const mockUnavailableState = {
      foundation: { checks: {}, healthy: false, failed_checks: [] },
      processing: { counts: {} },
      outbox: { counts: {} },
      packages: { counts: {}, records: [] },
      release_gate: { ready: false, checks: {} },
    };

    it('renders OBSERVED_ZERO as numeric zero with bounded unblocked claims', async () => {
      const wrapper = mount(HealthSurface, {
        props: { state: mockObservedZeroState, selectedSubsystem: 'validation' },
      });

      expect(wrapper.text()).toContain('جميع الحزم المسجلة مطابقة');
      expect(wrapper.text()).toContain('لا يوجد حجب نشط');

      await wrapper.setProps({ selectedSubsystem: 'processing' });
      expect(wrapper.text()).toContain('لا توجد مهام نشطة');

      await wrapper.setProps({ selectedSubsystem: 'ai-bridge' });
      expect(wrapper.text()).toContain('يدوي فقط'); // AI bridge policy observed

      await wrapper.setProps({ selectedSubsystem: 'backups' });
      expect(wrapper.text()).toContain('لم تسجل نسخ احتياطية');
      expect(wrapper.text()).not.toContain('وحدة التخزين متاحة');

      expect(wrapper.get('[data-testid="metric-processing-running"]').text()).toBe('0');
      expect(wrapper.get('[data-testid="metric-outbox-failed"]').text()).toBe('0');
      expect(wrapper.get('[data-testid="metric-packages-rejected"]').text()).toBe('0');

      const contextWrapper = mount(HealthContext, {
        props: { state: mockObservedZeroState, selectedSubsystem: 'validation' },
      });
      expect(contextWrapper.text()).toContain('لم يتم رصد حزم مرفوضة');
    });

    it('renders UNAVAILABLE without zero, no-block, or no-action claims', async () => {
      const wrapper = mount(HealthSurface, {
        props: { state: mockUnavailableState, selectedSubsystem: 'validation' },
      });

      expect(wrapper.text()).toContain('غير متاح — لم تتم ملاحظة حالة الحجب');
      expect(wrapper.text()).toContain('تعذر تحديد الإجراء المطلوب قبل توفر بيانات التحقق');
      expect(wrapper.text()).not.toContain('لا يوجد حجب نشط');
      expect(wrapper.text()).not.toContain('لا يلزم أي إجراء');
      expect(wrapper.get('[data-testid="metric-processing-running"]').text()).toBe('—');
      expect(wrapper.get('[data-testid="metric-outbox-failed"]').text()).toBe('—');
      expect(wrapper.get('[data-testid="metric-packages-rejected"]').text()).toBe('—');

      await wrapper.setProps({ selectedSubsystem: 'processing' });
      expect(wrapper.text()).toContain('تعذر تحديد الإجراء المطلوب قبل توفر بيانات المعالجة');
      expect(wrapper.text()).not.toContain('الطابور في حالة طبيعية');
      expect(wrapper.text()).not.toContain('لا توجد مهام نشطة');

      const contextWrapper = mount(HealthContext, {
        props: {
          state: {
            foundation: { checks: { storage: 'ok' }, healthy: true, failed_checks: [] },
            processing: { counts: { failed: 0 } },
          },
          selectedSubsystem: 'validation',
        },
      });
      expect(contextWrapper.text()).toContain('غير متاح — لم تتم ملاحظة أية فحوص تشغيلية');
      expect(contextWrapper.text()).not.toContain('لم يتم رصد مهام معالجة فاشلة');
    });

    it('renders OBSERVED_NONZERO failures and counts reactively', () => {
      const mockFailureState = {
        foundation: {
          checks: { database: 'failed', queue: 'ok', storage: 'ok' },
          healthy: false,
          failed_checks: ['database'],
        },
        processing: { counts: { running: 1, pending: 2, failed: 4 } },
        outbox: { counts: { failed: 1 } },
        packages: { counts: { rejected: 3, exported: 8 }, records: [] },
        release_gate: {
          ready: false,
          checks: { migrations: { status: 'failed', detail: 'Pending migration.' } },
        },
      };

      // Test processing selection
      const wrapperProcessing = mount(HealthSurface, {
        props: {
          state: mockFailureState,
          selectedSubsystem: 'processing',
        },
      });

      expect(wrapperProcessing.text()).toContain('توجد 4 معالجة فاشلة مسجلة');
      expect(wrapperProcessing.text()).toContain('توجد معالجات فاشلة (4)');
      expect(wrapperProcessing.text()).toContain('✕ 4 فاشلة');
      expect(wrapperProcessing.text()).toContain('▶ 1 جارية');
      expect(wrapperProcessing.text()).toContain('⏳ 2 معلقة');
      expect(wrapperProcessing.text()).toContain('تعثر مهام في الطابور');

      // Test validation selection
      const wrapperValidation = mount(HealthSurface, {
        props: {
          state: mockFailureState,
          selectedSubsystem: 'validation',
        },
      });

      expect(wrapperValidation.text()).toContain('توجد 3 حزم مرفوضة في السجل');
      expect(wrapperValidation.text()).toContain('توجد حزم مرفوضة (3)');
      expect(wrapperValidation.text()).toContain('✕ 3 مرفوضة');
      expect(wrapperValidation.text()).toContain('✓ 8 مقبولة');
      expect(wrapperValidation.text()).toContain('حجب الحزم المرفوضة');
    });

    it('isolates selected-subsystem health from unrelated observations', () => {
      const validationContext = mount(HealthContext, {
        props: {
          state: { processing: { counts: { running: 0, pending: 0, failed: 0 } } },
          selectedSubsystem: 'validation',
        },
      });

      expect(validationContext.text()).toContain('غير متاح — لم تتم ملاحظة أية فحوص تشغيلية');
      expect(validationContext.text()).not.toContain('لم يتم رصد مهام معالجة فاشلة');

      const processingContext = mount(HealthContext, {
        props: {
          state: { packages: { counts: { rejected: 0, exported: 0 }, records: [] } },
          selectedSubsystem: 'processing',
        },
      });

      expect(processingContext.text()).toContain('غير متاح — لم تتم ملاحظة أية فحوص تشغيلية');
      expect(processingContext.text()).not.toContain('لم يتم رصد حزم مرفوضة');
    });

    it('derives backup health only from backup manifests, independently of storage health', () => {
      const storageOnly = mount(HealthSurface, {
        props: {
          state: {
            foundation: { checks: { storage: 'ok' }, healthy: true, failed_checks: [] },
          },
          selectedSubsystem: 'backups',
        },
      });
      const storageOnlyRow = storageOnly
        .findAll('tbody tr')
        .find((row) => row.text().includes('حالة النسخ الاحتياطي'))!;
      expect(storageOnlyRow.text()).toContain('غير متاح');
      expect(storageOnlyRow.text()).not.toContain('سليم');

      const verifiedBackup = mount(HealthSurface, {
        props: {
          state: {
            foundation: {
              checks: { storage: 'failed' },
              healthy: false,
              failed_checks: ['storage'],
            },
            backups: [
              {
                id: 'backup-verified',
                portable_package_id: 'pkg-backup',
                status: 'verified',
                database_driver: 'pgsql',
                content_digest: 'a'.repeat(64),
                created_at: '2026-08-25T01:00:00Z',
              },
            ],
          },
          selectedSubsystem: 'backups',
        },
      });
      const verifiedRow = verifiedBackup
        .findAll('tbody tr')
        .find((row) => row.text().includes('حالة النسخ الاحتياطي'))!;
      expect(verifiedRow.text()).toContain('سليم');
      expect(verifiedRow.text()).toContain('verified');
      expect(verifiedBackup.text()).not.toContain('وحدة التخزين متاحة');
    });

    it('distinguishes absent release checks from an observed closed gate', () => {
      const absentChecks = mount(HealthSurface, {
        props: {
          state: { release_gate: { ready: false, checks: {} } },
          selectedSubsystem: 'releases',
        },
      });
      expect(absentChecks.text()).toContain('غير متاح — لم تتم ملاحظة حالة البوابة');
      expect(absentChecks.text()).not.toContain('سياسة حظر الإطلاق التلقائي');

      const observedClosed = mount(HealthSurface, {
        props: {
          state: {
            release_gate: {
              ready: false,
              checks: { migrations: { status: 'failed', detail: 'Pending migration.' } },
            },
          },
          selectedSubsystem: 'releases',
        },
      });
      expect(observedClosed.text()).toContain('سياسة حظر الإطلاق التلقائي');
      expect(observedClosed.text()).toContain('بانتظار استيفاء الفحوص');

      const absentContext = mount(HealthContext, {
        props: {
          state: { release_gate: { ready: false, checks: {} } },
          selectedSubsystem: 'releases',
        },
      });
      expect(absentContext.text()).not.toContain('بوابة الإصدار مغلقة');
      expect(absentContext.text()).toContain('غير متاح — لم تتم ملاحظة أية فحوص تشغيلية');
    });

    it('renders HealthContext with state-driven impact and dependencies based on selected subsystem', () => {
      const failureContext = mount(HealthContext, {
        props: {
          state: {
            foundation: {
              checks: { database: 'failed' },
              healthy: false,
              failed_checks: ['database'],
            },
            packages: { counts: { rejected: 2 } },
          },
          selectedSubsystem: 'validation',
        },
      });

      expect(failureContext.text()).toContain('تأثير الإخفاقات الأساسية');
      expect(failureContext.text()).toContain('توجد 1 فحوص أساسية فاشلة (database)');
    });

    it('renders neutral UNAVAILABLE hero status when foundation checks are missing rather than ATTENTION danger', () => {
      const emptyStateWrapper = mount(HealthSurface, {
        props: { state: {}, selectedSubsystem: 'validation' },
      });

      const hero = emptyStateWrapper.find('[data-testid="health-hero"]');
      expect(hero.text()).toContain('حالة المكونات الأساسية غير متوفرة');
      expect(hero.text()).toContain('UNAVAILABLE');
      expect(hero.text()).not.toContain('ATTENTION');

      const unavailableStateWrapper = mount(HealthSurface, {
        props: {
          state: {
            foundation: { checks: {}, healthy: false, failed_checks: [] },
          },
          selectedSubsystem: 'validation',
        },
      });

      const unavailHero = unavailableStateWrapper.find('[data-testid="health-hero"]');
      expect(unavailHero.text()).toContain('حالة المكونات الأساسية غير متوفرة');
      expect(unavailHero.text()).toContain('UNAVAILABLE');
      expect(unavailHero.text()).not.toContain('ATTENTION');
    });
  });

  describe('ProcessingSurface & ProcessingContext', () => {
    it('emits openDeep event with full diagnostics when inspect button is clicked', async () => {
      const wrapper = mount(ProcessingSurface, {
        props: {
          state: {
            processing: {
              counts: { failed: 1 },
              runs: [
                {
                  id: 'proc-101',
                  type: 'artifact.generation',
                  input_digest: 'd'.repeat(64),
                  status: 'failed',
                  attempt_count: 3,
                  max_attempts: 3,
                  worker_identifier: 'worker-primary',
                  started_at: '2026-08-20T10:00:00Z',
                  completed_at: '2026-08-20T10:01:00Z',
                  cancelled_at: null,
                  error_category: 'timeout',
                  safe_error_message: 'Worker execution exceeded limit.',
                  created_at: '2026-08-20T10:00:00Z',
                },
              ],
            },
            outbox: {
              counts: {},
              messages: [
                {
                  id: 'outbox-1',
                  type: 'artifact.published',
                  producer_module: 'MOD-PLT',
                  correlation_id: 'corr-1',
                  dispatch_state: 'dispatched',
                  attempts: 1,
                  occurred_at: '2026-08-20T10:00:00Z',
                  next_attempt_at: null,
                  dispatched_at: '2026-08-20T10:00:05Z',
                },
              ],
            },
          },
        },
      });

      expect(wrapper.text()).toContain('proc-101');
      expect(wrapper.text()).toContain('artifact.generation');
      expect(wrapper.text()).toContain('worker-primary');
      expect(wrapper.text()).toContain('artifact.published');

      await wrapper.find('.btn-inspect').trigger('click');
      expect(wrapper.emitted('openDeep')).toBeTruthy();
      const emitted = wrapper.emitted('openDeep')![0];
      expect(emitted[0]).toContain('تشخيص المعالجة');
      expect(JSON.stringify(emitted[1])).toContain('Worker execution exceeded limit.');
    });

    it('renders ProcessingContext with queue engine and cancellation invariants', () => {
      const wrapper = mount(ProcessingContext, {
        props: { state: {} },
      });

      expect(wrapper.text()).toContain('ضوابط الطوابير والتنفيذ');
      expect(wrapper.text()).toContain('سياسة الإلغاء');
      expect(wrapper.text()).toContain('محرك الطوابير');
      expect(wrapper.text()).toContain('نمط Outbox');
    });

    it('distinguishes unavailable processing counts from observed zero', () => {
      const unavailable = mount(ProcessingSurface, { props: { state: {} } });
      expect(unavailable.get('[data-testid="processing-count-pending"]').text()).toBe('—');
      expect(unavailable.get('[data-testid="processing-count-running"]').text()).toBe('—');
      expect(unavailable.get('[data-testid="processing-count-completed"]').text()).toBe('—');
      expect(unavailable.get('[data-testid="processing-count-failed"]').text()).toBe('—');

      const observedZero = mount(ProcessingSurface, {
        props: {
          state: {
            processing: {
              counts: { pending: 0, running: 0, completed: 0, failed: 0 },
              runs: [],
            },
          },
        },
      });
      expect(observedZero.get('[data-testid="processing-count-pending"]').text()).toBe('0');
      expect(observedZero.get('[data-testid="processing-count-running"]').text()).toBe('0');
      expect(observedZero.get('[data-testid="processing-count-completed"]').text()).toBe('0');
      expect(observedZero.get('[data-testid="processing-count-failed"]').text()).toBe('0');
    });

    it('distinguishes unavailable vs observed empty vs observed nonempty for runs and outbox', () => {
      const unavailableWrapper = mount(ProcessingSurface, {
        props: { state: {} },
      });
      expect(unavailableWrapper.text()).toContain('غير متاح — لم تتم ملاحظة سجل المعالجات');
      expect(unavailableWrapper.text()).toContain('غير متاح — لم تتم ملاحظة رسائل Outbox');

      const emptyWrapper = mount(ProcessingSurface, {
        props: {
          state: {
            processing: { counts: {}, runs: [] },
            outbox: { counts: {}, messages: [] },
          },
        },
      });
      expect(emptyWrapper.text()).toContain('لا توجد معالجات تشغيلية مسجلة حالياً');
      expect(emptyWrapper.text()).toContain('طابور Outbox خالٍ من الرسائل المعلقة');
    });
  });

  describe('ValidationSurface & ValidationContext', () => {
    it('renders source import upload form and package records', async () => {
      const wrapper = mount(ValidationSurface, {
        props: {
          state: {
            packages: {
              counts: { exported: 2, rejected: 1 },
              records: [
                {
                  id: 'pkg-01',
                  package_type: 'knowledge-package',
                  schema_version: 1,
                  owner_module: 'MOD-KNW',
                  package_digest: 'e'.repeat(64),
                  status: 'exported',
                  created_at: '2026-08-20T12:00:00Z',
                },
              ],
            },
            source_imports: {
              counts: {},
              records: [
                {
                  id: 'src-01',
                  original_name: 'syllabus.pdf',
                  detected_media_type: 'application/pdf',
                  extension: 'pdf',
                  size_bytes: 1048576,
                  sha256: 'a'.repeat(64),
                  status: 'accepted',
                  rejection_code: null,
                  created_at: '2026-08-20T12:00:00Z',
                },
              ],
            },
          },
        },
      });

      expect(wrapper.text()).toContain('استيراد ملف مصدري للتحقق');
      expect(wrapper.find('input[type="file"]').exists()).toBe(true);
      expect(wrapper.text()).toContain('knowledge-package');
      expect(wrapper.text()).toContain('syllabus.pdf');
      expect(wrapper.text()).toContain('1 MB');

      await wrapper.find('.package-actions button').trigger('click');
      expect(wrapper.emitted('openDeep')).toBeTruthy();
    });

    it('renders ValidationContext asserting technical scope boundary', () => {
      const wrapper = mount(ValidationContext, {
        props: { state: {} },
      });

      expect(wrapper.text()).toContain('حدود التحقق التقني');
      expect(wrapper.text()).toContain('فحص تقني فقط');
      expect(wrapper.text()).toContain('حراسة المصادر');
    });

    it('distinguishes unavailable validation counts from observed zero', () => {
      const unavailable = mount(ValidationSurface, {
        props: { state: { packages: { records: [] } } },
      });
      expect(unavailable.get('[data-testid="validation-count-accepted"]').text()).toBe('—');
      expect(unavailable.get('[data-testid="validation-count-rejected"]').text()).toBe('—');

      const observedZero = mount(ValidationSurface, {
        props: {
          state: {
            packages: { counts: { exported: 0, valid: 0, rejected: 0 }, records: [] },
          },
        },
      });
      expect(observedZero.get('[data-testid="validation-count-accepted"]').text()).toBe('0');
      expect(observedZero.get('[data-testid="validation-count-rejected"]').text()).toBe('0');
    });

    it('distinguishes unavailable vs observed empty packages and source imports and verifies fixed accessible label', () => {
      const unavailableWrapper = mount(ValidationSurface, {
        props: { state: {} },
      });
      expect(unavailableWrapper.text()).toContain('غير متاح — لم تتم ملاحظة سجل الحزم المحمولة');
      expect(unavailableWrapper.text()).toContain('غير متاح — لم تتم ملاحظة سجل استيراد المصادر');
      expect(unavailableWrapper.find('input[type="file"]').attributes('aria-label')).toBe(
        'اختر ملفاً مصدرياً للتحقق',
      );

      const emptyWrapper = mount(ValidationSurface, {
        props: {
          state: {
            packages: { records: [] },
            source_imports: { counts: {}, records: [] },
          },
        },
      });
      expect(emptyWrapper.text()).toContain('لا توجد حزم محمولة مسجلة للتحقق');
      expect(emptyWrapper.text()).toContain('لا توجد ملفات مصدرية مسجلة');
    });
  });

  describe('AiBridgeSurface & AiBridgeContext (Truthful Policy Governance)', () => {
    it('requires human rationale before enabling accept or reject decisions', async () => {
      const wrapper = mount(AiBridgeSurface, {
        props: {
          state: {
            policy: { execution: 'MANUAL_ONLY' },
            results: [
              {
                id: 'ai-res-1',
                prompt_package_revision_id: 'rev-1',
                portable_package_id: 'pkg-1',
                result_digest: 'f'.repeat(64),
                structured_result: { blocks: ['Sample lesson block'] },
                status: 'pending_review',
                imported_at: '2026-08-20T14:00:00Z',
                prompt_package_id: 'prompt-1',
                prompt_revision: 1,
                prompt_input_digest: '1'.repeat(64),
                declared_scope: {},
                prompt_portable_package_id: 'pkg-1',
                prompt_purpose: 'Generate Practice',
                prompt_status: 'exported',
                returned_package_type: 'manual-ai-result',
                returned_package_digest: '2'.repeat(64),
                returned_package_scope: {},
                returned_package_manifest: {},
                returned_package_status: 'exported',
              },
            ],
          },
        },
      });

      const acceptBtn = wrapper.find('button.accept-button');
      const rejectBtn = wrapper.find('button.danger-button');
      expect(acceptBtn.attributes('disabled')).toBeDefined();
      expect(rejectBtn.attributes('disabled')).toBeDefined();

      const textarea = wrapper.find('textarea[id="rationale-ai-res-1"]');
      await textarea.setValue('Approved after expert pedagogy review.');

      expect(wrapper.find('button.accept-button').attributes('disabled')).toBeUndefined();
      expect(wrapper.find('button.danger-button').attributes('disabled')).toBeUndefined();
    });

    it('proves AiBridgeContext responds truthfully when automatic_provider_enabled changes and has no unconditional guarantees', () => {
      // Disabled state
      const disabledWrapper = mount(AiBridgeContext, {
        props: {
          state: {
            policy: {
              execution: 'MANUAL_ONLY',
              automatic_provider_enabled: false,
              automatic_publish: false,
              polling: false,
              embeddings: false,
            },
          },
        },
      });

      const disabledText = disabledWrapper.text();
      expect(disabledText).toContain('نمط التنفيذ: MANUAL_ONLY');
      expect(disabledText).toContain('المزود الشبكي: معطّل (Off)');
      expect(disabledText).toContain('automatic_provider_enabled: false');
      expect(disabledText).toContain('automatic_publish: false');
      expect(disabledText).toContain('الاستطلاع التلقائي (Polling): معطّل');
      expect(disabledText).toContain('توليد التضمينات (Embeddings): معطّل');

      // Verify no unconditional global guarantees remain
      expect(disabledText).not.toContain('لا تتصل المنصة بأي مزود سحابي أو محلي تلقائياً');
      expect(disabledText).not.toContain('يضمن التصميم المعزول عدم تسريب');

      // Enabled state
      const enabledWrapper = mount(AiBridgeContext, {
        props: {
          state: {
            policy: {
              execution: 'HYBRID',
              automatic_provider_enabled: true,
              automatic_publish: true,
              polling: true,
              embeddings: true,
            },
          },
        },
      });

      const enabledText = enabledWrapper.text();
      expect(enabledText).toContain('نمط التنفيذ: HYBRID');
      expect(enabledText).toContain('المزود الشبكي: مفعّل في الإعدادات');
      expect(enabledText).toContain('automatic_provider_enabled: true');
      expect(enabledText).toContain('automatic_publish: true');
      expect(enabledText).toContain('الاستطلاع التلقائي (Polling): مفعّل');
      expect(enabledText).toContain('توليد التضمينات (Embeddings): مفعّل');
    });

    it('renders unobserved AI policy as unavailable without claiming verified compliance', () => {
      const unobservedContext = mount(AiBridgeContext, {
        props: { state: {} },
      });

      expect(unobservedContext.text()).toContain('نمط التنفيذ: غير متاح');
      expect(unobservedContext.text()).toContain('المزود الشبكي: غير متاح');
      expect(unobservedContext.text()).toContain('بوابة القرار البشري: غير متاح');
      expect(unobservedContext.text()).toContain('الاستطلاع التلقائي (Polling): غير متاح');
      expect(unobservedContext.text()).toContain('توليد التضمينات (Embeddings): غير متاح');
      expect(unobservedContext.text()).not.toContain('automatic_provider_enabled: false');
      expect(unobservedContext.text()).not.toContain('automatic_publish: false');
    });

    it('surfaces policy violation when runtime configuration conflicts with governing manual-only policy', () => {
      const violationContext = mount(AiBridgeContext, {
        props: {
          state: {
            policy: {
              execution: 'HYBRID',
              automatic_provider_enabled: true,
              automatic_publish: true,
            },
          },
        },
      });

      expect(violationContext.text()).toContain('انتهاك لسياسة الحوكمة');
    });

    it('distinguishes unavailable vs observed empty AI results and prompt revisions', () => {
      const unavailableSurface = mount(AiBridgeSurface, {
        props: { state: {} },
      });
      expect(unavailableSurface.text()).toContain(
        'غير متاح — لم تتم ملاحظة نتائج الذكاء الاصطناعي',
      );
      expect(unavailableSurface.text()).toContain('غير متاح — لم تتم ملاحظة مراجعات الموجهات');

      const emptySurface = mount(AiBridgeSurface, {
        props: {
          state: {
            results: [],
            prompt_revisions: [],
          },
        },
      });
      expect(emptySurface.text()).toContain('لا توجد نتائج ذكاء اصطناعي بانتظار المراجعة');
      expect(emptySurface.text()).toContain('لا توجد مراجعات موجهات سابقة');
    });
  });

  describe('BackupsSurface & BackupsContext', () => {
    it('maintains closed danger-zone and displays backup manifests and staged restore history', () => {
      const wrapper = mount(BackupsSurface, {
        props: {
          state: {
            backups: [
              {
                id: 'backup-20260825',
                portable_package_id: 'pkg-backup-1',
                status: 'completed',
                database_driver: 'pgsql',
                content_digest: '3'.repeat(64),
                created_at: '2026-08-25T01:00:00Z',
              },
            ],
            restores: [
              {
                id: 'restore-1',
                backup_manifest_id: 'backup-20260825',
                target_database: 'cep_stage_verify',
                status: 'verified',
                verification: { tables_checked: 15, valid: true },
                started_at: '2026-08-25T02:00:00Z',
                completed_at: '2026-08-25T02:01:00Z',
              },
            ],
            safety: {
              web_restore_mode: 'STAGE_AND_VERIFY_ONLY',
              activation_route_available: false,
            },
          },
        },
      });

      expect(wrapper.text()).toContain('backup-20260825');
      expect(wrapper.text()).toContain('Driver: pgsql');
      expect(wrapper.find('details.danger-zone').attributes('open')).toBeUndefined();
      expect(wrapper.text()).toContain('cep_stage_verify');
    });

    it('renders BackupsContext with isolated staging and CLI activation rules', () => {
      const wrapper = mount(BackupsContext, {
        props: { state: {} },
      });

      expect(wrapper.text()).toContain('سياسة الاستعادة الآمنة');
      expect(wrapper.text()).toContain('الفحص المرحلي المعزول');
      expect(wrapper.text()).toContain('التحقق من البصمات');
      expect(wrapper.text()).toContain('التفعيل عبر CLI فقط');
    });

    it('distinguishes unavailable backups and restores from observed empty', () => {
      const unavailableSurface = mount(BackupsSurface, {
        props: { state: {} },
      });
      expect(unavailableSurface.text()).toContain('غير متاح — لم تتم ملاحظة سجل النسخ الاحتياطية');

      const emptySurface = mount(BackupsSurface, {
        props: {
          state: {
            backups: [],
            restores: [],
          },
        },
      });
      expect(emptySurface.text()).toContain('لم تسجل نسخ احتياطية في السجل المرصود');
    });
  });

  describe('AuditSurface & AuditContext', () => {
    it('renders audit hash-chain indicator and sequence log rows', async () => {
      const wrapper = mount(AuditSurface, {
        props: {
          state: {
            chain: { valid: true, count: 42 },
            records: [
              {
                id: 'aud-42',
                sequence_no: 42,
                actor_identifier: 'admin-1',
                action: 'backup.created',
                target_type: 'backup_manifest',
                target_identifier: 'backup-42',
                correlation_id: 'corr-backup-42',
                outcome: 'success',
                safe_metadata: { size: 4096 },
                occurred_at: '2026-08-25T03:00:00Z',
                previous_hash: '4'.repeat(64),
                record_hash: '5'.repeat(64),
              },
            ],
          },
        },
      });

      expect(wrapper.text()).toContain('VALID_CHAIN');
      expect(wrapper.text()).toContain('42 سجلات موثقة');
      expect(wrapper.text()).toContain('#42');
      expect(wrapper.text()).toContain('backup.created');
      expect(wrapper.text()).toContain('admin-1');

      await wrapper.find('.btn-inspect').trigger('click');
      expect(wrapper.emitted('openDeep')).toBeTruthy();
    });

    it('renders AuditContext with append-only and hash-chain invariants', () => {
      const wrapper = mount(AuditContext, {
        props: { state: {} },
      });

      expect(wrapper.text()).toContain('سلسلة النزاهة المشفرة');
      expect(wrapper.text()).toContain('سجل إضافة فقط (Append-Only)');
      expect(wrapper.text()).toContain('ترابط التجزئة (Hash Chaining)');
    });

    it('renders unobserved audit hash-chain as UNAVAILABLE with unobserved count instead of CHAIN_INVALID or 0', () => {
      const unavailableWrapper = mount(AuditSurface, {
        props: { state: {} },
      });
      expect(unavailableWrapper.text()).toContain('UNAVAILABLE');
      expect(unavailableWrapper.text()).toContain('(غير متاح)');
      expect(unavailableWrapper.text()).not.toContain('CHAIN_INVALID');
      expect(unavailableWrapper.text()).not.toContain('(0 سجلات موثقة)');
      expect(unavailableWrapper.text()).toContain('غير متاح — لم تتم ملاحظة سجل الأحداث التشغيلية');

      const emptyWrapper = mount(AuditSurface, {
        props: {
          state: {
            chain: { valid: true, count: 0 },
            records: [],
          },
        },
      });
      expect(emptyWrapper.text()).toContain('VALID_CHAIN');
      expect(emptyWrapper.text()).toContain('(0 سجلات موثقة)');
      expect(emptyWrapper.text()).toContain('لا توجد سجلات تدقيق مسجلة');
    });
  });

  describe('ReleasesSurface & ReleasesContext', () => {
    it('renders release checks, evidence packages, and download triggers', () => {
      const wrapper = mount(ReleasesSurface, {
        props: {
          state: {
            readiness: {
              ready: true,
              checks: {
                migrations: { status: 'ok', detail: 'All migrations current.' },
                tests: { status: 'ok', detail: 'All regression gates passing.' },
              },
            },
            packages: [
              {
                id: 'rel-pkg-1',
                package_type: 'release-manifest',
                owner_module: 'MOD-PLT',
                package_digest: '6'.repeat(64),
                status: 'exported',
                created_at: '2026-08-25T04:00:00Z',
              },
            ],
          },
        },
      });

      expect(wrapper.text()).toContain('READY');
      expect(wrapper.text()).toContain('migrations');
      expect(wrapper.text()).toContain('All migrations current.');
      expect(wrapper.text()).toContain('rel-pkg-1');
      expect(wrapper.find('a.btn-download').attributes('href')).toBe('/system/packages/rel-pkg-1');
    });

    it('renders ReleasesContext with non-deployment boundary statement', () => {
      const wrapper = mount(ReleasesContext, {
        props: { state: {} },
      });

      expect(wrapper.text()).toContain('ضوابط جاهزية الإصدار');
      expect(wrapper.text()).toContain('حظر الإطلاق التلقائي');
      expect(wrapper.text()).toContain('حزم الأدلة الموثقة');
    });

    it('does not turn ready=false into an observed closed release gate without checks', () => {
      const unavailable = mount(ReleasesSurface, {
        props: { state: { readiness: { ready: false, checks: {} } } },
      });
      expect(unavailable.text()).toContain('UNAVAILABLE');
      expect(unavailable.text()).not.toContain('NOT_READY');

      const observedClosed = mount(ReleasesSurface, {
        props: {
          state: {
            readiness: {
              ready: false,
              checks: { migrations: { status: 'failed', detail: 'Pending migration.' } },
            },
          },
        },
      });
      expect(observedClosed.text()).toContain('NOT_READY');
      expect(observedClosed.text()).toContain('Pending migration.');
    });

    it('distinguishes unavailable release packages from observed empty', () => {
      const unavailableWrapper = mount(ReleasesSurface, {
        props: { state: {} },
      });
      expect(unavailableWrapper.text()).toContain('غير متاح — لم تتم ملاحظة حزم الأدلة');

      const emptyWrapper = mount(ReleasesSurface, {
        props: { state: { packages: [] } },
      });
      expect(emptyWrapper.text()).toContain('لا توجد حزم أدلة إصدار مسجلة');
    });
  });

  describe('ConfigurationSurface & ConfigurationContext', () => {
    it('renders read-only whitelist and operational limits without any form tag', () => {
      const wrapper = mount(ConfigurationSurface, {
        props: {
          state: {
            profile: 'local',
            queue_connection: 'database',
            blob_disk: 'local',
            release_loopback_only: true,
            ai_network_provider_enabled: false,
            force_https: false,
            limits: {
              source_import_max_bytes: 10485760,
              manual_ai_result_max_bytes: 5242880,
              audit_metadata_max_bytes: 65536,
              outbox_payload_max_bytes: 262144,
            },
          },
        },
      });

      expect(wrapper.find('form').exists()).toBe(false);
      expect(wrapper.text()).toContain('READ_ONLY_WHITELIST');
      expect(wrapper.text()).toContain('local');
      expect(wrapper.text()).toContain('database');
      expect(wrapper.text()).toContain('10 MB');
      expect(wrapper.text()).toContain('5 MB');
    });

    it('renders ConfigurationContext with secret protection and immutability', () => {
      const wrapper = mount(ConfigurationContext, {
        props: { state: {} },
      });

      expect(wrapper.text()).toContain('ضوابط أمان التهيئة');
      expect(wrapper.text()).toContain('قائمة بيضاء معتمدة');
      expect(wrapper.text()).toContain('حماية الأسرار والمفاتيح');
      expect(wrapper.text()).toContain('انضباط السقوف التشغيلية');
    });

    it('renders unobserved profile, queue, blob disk, and booleans as UNAVAILABLE or — without defaulting', () => {
      const wrapper = mount(ConfigurationSurface, {
        props: { state: {} },
      });

      expect(wrapper.text()).not.toContain('local');
      expect(wrapper.text()).not.toContain('database');
      expect(wrapper.findAll('.param-value').some((el) => el.text().includes('—'))).toBe(true);
      expect(wrapper.findAll('.state-pill').length).toBe(3);
      wrapper.findAll('.state-pill').forEach((pill) => {
        expect(pill.text()).toContain('UNAVAILABLE');
        expect(pill.classes()).toContain('state-pill--neutral');
      });
    });
  });

  describe('Root Workspace Surface Switching', () => {
    it('renders the appropriate surface component and context panel for each surface prop', () => {
      const surfaces = [
        'health',
        'processing',
        'validation',
        'ai-bridge',
        'backups',
        'audit',
        'releases',
        'configuration',
      ] as const;

      for (const surface of surfaces) {
        const wrapper = mount(Workspace, {
          props: {
            surface,
            state: {
              foundation: { checks: {}, healthy: true, failed_checks: [] },
              processing: { counts: {} },
              outbox: { counts: {} },
              packages: { counts: {}, records: [] },
              readiness: { ready: true, checks: {} },
              chain: { valid: true, count: 0 },
            },
          },
        });

        expect(wrapper.find('.system-surface-container').exists()).toBe(true);
        expect(wrapper.find('.rail-link.active').exists()).toBe(true);
      }
    });
  });
});
