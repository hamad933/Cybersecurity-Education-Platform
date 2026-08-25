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

    const mockTrueEmptyState = {
      foundation: { checks: {}, healthy: false, failed_checks: [] },
      processing: { counts: {} },
      outbox: { counts: {} },
      packages: { counts: {}, records: [] },
      release_gate: { ready: false, checks: {} },
      // no policy, no results, no backups
    };

    it('proves observed zero state renders healthy/compliant and unblocked claims', async () => {
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
      expect(wrapper.text()).toContain('وحدة التخزين متاحة'); // Backups observed

      const contextWrapper = mount(HealthContext, {
        props: { state: mockObservedZeroState, selectedSubsystem: 'validation' },
      });
      expect(contextWrapper.text()).toContain('الفحوص المسجلة للنظام في حالة طبيعية');
    });

    it('proves true empty state rejects healthy wording and strictly reports unavailable/unobserved', () => {
      const wrapper = mount(HealthSurface, {
        props: { state: mockTrueEmptyState },
      });

      const text = wrapper.text();
      // Must not claim compliance without observation
      expect(text).not.toContain('جميع الحزم المسجلة مطابقة');
      expect(text).not.toContain('الطابور في حالة طبيعية');
      expect(text).not.toContain('لا توجد مهام نشطة');

      expect(text).toContain('لم تتم ملاحظته');
      expect(text).toContain('غير متاح');
      expect(text).toContain('بيانات المكوّن المحدد غير متوفرة');

      const contextWrapper = mount(HealthContext, {
        props: { state: mockTrueEmptyState, selectedSubsystem: 'validation' },
      });
      expect(contextWrapper.text()).toContain('غير متاح — لم تتم ملاحظة أية فحوص تشغيلية');
      expect(contextWrapper.text()).not.toContain('حالة طبيعية');
    });

    it('proves projected failures and counts drive truthful UI reactively', async () => {
      const mockFailureState = {
        foundation: {
          checks: { database: 'failed', queue: 'ok', storage: 'ok' },
          healthy: false,
          failed_checks: ['database'],
        },
        processing: { counts: { running: 1, pending: 2, failed: 4 } },
        outbox: { counts: { failed: 1 } },
        packages: { counts: { rejected: 3, exported: 8 }, records: [] },
        release_gate: { ready: false, checks: {} },
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
