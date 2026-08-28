<script setup lang="ts">
import { computed } from 'vue';

import type { Counts, WorkspaceState } from '../../types';

type SubsystemKey = 'validation' | 'processing' | 'ai-bridge' | 'backups' | 'releases';

const props = withDefaults(
  defineProps<{
    state: WorkspaceState;
    selectedSubsystem?: SubsystemKey;
  }>(),
  {
    selectedSubsystem: 'validation',
  },
);

const count = (counts: Counts | undefined, key: string): number => counts?.[key] ?? 0;

const packageCounts = computed<Counts | undefined>(() => {
  if (Array.isArray(props.state.packages)) {
    const counts: Counts = {};
    for (const p of props.state.packages) {
      counts[p.status] = (counts[p.status] ?? 0) + 1;
    }
    return counts;
  }
  return props.state.packages?.counts;
});

const activeSubsystem = computed<SubsystemKey>(() => props.selectedSubsystem ?? 'validation');

const failedChecks = computed<string[]>(() => props.state.foundation?.failed_checks ?? []);
const totalFoundationChecks = computed(
  () => Object.keys(props.state.foundation?.checks ?? {}).length,
);
const failedProcessing = computed(() => count(props.state.processing?.counts, 'failed'));
const rejectedPackages = computed(() => count(packageCounts.value, 'rejected'));
const hasKeys = (counts: Counts | undefined): boolean =>
  Boolean(counts && Object.keys(counts).length > 0);
const processingObserved = computed(() => hasKeys(props.state.processing?.counts));
const packagesObserved = computed(() => hasKeys(packageCounts.value));
const releaseGate = computed(() => props.state.release_gate ?? props.state.readiness);
const releaseHasChecks = computed(() =>
  Boolean(releaseGate.value?.checks && Object.keys(releaseGate.value.checks).length > 0),
);

const selectedObservationSummary = computed<string | null>(() => {
  switch (activeSubsystem.value) {
    case 'validation':
      return packagesObserved.value
        ? 'لم يتم رصد حزم مرفوضة في بيانات التحقق المتاحة للمكوّن المحدد.'
        : null;
    case 'processing':
      return processingObserved.value
        ? 'لم يتم رصد مهام معالجة فاشلة في بيانات الطابور المتاحة للمكوّن المحدد.'
        : null;
    case 'releases':
      return releaseHasChecks.value && releaseGate.value?.ready === true
        ? 'فحوص الجاهزية المرصودة للمكوّن المحدد مستوفاة بالكامل.'
        : null;
    case 'backups': {
      if (props.state.backups === undefined) return null;
      if (props.state.backups.length === 0) {
        return 'تمت ملاحظة سجل النسخ الاحتياطي دون وجود نسخ؛ لا يمكن استنتاج صحة النسخ من توفر التخزين العام.';
      }
      const failed = props.state.backups.filter((backup) => backup.status === 'failed').length;
      const verified = props.state.backups.filter((backup) => backup.status === 'verified').length;
      if (failed === 0 && verified === props.state.backups.length) {
        return `تمت ملاحظة ${verified} نسخة احتياطية بحالة verified للمكوّن المحدد.`;
      }
      return 'بيانات النسخ الاحتياطي متاحة، لكن حالتها لا تثبت سلامة جميع النسخ المسجلة.';
    }
    case 'ai-bridge':
      return props.state.policy?.execution ||
        props.state.prompts !== undefined ||
        props.state.results !== undefined
        ? 'تمت ملاحظة بيانات تشغيل الجسر للمكوّن المحدد؛ لا تمثل هذه الملاحظة فحص صحة تشغيلياً.'
        : null;
    default:
      return null;
  }
});

