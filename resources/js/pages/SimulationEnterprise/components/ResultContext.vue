<script setup lang="ts">
import { computed } from 'vue';

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
  selection: ResultContextSelection;
  compare: ResultCompareProjection | null;
  loading: boolean;
}>();

const effective = computed(() => props.result?.analytics.overview.effective ?? null);
const envelope = computed(() => props.result?.analytics.candidate_evidence.envelope ?? {});
</script>

<template>
  <aside class="sim-context sim-results-context" data-testid="result-right" :aria-busy="loading">
    <div class="sim-panel-heading">
      <div class="sim-panel-heading__title">
        <p class="sim-kicker">RIGHT · UNIQUE CONTEXT</p>
        <h2>السياق المحدد</h2>
      </div>
      <span class="sim-context-mode sim-technical">{{ mode.toUpperCase() }}</span>
    </div>

    <div v-if="loading" class="sim-context-loading" role="status">
      جارٍ تحديث السياق من الإسقاط المحكوم…
    </div>
    <p v-else-if="!result" class="sim-muted">اختر Result لعرض سياقها.</p>
    <section v-else-if="!effective" class="sim-context-section">
      <h3>{{ result.analytics.overview.status }}</h3>
      <p>لا يوجد Effective Result يمكن استخدامه في السياق التحليلي.</p>
      <code class="sim-technical sim-wrap">{{ result.result_digest }}</code>
    </section>

    <template v-else>
      <section v-if="selection.kind === 'replay-event'" class="sim-context-section">
        <p class="sim-kicker">SELECTED TIMELINE POINT</p>
        <h3 class="sim-technical">{{ selection.event.event_type }}</h3>
        <div class="sim-sealed-note">
          <strong>ZERO WRITE</strong>
          <span>اختيار النقطة يغيّر سياق الفحص المحلي فقط.</span>
        </div>
        <dl class="sim-context-facts">
          <div>
            <dt>Sequence</dt>
            <dd class="sim-technical">{{ selection.event.sequence }}</dd>
          </div>
          <div>
            <dt>Source</dt>
            <dd class="sim-technical">{{ selection.event.source_ref }}</dd>
          </div>
          <div>
            <dt>Actor</dt>
            <dd class="sim-technical">{{ selection.event.actor_id || 'N/A' }}</dd>
          </div>
          <div>
            <dt>Operation key</dt>
            <dd class="sim-technical">{{ selection.event.operation_key ?? 'N/A' }}</dd>
          </div>
          <div>
            <dt>Grammar</dt>
            <dd class="sim-technical">
              {{ result.analytics.replay.projector?.grammar_version ?? 'N/A' }}
            </dd>
          </div>
        </dl>
        <div class="sim-context-payload">
          <strong>حمولة الحدث المختومة</strong>
          <dl>
            <div v-for="(value, key) in selection.event.payload" :key="key">
              <dt class="sim-technical">{{ key }}</dt>
              <dd class="sim-technical sim-wrap">{{ displayValue(value) }}</dd>
            </div>
          </dl>
        </div>
      </section>

      <section v-else-if="selection.kind === 'aar-fact'" class="sim-context-section">
        <p class="sim-kicker">SELECTED AAR FACT</p>
        <h3>{{ selection.fact.label_ar }}</h3>
        <strong class="sim-context-value sim-technical">
          {{ displayValue(selection.fact.value) }}
        </strong>
        <dl class="sim-context-facts">
          <div>
            <dt>Fact kind</dt>
            <dd class="sim-technical">{{ selection.fact.kind }}</dd>
          </div>
          <div>
            <dt>Source ref</dt>
            <dd class="sim-technical">{{ selection.fact.source_ref }}</dd>
          </div>
          <div v-if="selection.fact.sequence">
            <dt>Sequence</dt>
            <dd class="sim-technical">{{ selection.fact.sequence }}</dd>
          </div>
        </dl>
        <p class="sim-context-boundary">
          هذا Fact من مصدر محدد؛ لا يُحوّل إلى سبب أو درس أو missed detection تلقائيًا.
        </p>
      </section>

      <section v-else-if="selection.kind === 'compare-dimension'" class="sim-context-section">
        <p class="sim-kicker">SELECTED COMPARISON DIMENSION</p>
        <h3>{{ selection.dimension.label_ar }}</h3>
        <dl class="sim-context-facts">
          <div>
            <dt>Registry key</dt>
            <dd class="sim-technical">{{ selection.dimension.key }}</dd>
          </div>
          <div>
            <dt>Value type</dt>
            <dd class="sim-technical">{{ selection.dimension.value_type }}</dd>
          </div>
          <div>
            <dt>Compatibility</dt>
            <dd class="sim-technical">
              {{ selection.dimension.compatible ? 'COMPATIBLE' : 'N/A' }}
            </dd>
          </div>
        </dl>
        <ul class="sim-context-compare-values">
          <li v-for="value in selection.dimension.values" :key="value.result_id">
            <strong class="sim-technical">RUN {{ shortDigest(value.run_id) }}</strong>
            <span class="sim-technical">{{ value.display }}</span>
            <code class="sim-technical">{{ value.source_ref }}</code>
          </li>
        </ul>
      </section>

      <section
        v-else-if="selection.kind === 'candidate-evidence' || mode === 'candidate-evidence'"
        class="sim-context-section"
      >
        <p class="sim-kicker">SOURCE PREVIEW BOUNDARY</p>
        <h3>W03 مصدر فقط</h3>
        <div class="sim-sealed-note">
          <strong>ZERO WRITE</strong>
          <span>لا يُنشأ W04 Evidence أو Review أو Decision أو Mastery.</span>
        </div>
        <dl class="sim-context-facts">
          <div>
            <dt>Preview status</dt>
            <dd class="sim-technical">{{ result.analytics.candidate_evidence.status }}</dd>
          </div>
          <div>
            <dt>W04 state</dt>
            <dd class="sim-technical">{{ result.analytics.candidate_evidence.w04_state }}</dd>
          </div>
          <div>
            <dt>Result</dt>
            <dd class="sim-technical">{{ displayValue(envelope.result_id) }}</dd>
          </div>
          <div>
            <dt>Effective revision</dt>
            <dd class="sim-technical">{{ displayValue(envelope.effective_revision_id) }}</dd>
          </div>
        </dl>
      </section>

      <section v-else class="sim-context-section" data-testid="result-overview-context">
        <p class="sim-kicker">RESULT IDENTITY</p>
        <h3>الهوية الفعالة والمصدر</h3>
        <dl class="sim-context-facts">
          <div>
            <dt>Canonical Result</dt>
            <dd class="sim-technical">{{ shortDigest(result.id) }}</dd>
          </div>
          <div>
            <dt>Canonical Run</dt>
            <dd class="sim-technical">{{ shortDigest(result.run_id) }}</dd>
          </div>
          <div>
            <dt>Effective revision</dt>
            <dd class="sim-technical">{{ shortDigest(effective.id) }}</dd>
          </div>
          <div>
            <dt>Outcome</dt>
            <dd class="sim-technical">{{ effective.outcome ?? 'N/A' }}</dd>
          </div>
          <div>
            <dt>Score</dt>
            <dd class="sim-technical">{{ effective.score ?? 'N/A' }}</dd>
          </div>
          <div>
            <dt>Provenance</dt>
            <dd class="sim-technical">{{ result.provenance }}</dd>
          </div>
        </dl>
      </section>

      <section class="sim-context-section" data-testid="result-interpretation-boundary">
        <h3>حدود الملكية والتفسير</h3>
        <p class="sim-context-copy">
          canonical Result ثابتة، والمراجعات Append-only. ‏Replay وAAR وCompare إسقاطات قراءة فقط،
          وCandidate Evidence لا تدّعي أي حالة تابعة لـ W04.
        </p>
      </section>

      <div class="sim-rule-note">
        Result ≠ Evidence. لا تُحوّل التحليلات إلى حقيقة Canonical جديدة.
      </div>
    </template>
  </aside>
</template>
