<script setup lang="ts">
import CepEmptyState from '../shared/CepEmptyState.vue';
import TechnicalText from '../shared/TechnicalText.vue';
import type { TodayRationaleItem } from './types';

defineProps<{
  rationale?: TodayRationaleItem | null;
}>();
</script>

<template>
  <section
    id="rationale"
    class="cep-section today-section"
    aria-labelledby="rationale-title"
    data-today-level="3"
  >
    <p class="cep-kicker">السياق والمسوّغ</p>
    <h2 id="rationale-title" class="cep-section-title">المسوّغ والهدف</h2>

    <div v-if="rationale" class="today-rationale-card" data-testid="today-rationale-active">
      <div class="today-rationale-card__body">
        <p class="today-rationale-text">{{ rationale.text }}</p>

        <div v-if="rationale.targetCompetency" class="today-rationale-tag-row">
          <span class="today-tag-label">الكفاءة المستهدفة:</span>
          <TechnicalText :value="rationale.targetCompetency" />
        </div>

        <div
          v-if="rationale.unlockedCapabilities && rationale.unlockedCapabilities.length > 0"
          class="today-capability-list"
        >
          <span class="today-tag-label">ما يفتحه هذا الإجراء:</span>
          <ul class="today-bullet-list">
            <li v-for="(cap, idx) in rationale.unlockedCapabilities" :key="idx">
              {{ cap }}
            </li>
          </ul>
        </div>

        <div
          v-if="rationale.prerequisiteChain && rationale.prerequisiteChain.length > 0"
          class="today-prereq-list"
        >
          <span class="today-tag-label">سلسلة المتطلبات المستوفاة:</span>
          <ul class="today-bullet-list">
            <li v-for="(pre, idx) in rationale.prerequisiteChain" :key="idx">
              <TechnicalText :value="pre" />
            </li>
          </ul>
        </div>
      </div>
    </div>

    <CepEmptyState
      v-else
      class="cep-section__body"
      title="المسوغات التشغيلية تستند إلى شجرة المتطلبات الحقيقية"
      description="يتم توليد مسوغات التوصيات استنادًا إلى سجلات المتطلبات والأدلة المحققة في المنصة، دون اللجوء إلى خوارزميات إجبار أو تحفيز مصطنع."
      data-testid="today-rationale-empty"
    />
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-rationale-card {
  margin-top: 0.9rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.15rem;
}

.today-rationale-text {
  margin: 0;
  color: var(--cep-text);
  font-size: 0.92rem;
  line-height: 1.8;
}

.today-rationale-tag-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.85rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--cep-border);
}

.today-tag-label {
  color: var(--cep-accent);
  font-size: 0.8rem;
  font-weight: 700;
}

.today-capability-list,
.today-prereq-list {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--cep-border);
}

.today-bullet-list {
  display: grid;
  gap: 0.35rem;
  margin: 0.45rem 0 0;
  padding-inline-start: 1.25rem;
  color: var(--cep-text-muted);
  font-size: 0.84rem;
  line-height: 1.7;
}
</style>