// 1. Contextual Impact Block
const impactInfo = computed(() => {
  if (failedChecks.value.length > 0) {
    return {
      hasIncident: true,
      heading: 'تأثير الإخفاقات الأساسية',
      body: `توجد ${failedChecks.value.length} فحوص أساسية فاشلة (${failedChecks.value.join(', ')}). قد تتأثر موثوقية العمليات حتى تصحيح الخلل.`,
    };
  }

  if (activeSubsystem.value === 'processing' && failedProcessing.value > 0) {
    return {
      hasIncident: true,
      heading: 'تأثير فشل المعالجة',
      body: `توجد ${failedProcessing.value} مهام معالجة فاشلة في الطابور تتطلب مراجعة سجلات الأخطاء.`,
    };
  }

  if (activeSubsystem.value === 'validation' && rejectedPackages.value > 0) {
    return {
      hasIncident: true,
      heading: 'تأثير رفض الحزم',
      body: `توجد ${rejectedPackages.value} حزم مرفوضة في مرحلة التحقق الفني لم يتم اعتمادها كحزم جاهزة.`,
    };
  }

  if (
    activeSubsystem.value === 'releases' &&
    releaseHasChecks.value &&
    releaseGate.value?.ready === false
  ) {
    return {
      hasIncident: false,
      heading: 'تأثير حالة الجاهزية',
      body: 'بوابة الإصدار مغلقة وتمنع النشر التلقائي حتى استيفاء كامل فحوص الجاهزية.',
    };
  }

  if (selectedObservationSummary.value) {
    return {
      hasIncident: false,
      heading: 'التأثير التشغيلي',
      body: selectedObservationSummary.value,
    };
  }

  return {
    hasIncident: false,
    heading: 'التأثير التشغيلي',
    body: 'غير متاح — لم تتم ملاحظة أية فحوص تشغيلية أو بيانات في السجل الحالي.',
  };
});

// 2. Dependencies Block
const dependenciesInfo = computed(() => {
  switch (activeSubsystem.value) {
    case 'validation':
      return {
        heading: 'اعتمادات التحقق',
        body: 'تعتمد خدمة التحقق على سلامة مخططات JSON ومطابقة بصمات SHA-256 للحزم المستوردة.',
      };
    case 'processing':
      return {
        heading: 'اعتمادات المعالجة',
        body: 'تعتمد المعالجة على محرك طوابير PostgreSQL وجداول المهام والرسائل الخارجة (Outbox).',
      };
    case 'ai-bridge':
      return {
        heading: 'اعتمادات جسر AI',
        body: 'يعتمد جسر AI على التبادل اليدوي لملفات JSON مع اشتراط مراجعة المشغل البشري.',
      };
    case 'backups':
      return {
        heading: 'اعتمادات النسخ الاحتياطي',
        body: 'يعتمد النسخ الاحتياطي على سلامة التخزين المحلي وأدوات قواعد البيانات للتحقق المرحلي.',
      };
    case 'releases':
      return {
        heading: 'اعتمادات الإصدار',
        body: 'يعتمد الإصدار على اجتياز فحوص الترحيل وحزم الأدلة واختبارات الجاهزية المسبقة.',
      };
    default:
      return {
        heading: 'اعتمادات المنصة',
        body: 'ترابط خدمات المنصة الأساسية (قاعدة البيانات، الطوابير، التخزين، والمخططات).',
      };
  }
});

// 3. Last Validation Context Block
const validationContextInfo = computed(() => {
  if (totalFoundationChecks.value > 0) {
    return {
      heading: 'آخر سياق تحقق',
      body: `تم فحص ${totalFoundationChecks.value} مكونات أساسية بنتيجة: ${props.state.foundation?.healthy ? 'اجتياز كامل (سليم)' : 'توجد فحوص تتطلب الانتباه'}.`,
    };
  }

  if (releaseHasChecks.value) {
    return {
      heading: 'آخر سياق تحقق',
      body: `فحوص الجاهزية: ${releaseGate.value?.ready ? 'مستوفاة بالكامل' : 'غير مكتملة'}.`,
    };
  }

  return {
    heading: 'آخر سياق تحقق',
    body: 'غير متاح — لم يُسجل سياق تحقق حديث في البيانات الحالية.',
  };
});

