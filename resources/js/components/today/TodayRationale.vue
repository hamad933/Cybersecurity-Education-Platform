<script setup lang="ts">
import CepEmptyState from '../shared/CepEmptyState.vue';
import TechnicalText from '../shared/TechnicalText.vue';
import type { TodayRationaleItem, OrchestrationNode } from './types';

defineProps<{
  rationale?: OrchestrationNode<TodayRationaleItem> | null;
}>();
</script>

<template>
  <section
    id="rationale"
    class="cep-section today-section"
    aria-labelledby="rationale-title"
    data-today-level="3"
  >
    <div class="today-section-header">
      <div>
        <p class="cep-kicker">السياق والمسوّغ</p>
        <h2 id="rationale-title" class="cep-section-title">المسوّغ والهدف</h2>
      </div>
      <span v-if="rationale?.status === 'AVAILABLE' && rationale.data" class="today-rationale-badge">
        <svg
          class="today-rationale-icon"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
            clip-rule="evenodd"
          />
        </svg>
        سياق تعليمي مبني على الأدلة
      </span>
    </div>

    <div v-if="rationale?.status === 'AVAILABLE' && rationale.data" class="today-rationale-card" data-testid="today-rationale-active">
      <div class="today-rationale-card__body">
        <div class="today-rationale-statement">
          <div class="today-rationale-quote-bar" aria-hidden="true" />
          <p class="today-rationale-text">{{ rationale.data.text }}</p>
        </div>

        <div
          v-if="rationale.data.targetCompetency"
          class="today-rationale-row today-rationale-row--competency"
        >
          <span class="today-tag-label">
            <svg
              class="today-section-micro-icon"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                clip-rule="evenodd"
              />
            </svg>
            الكفاءة المستهدفة:
          </span>
          <span class="today-competency-pill">
            <TechnicalText :value="rationale.data.targetCompetency" />
          </span>
        </div>

        <div
          v-if="rationale.data.unlockedCapabilities && rationale.data.unlockedCapabilities.length > 0"
          class="today-rationale-row"
        >
          <span class="today-tag-label">
            <svg
              class="today-section-micro-icon"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"
                clip-rule="evenodd"
              />
            </svg>
            ما يفتحه هذا الإجراء:
          </span>
          <ul class="today-chips-grid">
            <li
              v-for="(cap, idx) in rationale.data.unlockedCapabilities"
              :key="idx"
              class="today-capability-chip"
            >
              <svg
                class="today-chip-check"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  fill-rule="evenodd"
                  d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                  clip-rule="evenodd"
                />
              </svg>
              <span>{{ cap }}</span>
            </li>
          </ul>
        </div>

        <div
          v-if="rationale.data.prerequisiteChain && rationale.data.prerequisiteChain.length > 0"
          class="today-rationale-row"
        >
          <span class="today-tag-label">
            <svg
              class="today-section-micro-icon"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z"
                clip-rule="evenodd"
              />
            </svg>
            سلسلة المتطلبات المستوفاة:
          </span>
          <div class="today-prereq-chain">
            <template v-for="(pre, idx) in rationale.data.prerequisiteChain" :key="idx">
              <span class="today-prereq-pill">
                <TechnicalText :value="pre" />
              </span>
              <span
                v-if="idx < rationale.data.prerequisiteChain.length - 1"
                class="today-chain-arrow"
                aria-hidden="true"
              >
                ◀
              </span>
            </template>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="rationale?.status === 'UNAVAILABLE'" class="today-empty-wrapper">
      <div class="today-empty-icon-box" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="today-empty-svg">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="المسوغات غير متوفرة"
        description="تعذر الاتصال بالمجال لمعرفة مسوغات التوصية."
        data-testid="today-rationale-unavailable"
      />
    </div>

    <div v-else class="today-empty-wrapper">
      <div class="today-empty-icon-box" aria-hidden="true">
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          class="today-empty-svg"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"
          />
        </svg>
      </div>
      <CepEmptyState
        class="cep-section__body today-empty-content"
        title="المسوغات التشغيلية تستند إلى شجرة المتطلبات الحقيقية"
        description="يتم توليد مسوغات التوصيات استنادًا إلى سجلات المتطلبات والأدلة المحققة في المنصة، دون اللجوء إلى خوارزميات إجبار أو تحفيز مصطنع."
        data-testid="today-rationale-empty"
      />
    </div>
  </section>
</template>

<style scoped>
.today-section {
  scroll-margin-top: 6.5rem;
}

.today-section-header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 0.75rem;
}

.today-rationale-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel-strong);
  padding: 0.22rem 0.55rem;
  color: var(--cep-text-muted);
  font-size: 0.74rem;
  font-weight: 700;
}

.today-rationale-icon {
  width: 0.85rem;
  height: 0.85rem;
  color: var(--cep-accent);
}

.today-rationale-card {
  margin-top: 0.9rem;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.25rem;
  box-shadow: var(--cep-shadow);
}

.today-rationale-card__body {
  display: grid;
  gap: 1rem;
}

.today-rationale-statement {
  display: flex;
  align-items: stretch;
  gap: 0.85rem;
}

.today-rationale-quote-bar {
  width: 3px;
  flex: 0 0 3px;
  border-radius: 2px;
  background: var(--cep-accent);
}

.today-rationale-text {
  margin: 0;
  color: var(--cep-text);
  font-size: 0.96rem;
  font-weight: 600;
  line-height: 1.8;
}

.today-rationale-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding-top: 0.85rem;
  border-top: 1px solid var(--cep-border);
}

.today-rationale-row--competency {
  flex-direction: row;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.65rem;
}

.today-tag-label {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--cep-accent);
  font-size: 0.82rem;
  font-weight: 750;
}

.today-section-micro-icon {
  width: 0.85rem;
  height: 0.85rem;
}

.today-competency-pill {
  display: inline-flex;
  align-items: center;
  border: 1px solid rgba(34, 211, 238, 0.35);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-accent-soft);
  padding: 0.2rem 0.65rem;
  font-weight: 700;
}

.today-chips-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.today-capability-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-panel);
  padding: 0.25rem 0.65rem;
  color: var(--cep-text);
  font-size: 0.82rem;
  font-weight: 600;
}

.today-chip-check {
  width: 0.8rem;
  height: 0.8rem;
  color: #10b981;
}

.today-prereq-chain {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
}

.today-prereq-pill {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--cep-border-strong);
  border-radius: var(--cep-radius-sm);
  background: var(--cep-bg-canvas);
  padding: 0.22rem 0.65rem;
  font-size: 0.8rem;
}

.today-chain-arrow {
  color: var(--cep-accent);
  font-size: 0.75rem;
  font-weight: 700;
}

.today-empty-wrapper {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-top: 0.9rem;
  border: 1px dashed var(--cep-border-strong);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-panel-strong);
  padding: 1.25rem;
}

.today-empty-icon-box {
  display: grid;
  flex: 0 0 2.75rem;
  width: 2.75rem;
  height: 2.75rem;
  place-items: center;
  border: 1px solid var(--cep-border);
  border-radius: var(--cep-radius-md);
  background: var(--cep-bg-canvas);
  color: var(--cep-accent);
}

.today-empty-svg {
  width: 1.35rem;
  height: 1.35rem;
}

.today-empty-content {
  flex: 1;
  min-width: 0;
  border: none !important;
  background: transparent !important;
  padding: 0 !important;
}

@media (max-width: 48rem) {
  .today-empty-wrapper {
    flex-direction: column;
  }
}
</style>
