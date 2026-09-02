<script setup lang="ts">
import { computed, ref, watch } from 'vue';

import { shortDigest } from '../formatters';
import { displayValue } from '../projections';
import type {
  ResultCompareProjection,
  ResultContextSelection,
  ResultItem,
  ResultMode,
} from '../types';

const props = defineProps<{
  result: ResultItem | null;
  mode: ResultMode;
  compare: ResultCompareProjection | null;
  loading: boolean;
}>();

const emit = defineEmits<{ selectContext: [selection: ResultContextSelection] }>();

const selectedReplayIndex = ref(0);
const selectedFactId = ref<string | null>(null);
const selectedDimensionKey = ref<string | null>(null);

const effective = computed(() => props.result?.analytics.overview.effective ?? null);
const replayEvents = computed(() => props.result?.analytics.replay.events ?? []);
const aarFacts = computed(() => props.result?.analytics.aar.facts ?? []);
const selectedReplayEvent = computed(() => replayEvents.value[selectedReplayIndex.value] ?? null);
const candidateEnvelope = computed(() => props.result?.analytics.candidate_evidence.envelope ?? {});
const materialContext = computed(() => {
  const value = candidateEnvelope.value.material_context;
  return value !== null && typeof value === 'object' && !Array.isArray(value)
    ? (value as Record<string, unknown>)
    : {};
});

const modeTitle: Record<ResultMode, string> = {
  overview: 'نظرة تحليلية على النتيجة',
  replay: 'إعادة عرض التاريخ المختوم',
  aar: 'مراجعة ما بعد المهمة',
  compare: 'مقارنة تشغيلات متميزة',
  'candidate-evidence': 'معاينة مصدر Candidate Evidence',
};

const stateCopy: Record<string, { title: string; body: string }> = {
  INITIAL_REVISION_REQUIRED: {
    title: 'يلزم إنشاء المراجعة الابتدائية صراحةً',
    body: 'لا تنشئ قراءة Results مراجعةً ضمنيًا. يلزم مسار orchestration محكوم قبل التحليل.',
  },
  LINEAGE_RECONCILIATION_REQUIRED: {
    title: 'تتطلب سلسلة المراجعات مصالحة',
    body: 'لا توجد ورقة خطية وحيدة يمكن اعتمادها بوصفها المراجعة الفعالة.',
  },
  SEMANTIC_PROJECTOR_UNAVAILABLE: {
    title: 'Semantic Projector غير متاح',
    body: 'التاريخ المختوم ظاهر، لكن إعادة بناء الحالة متوقفة لأن نسخة القواعد غير مدعومة.',
  },
  PARTIAL_ANALYTICS: {
    title: 'تحليلات جزئية',
    body: 'تتوفر حقائق محكومة مشتركة، بينما تبقى بعض الأبعاد غير متاحة أو غير متوافقة.',
  },
  UNAVAILABLE: {
    title: 'الإسقاط غير متاح',
    body: 'لم تتوفر تبعية محكومة لازمة لهذا الوضع.',
  },
  ERROR: {
    title: 'تعذر تحميل الإسقاط',
    body: 'فشل مسار القراءة بأمان، ولم تُستنتج أي قيمة بديلة.',
  },
  EMPTY: {
    title: 'لا توجد بيانات لهذا الوضع',
    body: 'الاستعلام صالح، لكنه لم يُرجع حقيقة قابلة للعرض.',
  },
};

watch(
  () => [props.result?.id, props.mode],
  () => {
    selectedReplayIndex.value = 0;
    selectedFactId.value = null;
    selectedDimensionKey.value = null;
    emit(
      'selectContext',
      props.mode === 'candidate-evidence' ? { kind: 'candidate-evidence' } : { kind: 'overview' },
    );
  },
  { immediate: true },
);

function selectReplay(index: number): void {
  selectedReplayIndex.value = Math.min(
    Math.max(index, 0),
    Math.max(replayEvents.value.length - 1, 0),
  );
  const event = replayEvents.value[selectedReplayIndex.value];
  if (event) emit('selectContext', { kind: 'replay-event', event });
}

