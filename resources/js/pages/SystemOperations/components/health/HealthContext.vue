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

const packageCounts = computed<Counts>(() => {
  if (Array.isArray(props.state.packages)) {
    const counts: Counts = {};
    for (const p of props.state.packages) {
      counts[p.status] = (counts[p.status] ?? 0) + 1;
    }
    return counts;
  }
  return props.state.packages?.counts ?? {};
});

const activeSubsystem = computed<SubsystemKey>(() => props.selectedSubsystem ?? 'validation');

const failedChecks = computed<string[]>(() => props.state.foundation?.failed_checks ?? []);
const totalFoundationChecks = computed(
  () => Object.keys(props.state.foundation?.checks ?? {}).length,
);
const failedProcessing = computed(() => count(props.state.processing?.counts, 'failed'));
const rejectedPackages = computed(() => count(packageCounts.value, 'rejected'));

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
    props.state.release_gate &&
    !props.state.release_gate.ready
  ) {
    return {
      hasIncident: false,
      heading: 'تأثير حالة الجاهزية',
      body: 'بوابة الإصدار مغلقة وتمنع النشر التلقائي حتى استيفاء كامل فحوص الجاهزية.',
    };
  }

  return {
    hasIncident: false,
    heading: 'التأثير التشغيلي',
    body: 'لم يتم رصد أي حجب أو تعطل تشغيلي في السجل الحالي؛ الفحوص المسجلة للنظام في حالة طبيعية.',
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

  if (props.state.release_gate?.checks && Object.keys(props.state.release_gate.checks).length > 0) {
    return {
      heading: 'آخر سياق تحقق',
      body: `فحوص الجاهزية: ${props.state.release_gate.ready ? 'مستوفاة بالكامل' : 'غير مكتملة'}.`,
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
      <div class="context-block__icon" aria-hidden="true">
        {{ impactInfo.hasIncident ? '🚨' : 'ℹ️' }}
      </div>
      <div class="context-block__content">
        <h4 class="context-block__heading">{{ impactInfo.heading }}</h4>
        <p class="context-block__body">{{ impactInfo.body }}</p>
      </div>
    </article>

    <!-- Dependencies Block -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">📁</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">{{ dependenciesInfo.heading }}</h4>
        <p class="context-block__body">{{ dependenciesInfo.body }}</p>
      </div>
    </article>

    <!-- Last Validation Context Block -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">⏱️</div>
      <div class="context-block__content">
        <h4 class="context-block__heading">{{ validationContextInfo.heading }}</h4>
        <p class="context-block__body">{{ validationContextInfo.body }}</p>
      </div>
    </article>

    <!-- Configuration Scope Block -->
    <article class="context-block">
      <div class="context-block__icon" aria-hidden="true">⚙️</div>
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
  gap: 1rem;
}

.context-header {
  padding-bottom: 0.65rem;
  border-bottom: 1px solid var(--cep-border);
}

.context-block {
  display: flex;
  gap: 0.75rem;
  padding: 0.85rem;
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  border: 1px solid var(--cep-border);
}

.context-block--impact {
  border-color: rgba(239, 68, 68, 0.3);
  background: rgba(239, 68, 68, 0.05);
}

.context-block__icon {
  font-size: 1.2rem;
  flex-shrink: 0;
  line-height: 1.2;
}

.context-block__content {
  flex: 1;
}

.context-block__heading {
  margin: 0 0 0.25rem;
  font-size: 0.88rem;
  font-weight: 750;
  color: var(--cep-text);
}

.context-block__body {
  margin: 0;
  font-size: 0.8rem;
  color: var(--cep-text-muted);
  line-height: 1.55;
}
</style>
