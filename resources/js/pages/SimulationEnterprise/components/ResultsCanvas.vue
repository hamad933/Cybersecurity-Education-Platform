<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { ResultItem } from "../types";
import { fieldEntries, runTypeLabel, valueText } from "../utils";

const props = defineProps<{ result: ResultItem; results: ResultItem[] }>();
const mode = ref<"replay" | "aar" | "compare">("replay");
const compareId = ref<string | null>(null);

watch(
  () => props.result.id,
  () => {
    compareId.value =
      props.results.find((item) => item.id !== props.result.id)?.id ?? null;
  },
  { immediate: true },
);
const comparison = computed(
  () => props.results.find((item) => item.id === compareId.value) ?? null,
);
</script>

<template>
  <section class="surface-panel" data-testid="results-history-replay">
    <header class="section-heading">
      <div>
        <p class="rail-kicker">Sealed Result</p>
        <h2>حقائق التشغيل التاريخية</h2>
        <small class="technical technical-id" dir="ltr"
          >Run {{ result.run_id }}</small
        >
      </div>
      <div class="mode-tabs" aria-label="أوضاع النتائج">
        <button
          type="button"
          :class="{ active: mode === 'replay' }"
          @click="mode = 'replay'"
        >
          Replay
        </button>
        <button
          type="button"
          :class="{ active: mode === 'aar' }"
          @click="mode = 'aar'"
        >
          AAR
        </button>
        <button
          type="button"
          :class="{ active: mode === 'compare' }"
          @click="mode = 'compare'"
        >
          Compare
        </button>
      </div>
    </header>

    <template v-if="mode === 'replay'">
      <div class="machine-facts historical-facts">
        <article>
          <small>Run Type</small
          ><strong class="technical" dir="ltr">{{
            runTypeLabel(result.run_type)
          }}</strong>
        </article>
        <article>
          <small>Run Lifecycle</small
          ><strong class="technical" dir="ltr">{{
            result.run_lifecycle
          }}</strong>
        </article>
        <article>
          <small>Outcome</small
          ><strong class="technical" dir="ltr">{{ result.outcome }}</strong>
        </article>
        <article>
          <small>Sealed At</small
          ><strong class="technical" dir="ltr">{{ result.sealed_at }}</strong>
        </article>
      </div>
      <ol v-if="result.replay_timeline.length" class="replay-timeline">
        <li v-for="event in result.replay_timeline" :key="event.sequence">
          <span class="timeline-sequence technical" dir="ltr">{{
            event.sequence
          }}</span>
          <div>
            <strong class="technical" dir="ltr">{{ event.event_type }}</strong
            ><small class="technical" dir="ltr">{{ event.occurred_at }}</small>
          </div>
        </li>
      </ol>
      <p v-else class="truthful-unavailable">
        لا يحتوي Result المختوم على Replay Timeline.
      </p>
    </template>

    <section v-else-if="mode === 'aar'" class="aar-panel">
      <p class="rail-kicker">After Action Review</p>
      <h3>السرد المختوم مع النتيجة</h3>
      <p class="aar-summary">{{ result.summary_ar }}</p>
      <div class="aar-facts">
        <span
          >Outcome <b class="technical" dir="ltr">{{ result.outcome }}</b></span
        ><span
          >Score
          <b class="technical" dir="ltr">{{ result.score ?? "—" }}</b></span
        >
      </div>
      <p class="rail-kicker">Artifacts</p>
      <div v-if="result.artifacts.length" class="artifact-list">
        <article v-for="(artifact, index) in result.artifacts" :key="index">
          <span class="ordinal">{{ index + 1 }}</span>
          <div class="kv-list compact-kv">
            <div v-for="field in fieldEntries(artifact)" :key="field.key">
              <span class="technical" dir="ltr">{{ field.key }}</span
              ><strong>{{ field.value }}</strong>
            </div>
            <strong v-if="!fieldEntries(artifact).length">{{
              valueText(artifact)
            }}</strong>
          </div>
        </article>
      </div>
      <p v-else class="truthful-unavailable">
        لا توجد Artifacts مختومة مع هذه النتيجة.
      </p>
    </section>

    <section v-else class="compare-panel">
      <label class="compare-control"
        ><span>مقارنة مع Result مختوم</span
        ><select
          v-model="compareId"
          class="technical"
          :disabled="results.length < 2"
        >
          <option
            v-for="item in results.filter((entry) => entry.id !== result.id)"
            :key="item.id"
            :value="item.id"
          >
            {{ item.run_id }} — {{ item.outcome }}
          </option>
        </select></label
      >
      <div v-if="comparison" class="comparison-grid">
        <div class="comparison-head">
          <span>الحقيقة</span><strong>الحالي</strong><strong>المقارن</strong>
        </div>
        <div>
          <span>Run Type</span
          ><b class="technical" dir="ltr">{{ runTypeLabel(result.run_type) }}</b
          ><b class="technical" dir="ltr">{{
            runTypeLabel(comparison.run_type)
          }}</b>
        </div>
        <div>
          <span>Lifecycle</span
          ><b class="technical" dir="ltr">{{ result.run_lifecycle }}</b
          ><b class="technical" dir="ltr">{{ comparison.run_lifecycle }}</b>
        </div>
        <div>
          <span>Outcome</span
          ><b class="technical" dir="ltr">{{ result.outcome }}</b
          ><b class="technical" dir="ltr">{{ comparison.outcome }}</b>
        </div>
        <div>
          <span>Score</span
          ><b class="technical" dir="ltr">{{ result.score ?? "—" }}</b
          ><b class="technical" dir="ltr">{{ comparison.score ?? "—" }}</b>
        </div>
        <div>
          <span>Sealed At</span
          ><b class="technical" dir="ltr">{{ result.sealed_at }}</b
          ><b class="technical" dir="ltr">{{ comparison.sealed_at }}</b>
        </div>
      </div>
      <p v-else class="truthful-unavailable">
        يلزم Result تاريخي ثانٍ لإجراء مقارنة واقعية؛ لن تنشئ الواجهة نتيجة
        بديلة.
      </p>
    </section>
  </section>
</template>