// 4. Configuration Scope Block
const configScopeInfo = computed(() => {
  if (props.state.foundation?.checks?.configuration !== undefined) {
    return {
      heading: 'نطاق التكوين',
      body: `حالة تكوين البيئة: ${props.state.foundation.checks.configuration === 'ok' ? 'مطابقة للسياسة المعتمدة' : 'تتطلب مراجعة'}.`,
    };
  }

  if (props.state.profile) {
    return {
      heading: 'نطاق التكوين',
      body: `ملف التشغيل المعتمد: ${props.state.profile}.`,
    };
  }

  return {
    heading: 'نطاق التكوين',
    body: 'تخضع عمليات النظام للسياسات التشغيلية المعتمدة وحدود الموارد المحددة.',
  };
});
</script>

<template>
  <div class="health-context cep-context-stack">
    <div class="context-header">
      <span class="cep-kicker">سياق الحالة</span>
      <h3 class="cep-context-title">السياق التشغيلي</h3>
    </div>

    <!-- Impact Block -->
    <article class="context-block" :class="{ 'context-block--impact': impactInfo.hasIncident }">
      <div
        class="context-block__icon-box"
        :class="impactInfo.hasIncident ? 'icon-box--danger' : 'icon-box--info'"
        aria-hidden="true"
      >
        {{ impactInfo.hasIncident ? '🚨' : 'ℹ️' }}
      </div>
      <div class="context-block__content">
        <h4 class="context-block__heading">{{ impactInfo.heading }}</h4>
        <p class="context-block__body">{{ impactInfo.body }}</p>
      </div>
    </article>

    <!-- Dependencies Block -->
    <article class="context-block">
      <div class="context-block__icon-box icon-box--warning" aria-hidden="true">📁</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">{{ dependenciesInfo.heading }}</h4>
        <p class="context-block__body">{{ dependenciesInfo.body }}</p>
      </div>
    </article>

    <!-- Last Validation Context Block -->
    <article class="context-block">
      <div class="context-block__icon-box icon-box--accent" aria-hidden="true">⏱️</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">{{ validationContextInfo.heading }}</h4>
        <p class="context-block__body">{{ validationContextInfo.body }}</p>
      </div>
    </article>

    <!-- Configuration Scope Block -->
    <article class="context-block">
      <div class="context-block__icon-box icon-box--neutral" aria-hidden="true">⚙️</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">{{ configScopeInfo.heading }}</h4>
        <p class="context-block__body">{{ configScopeInfo.body }}</p>
      </div>
    </article>
  </div>
</template>

<style scoped>
.health-context {
  display: grid;
  gap: 0.85rem;
}

.context-header {
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--cep-border);
}

.context-block {
  display: flex;
  gap: 0.85rem;
  padding: 0.95rem 1rem;
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: all 140ms ease;
}

.context-block:hover {
  border-color: var(--cep-border-strong);
}

.context-block--impact {
  border-color: rgba(239, 68, 68, 0.35);
  background: rgba(239, 68, 68, 0.06);
}

.context-block__icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.35rem;
  height: 2.35rem;
  border-radius: var(--cep-radius-sm);
  font-size: 1.15rem;
  flex-shrink: 0;
  border: 1px solid transparent;
}

.icon-box--danger {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.35);
}

.icon-box--info {
  background: rgba(34, 211, 238, 0.12);
  border-color: rgba(34, 211, 238, 0.3);
}

.icon-box--warning {
  background: rgba(245, 158, 11, 0.12);
  border-color: rgba(245, 158, 11, 0.3);
}

.icon-box--accent {
  background: rgba(168, 85, 247, 0.12);
  border-color: rgba(168, 85, 247, 0.3);
}

.icon-box--neutral {
  background: rgba(148, 163, 184, 0.12);
  border-color: rgba(148, 163, 184, 0.25);
}

.context-block__content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.context-block__heading {
  margin: 0;
  font-size: 0.88rem;
  font-weight: 750;
  color: var(--cep-text);
  letter-spacing: -0.01em;
}

.context-block__body {
  margin: 0;
  font-size: 0.82rem;
  color: var(--cep-text-muted);
  line-height: 1.55;
}
</style>
