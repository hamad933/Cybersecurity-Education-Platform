<script setup lang="ts">
import type { EnterpriseItem } from '../types';

defineProps<{ enterprise: EnterpriseItem | null }>();
const emit = defineEmits<{ openDeepDetail: [] }>();
</script>

<template>
  <section class="sim-surface" data-testid="enterprise-center">
    <header class="sim-surface-header">
      <div>
        <p class="sim-kicker">ENTERPRISE · DIGITAL TWIN</p>
        <h1>{{ enterprise?.name_ar ?? 'المؤسسة والنسخة الرقمية' }}</h1>
        <p>
          استكشف تعريف المؤسسة وسلسلة النسخ الرقمية المنشورة من دون خلطها بحالة التشغيل المتغيرة.
        </p>
      </div>
      <button
        v-if="enterprise"
        type="button"
        class="sim-button sim-button--quiet"
        @click="emit('openDeepDetail')"
      >
        فتح البنية الخام
      </button>
    </header>

    <div v-if="!enterprise" class="sim-empty">
      <strong>لا توجد مؤسسة محددة</strong>
      <p>اختر مؤسسة منشورة من لوحة البنية.</p>
    </div>

    <div v-else class="sim-twin-grid">
      <article
        v-for="twin in enterprise.digital_twins"
        :key="twin.id"
        class="sim-card sim-twin-card"
      >
        <div class="sim-card__topline">
          <span class="sim-chip">DIGITAL TWIN</span>
          <span v-if="twin.is_fixture" class="sim-chip sim-chip--fixture">FIXTURE</span>
        </div>
        <h2>{{ twin.name_ar }}</h2>
        <code class="sim-technical">{{ twin.slug }}</code>
        <div class="sim-metric-row">
          <div>
            <small>المراجعات المنشورة</small><strong>{{ twin.revisions.length }}</strong>
          </div>
          <div>
            <small>Baselines</small>
            <strong>{{
              twin.revisions.reduce((count, revision) => count + revision.baselines.length, 0)
            }}</strong>
          </div>
        </div>
        <div class="sim-lineage">
          <div v-for="revision in twin.revisions" :key="revision.id" class="sim-lineage__row">
            <span>REV {{ revision.revision }}</span>
            <span class="sim-lineage__track" aria-hidden="true" />
            <strong>{{ revision.baselines.length }} baseline</strong>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>