function selectFact(fact: (typeof aarFacts.value)[number]): void {
  selectedFactId.value = fact.id;
  emit('selectContext', { kind: 'aar-fact', fact });
}

function selectDimension(dimension: ResultCompareProjection['dimensions'][number]): void {
  selectedDimensionKey.value = dimension.key;
  emit('selectContext', { kind: 'compare-dimension', dimension });
}
</script>

<template>
  <section
    class="sim-surface sim-results-workstation"
    data-testid="result-center"
    :aria-busy="loading"
  >
    <header class="sim-results-identity">
      <div>
        <p class="sim-kicker">RESULTS · ZERO-WRITE ANALYTICAL WORKSTATION</p>
        <h1>{{ modeTitle[mode] }}</h1>
      </div>
      <div v-if="result" class="sim-results-identity__facts">
        <span>
          <small>CANONICAL RESULT</small>
          <code class="sim-technical">{{ shortDigest(result.id) }}</code>
        </span>
        <span>
          <small>RUN</small>
          <code class="sim-technical">{{ shortDigest(result.run_id) }}</code>
        </span>
        <span>
          <small>LINEAGE</small>
          <b :data-state="result.analytics.overview.lineage.status">
            {{ result.analytics.overview.lineage.status }}
          </b>
        </span>
        <span>
          <small>READ CONTRACT</small>
          <b class="sim-text-success">ZERO WRITE</b>
        </span>
      </div>
    </header>

    <div v-if="loading" class="sim-analytical-state sim-analytical-state--loading" role="status">
      <span class="sim-loading-rail" aria-hidden="true" />
      <div>
        <strong>جارٍ تحميل الإسقاط المحكوم</strong>
        <p>تظل هندسة مساحة العمل ثابتة، ولا تظهر قيم مؤقتة.</p>
      </div>
    </div>

    <div v-else-if="!result" class="sim-analytical-state" data-testid="results-empty">
      <strong>لا توجد Results مختومة</strong>
      <p>أنشئ Result عبر دورة Run المحكومة كي تظهر هنا.</p>
    </div>

    <div
      v-else-if="result.analytics.status === 'ERROR'"
      class="sim-analytical-state sim-analytical-state--error"
      role="alert"
      data-testid="results-error"
    >
      <strong>{{ stateCopy.ERROR.title }}</strong>
      <p>{{ stateCopy.ERROR.body }}</p>
      <code class="sim-technical">DIAGNOSTIC {{ result.analytics.diagnostic_id }}</code>
    </div>

    <div
      v-else-if="!effective"
      class="sim-analytical-state"
      :data-state="result.analytics.overview.status"
      data-testid="results-lineage-state"
    >
      <strong>{{ stateCopy[result.analytics.overview.status]?.title }}</strong>
      <p>{{ stateCopy[result.analytics.overview.status]?.body }}</p>
      <dl class="sim-state-facts">
        <div>
          <dt>Canonical Result</dt>
          <dd class="sim-technical">{{ result.id }}</dd>
        </div>
        <div>
          <dt>Canonical digest</dt>
          <dd class="sim-technical sim-wrap">{{ result.result_digest }}</dd>
        </div>
        <div>
          <dt>Revision rows</dt>
          <dd>{{ result.analytics.overview.lineage.revision_count ?? 0 }}</dd>
        </div>
      </dl>
    </div>

    <template v-else>
      <section v-if="mode === 'overview'" class="sim-results-mode" data-testid="result-overview">
        <div class="sim-overview-verdict">
          <div>
            <small>EFFECTIVE OUTCOME</small>
            <strong class="sim-technical">{{ effective.outcome ?? 'N/A' }}</strong>
          </div>
          <div>
            <small>EFFECTIVE SCORE</small>
            <strong class="sim-technical">{{ effective.score ?? 'N/A' }}</strong>
          </div>
          <div>
            <small>EFFECTIVE REVISION</small>
            <code class="sim-technical">{{ shortDigest(effective.id) }}</code>
          </div>
          <div>
            <small>CANONICAL REVISION</small>
            <strong class="sim-technical">REV {{ result.result_revision }}</strong>
          </div>
        </div>

        <div class="sim-overview-grid">
          <section class="sim-analytical-pane sim-analytical-pane--dominant">
            <header class="sim-pane-heading">
              <div>
                <p class="sim-kicker">EFFECTIVE RESULT TRUTH</p>
                <h2>الحقيقة الفعالة للنتيجة</h2>
              </div>
              <span class="sim-chip">IMMUTABLE CANONICAL + APPEND-ONLY REVISION</span>
            </header>
            <p class="sim-effective-summary">
              {{ effective.summary_ar || 'لا يوجد تعليق مختوم في المراجعة الفعالة.' }}
            </p>
            <dl class="sim-result-metadata">
              <div>
                <dt>Result digest</dt>
                <dd class="sim-technical sim-wrap">{{ result.result_digest }}</dd>
              </div>
              <div>
                <dt>Effective digest</dt>
                <dd class="sim-technical sim-wrap">{{ effective.revision_digest }}</dd>
              </div>
              <div>
                <dt>Base revision</dt>
                <dd class="sim-technical">{{ effective.base_revision_id ?? 'INITIAL' }}</dd>
              </div>
              <div>
                <dt>Correction reason</dt>
                <dd>{{ effective.correction_reason ?? 'لا يوجد — مراجعة ابتدائية' }}</dd>
              </div>
              <div>
                <dt>Provenance</dt>
                <dd class="sim-technical">{{ result.provenance }}</dd>
              </div>
              <div>
                <dt>Source fixture</dt>
                <dd class="sim-technical">{{ result.source_fixture ? 'TRUE' : 'FALSE' }}</dd>
              </div>
            </dl>
          </section>

          <section class="sim-analytical-pane">
            <header class="sim-pane-heading">
              <div>
                <p class="sim-kicker">LINEAGE HEALTH</p>
                <h2>سلسلة المراجعات</h2>
              </div>
              <span class="sim-state-pill" data-state="READY">UNIQUE LEAF</span>
            </header>
            <ol class="sim-revision-lineage" tabindex="0" aria-label="سلسلة مراجعات Result">
              <li
                v-for="(revision, index) in result.analytics.overview.lineage.revisions"
                :key="revision.id"
                :class="{ 'is-effective': revision.id === effective.id }"
              >
                <span class="sim-lineage-index">{{ String(index + 1).padStart(2, '0') }}</span>
                <div>
                  <strong>{{
                    revision.id === effective.id ? 'المراجعة الفعالة' : 'مراجعة تاريخية'
                  }}</strong>
                  <code class="sim-technical">{{ shortDigest(revision.id) }}</code>
                  <small>{{ revision.correction_reason ?? 'INITIAL REVISION' }}</small>
                </div>
                <code class="sim-technical">{{ shortDigest(revision.revision_digest) }}</code>
              </li>
            </ol>
          </section>
        </div>
      </section>

      <section v-else-if="mode === 'replay'" class="sim-results-mode" data-testid="result-replay">
        <div
          v-if="result.analytics.replay.status === 'SEMANTIC_PROJECTOR_UNAVAILABLE'"
          class="sim-analytical-state sim-analytical-state--warning"
          data-testid="semantic-projector-unavailable"
        >
          <strong>{{ stateCopy.SEMANTIC_PROJECTOR_UNAVAILABLE.title }}</strong>
          <p>{{ stateCopy.SEMANTIC_PROJECTOR_UNAVAILABLE.body }}</p>
          <code class="sim-technical">
            GRAMMAR {{ result.analytics.replay.projector?.grammar_version ?? 'UNKNOWN' }}
          </code>
          <code class="sim-technical">
            {{ result.analytics.replay.projector?.reason ?? 'PROJECTOR VERSION NOT SUPPORTED' }}
          </code>
        </div>

        <template v-else>
          <div class="sim-replay-commandbar">
            <div>
              <small>PROJECTOR</small>
              <code class="sim-technical">
                {{ result.analytics.replay.projector?.semantic_version ?? 'NOT_APPLICABLE' }}
              </code>
            </div>
            <div class="sim-replay-stepper" aria-label="التنقل بين نقاط Replay">
              <button
                type="button"
                :disabled="selectedReplayIndex === 0"
                @click="selectReplay(selectedReplayIndex - 1)"
              >
                السابق
              </button>
              <span class="sim-technical">
                {{ replayEvents.length ? selectedReplayIndex + 1 : 0 }} / {{ replayEvents.length }}
              </span>
              <button
                type="button"
                :disabled="selectedReplayIndex >= replayEvents.length - 1"
                @click="selectReplay(selectedReplayIndex + 1)"
              >
                التالي
              </button>
            </div>
            <span class="sim-zero-write-badge">SEALED HISTORY · ZERO WRITE</span>
          </div>

          <div v-if="replayEvents.length" class="sim-replay-analysis">
            <ol
              class="sim-replay-timeline"
              tabindex="0"
              aria-label="الخط الزمني المختوم"
              data-testid="result-replay-timeline"
            >
              <li v-for="(event, index) in replayEvents" :key="event.sequence">
                <button
                  type="button"
                  :class="{ 'is-selected': selectedReplayIndex === index }"
                  :aria-pressed="selectedReplayIndex === index"
                  data-testid="replay-event"
                  @click="selectReplay(index)"
                >
                  <span class="sim-timeline-sequence">{{
                    String(event.sequence).padStart(2, '0')
                  }}</span>
                  <div>
                    <strong class="sim-technical">{{ event.event_type }}</strong>
                    <small class="sim-technical">{{ event.occurred_at || 'TIME N/A' }}</small>
                  </div>
                  <span :data-state="event.projection_status">{{ event.projection_status }}</span>
                </button>
              </li>
            </ol>

            <section class="sim-replay-point" tabindex="0">
              <header class="sim-pane-heading">
                <div>
                  <p class="sim-kicker">STATE AT SELECTED POINT</p>
                  <h2>الحالة في نقطة الفحص</h2>
                </div>
                <span v-if="selectedReplayEvent" class="sim-chip">
                  SEQ {{ selectedReplayEvent.sequence }}
                </span>
              </header>
              <template v-if="selectedReplayEvent">
                <dl class="sim-result-metadata sim-result-metadata--compact">
                  <div>
                    <dt>Event type</dt>
                    <dd class="sim-technical">{{ selectedReplayEvent.event_type }}</dd>
                  </div>
                  <div>
                    <dt>Source ref</dt>
                    <dd class="sim-technical">{{ selectedReplayEvent.source_ref }}</dd>
                  </div>
                  <div>
                    <dt>Actor</dt>
                    <dd class="sim-technical">{{ selectedReplayEvent.actor_id || 'N/A' }}</dd>
                  </div>
                  <div>
                    <dt>Operation key</dt>
                    <dd class="sim-technical">{{ selectedReplayEvent.operation_key ?? 'N/A' }}</dd>
                  </div>
                </dl>
                <div v-if="selectedReplayEvent.state_at_point" class="sim-projected-state">
                  <p>
                    <strong>نطاق الإسقاط</strong>
                    <code class="sim-technical">
                      {{ selectedReplayEvent.state_at_point.projection_scope }}
                    </code>
                  </p>
                  <dl>
                    <div
                      v-for="(value, key) in selectedReplayEvent.state_at_point.controls"
                      :key="key"
                    >
                      <dt class="sim-technical">{{ key }}</dt>
                      <dd class="sim-technical">{{ value ? 'TRUE' : 'FALSE' }}</dd>
                    </div>
                  </dl>
                  <p
                    v-if="!Object.keys(selectedReplayEvent.state_at_point.controls).length"
                    class="sim-muted"
                  >
                    لم تُطبّق عملية محكومة قبل هذه النقطة.
                  </p>
                </div>
              </template>
            </section>
          </div>
          <div v-else class="sim-analytical-state">
            <strong>{{ stateCopy.EMPTY.title }}</strong>
            <p>{{ stateCopy.EMPTY.body }}</p>
          </div>
        </template>
      </section>

      <section v-else-if="mode === 'aar'" class="sim-results-mode" data-testid="result-aar">
        <div class="sim-aar-header">
          <div>
            <small>SOURCE POLICY</small>
            <code class="sim-technical">{{ result.analytics.aar.source_policy }}</code>
          </div>
          <span class="sim-state-pill" :data-state="result.analytics.aar.status">
            {{ result.analytics.aar.status }}
          </span>
          <span class="sim-zero-write-badge">DETERMINISTIC · ZERO WRITE</span>
        </div>

        <section class="sim-aar-commentary">
          <p class="sim-kicker">SEALED RESULT COMMENTARY</p>
          <blockquote>
            {{
              result.analytics.aar.sealed_commentary?.value ??
              'لا يوجد تعليق مختوم ضمن المراجعة الفعالة.'
            }}
          </blockquote>
          <code class="sim-technical">
            {{ result.analytics.aar.sealed_commentary?.source_ref ?? 'SOURCE N/A' }}
          </code>
        </section>

        <div class="sim-aar-layout">
          <section class="sim-analytical-pane sim-analytical-pane--dominant">
            <header class="sim-pane-heading">
              <div>
                <p class="sim-kicker">SOURCE-TRACEABLE FACTS</p>
                <h2>الحقائق القابلة للتتبع</h2>
              </div>
              <span class="sim-chip">{{ aarFacts.length }} FACTS</span>
            </header>
            <div class="sim-aar-facts" tabindex="0">
              <button
                v-for="fact in aarFacts"
                :key="fact.id"
                type="button"
                :class="{ 'is-selected': selectedFactId === fact.id }"
                @click="selectFact(fact)"
              >
                <span>{{ fact.label_ar }}</span>
                <strong class="sim-technical">{{ displayValue(fact.value) }}</strong>
                <code class="sim-technical">{{ fact.source_ref }}</code>
              </button>
            </div>
          </section>

          <section class="sim-analytical-pane">
            <header class="sim-pane-heading">
              <div>
                <p class="sim-kicker">EXPLICIT ABSENCE</p>
                <h2>غير متاح من الحقيقة المختومة</h2>
              </div>
            </header>
            <ul class="sim-unavailable-facts">
              <li v-for="item in result.analytics.aar.unavailable_sections" :key="item.key">
                <span class="sim-technical">{{ item.key }}</span>
                <b>{{ item.reason }}</b>
              </li>
            </ul>
          </section>
        </div>
      </section>

      <section v-else-if="mode === 'compare'" class="sim-results-mode" data-testid="result-compare">
        <div v-if="!compare || compare.status === 'EMPTY'" class="sim-analytical-state">
          <strong>اختر نتيجتين من تشغيلين متميزين</strong>
          <p>استخدم لوحة البنية لاختيار Results. لا تُقارن مراجعتان من السلسلة نفسها.</p>
          <code class="sim-technical">{{ compare?.reason }}</code>
        </div>
        <div
          v-else-if="compare.status === 'UNAVAILABLE'"
          class="sim-analytical-state sim-analytical-state--warning"
          data-testid="compare-unavailable"
        >
          <strong>تعذر اعتماد مجموعة المقارنة</strong>
          <p>رُفضت المجموعة لأن قواعد التميّز أو المراجعة الفعالة لم تتحقق.</p>
          <code class="sim-technical">{{ compare.reason }}</code>
        </div>
        <template v-else>
          <div class="sim-compare-header">
            <div v-for="item in compare.items" :key="item.result_id">
              <small>CANONICAL RUN / EFFECTIVE REVISION</small>
              <strong class="sim-technical">{{ shortDigest(item.run_id) }}</strong>
              <code class="sim-technical">{{ shortDigest(item.effective_revision_id) }}</code>
            </div>
            <span class="sim-state-pill" :data-state="compare.status">{{ compare.status }}</span>
          </div>
          <div class="sim-compare-scroll" tabindex="0" aria-label="مصفوفة مقارنة Results">
            <table class="sim-compare-matrix">
              <thead>
                <tr>
                  <th>المعيار المحكوم</th>
                  <th v-for="item in compare.items" :key="item.result_id" class="sim-technical">
                    RUN {{ shortDigest(item.run_id) }}
                  </th>
                  <th>التوافق</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="dimension in compare.dimensions"
                  :key="dimension.key"
                  :class="{ 'is-selected': selectedDimensionKey === dimension.key }"
                >
                  <th>
                    <button type="button" @click="selectDimension(dimension)">
                      <span>{{ dimension.label_ar }}</span>
                      <code class="sim-technical">{{ dimension.key }}</code>
                    </button>
                  </th>
                  <td
                    v-for="value in dimension.values"
                    :key="value.result_id"
                    class="sim-technical"
                    :data-availability="value.availability"
                  >
                    {{ value.display }}
                  </td>
                  <td>
                    <span :data-state="dimension.status">
                      {{ dimension.compatible ? 'COMPATIBLE' : 'N/A' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <footer class="sim-compare-contract">
            <code class="sim-technical">{{ compare.comparison_semantics }}</code>
            <span>لا تُستنتج أسباب أو ترتيبات من فرق الدرجة.</span>
            <b>ZERO WRITE</b>
          </footer>
        </template>
      </section>

      <section v-else class="sim-results-mode" data-testid="result-candidate-evidence">
        <div class="sim-candidate-boundary">
          <div>
            <p class="sim-kicker">W03 SOURCE-SIDE PREVIEW</p>
            <h2>معاينة فقط — لا إنشاء لـ W04 Evidence</h2>
            <p>
              تُعرض الهوية والمراجع المحكومة لتجهيز المصدر. لا يحدث Intake أو Review أو Decision أو
              Mastery، ولا يُكتب سجل handoff قديم.
            </p>
          </div>
          <span class="sim-zero-write-badge">ZERO WRITE · W04 NOT CLAIMED</span>
        </div>

        <div class="sim-candidate-grid">
          <section class="sim-analytical-pane sim-analytical-pane--dominant">
            <header class="sim-pane-heading">
              <div>
                <p class="sim-kicker">SOURCE ENVELOPE</p>
                <h2>غلاف المصدر المحكوم</h2>
              </div>
              <span class="sim-state-pill" data-state="READY">
                {{ result.analytics.candidate_evidence.status }}
              </span>
            </header>
            <dl class="sim-result-metadata">
              <div
                v-for="key in [
                  'status',
                  'result_id',
                  'run_id',
                  'effective_revision_id',
                  'effective_revision_digest',
                  'source_result_digest',
                  'base_revision_id',
                  'correction_reason',
                  'provenance',
                  'source_fixture',
                ]"
                :key="key"
              >
                <dt class="sim-technical">{{ key }}</dt>
                <dd class="sim-technical sim-wrap">
                  {{ displayValue(candidateEnvelope[key]) }}
                </dd>
              </div>
            </dl>
          </section>

          <section class="sim-analytical-pane">
            <header class="sim-pane-heading">
              <div>
                <p class="sim-kicker">MATERIAL CONTEXT</p>
                <h2>سياق المواد</h2>
              </div>
            </header>
            <dl class="sim-material-context">
              <div v-for="(value, key) in materialContext" :key="key">
                <dt class="sim-technical">{{ key }}</dt>
                <dd class="sim-technical sim-wrap">{{ displayValue(value) }}</dd>
              </div>
            </dl>
          </section>
        </div>
      </section>

      <footer class="sim-results-truthbar">
        <span>
          <small>CANONICAL DIGEST</small>
          <code class="sim-technical">{{ shortDigest(result.result_digest) }}</code>
        </span>
        <span>
          <small>EFFECTIVE DIGEST</small>
          <code class="sim-technical">{{ shortDigest(effective.revision_digest) }}</code>
        </span>
        <span>
          <small>PROVENANCE</small>
          <b class="sim-technical">{{ result.provenance }}</b>
        </span>
        <span>
          <small>MODE</small>
          <b class="sim-technical">{{ mode.toUpperCase() }}</b>
        </span>
      </footer>
    </template>
  </section>
</template>
