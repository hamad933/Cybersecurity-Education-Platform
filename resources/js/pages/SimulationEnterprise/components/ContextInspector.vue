<script setup lang="ts">
import { computed, ref } from 'vue';
import type { EnterpriseItem, LabItem, ResultItem, RunItem, ScenarioItem } from '../types';
import { fieldEntries } from '../utils';

const props = defineProps<{
  enterprise: EnterpriseItem | null;
  scenario: ScenarioItem | null;
  lab: LabItem | null;
  run: RunItem | null;
  result: ResultItem | null;
}>();
const emit = defineEmits<{ handoff: [claim: string] }>();
const claim = ref(
  'مرشح دليل مشتق من نتيجة المحاكاة المختومة؛ يخضع لاحقًا لعملية Intake في Progress & Evidence.',
);

const runInterpretation = computed(() => {
  const lifecycle = props.run?.lifecycle;
  const messages: Record<string, string> = {
    PREPARED: 'تم تثبيت مدخلات التشغيل، لكن التنفيذ لم يَعبر بوابة الجاهزية بعد.',
    READY: 'التشغيل جاهز للبدء، والمدخلات المثبتة لم تدخل التنفيذ بعد.',
    RUNNING: 'التنفيذ الداخلي نشط الآن؛ إجراءات Operations هي المسار المصرّح لتغيير الحالة.',
    PAUSED: 'الحالة التشغيلية مجمّدة مؤقتًا ويمكن استئنافها عبر Operations.',
    COMPLETED: 'انتهى التنفيذ الداخلي؛ يمكن ختم Result إذا لم توجد نتيجة تاريخية بعد.',
    STOPPED: 'أُوقف التشغيل قبل الاستمرار؛ الحقائق المسجلة تبقى جزءًا من أثر التشغيل.',
    FAILED: 'أنهى محرك المحاكاة التشغيل بحالة فشل؛ يلزم تفسير النتيجة دون إعادة كتابة التاريخ.',
  };
  return lifecycle ? (messages[lifecycle] ?? 'يعرض الخادم حالة تشغيل غير مصنّفة في واجهة V1.') : '';
});

const resultInterpretation = computed(() => {
  const outcome = props.result?.outcome;
  const messages: Record<string, string> = {
    ACHIEVED: 'النتيجة المختومة تسجل تحقيق المعيار المحدد لهذا التشغيل فقط.',
    PARTIAL: 'النتيجة المختومة تسجل تحققًا جزئيًا داخل هذا التشغيل، دون استنتاج Mastery.',
    NOT_ACHIEVED: 'النتيجة المختومة تسجل عدم تحقق المعيار في هذا التشغيل فقط.',
    INCONCLUSIVE: 'الحقائق المختومة لا تكفي لحسم النتيجة وفق التقييم المسجل.',
    NOT_EVALUATED: 'تم ختم التاريخ التشغيلي دون حكم تقييم نهائي.',
  };
  return outcome ? (messages[outcome] ?? 'لا توجد دلالة تحليلية إضافية معرّفة لهذا Outcome.') : '';
});
</script>

<template>
  <aside class="right-zone" data-zone="right" aria-label="السياق والتفسير">
    <div class="boundary-note">
      <strong>حدّ التنفيذ</strong>
      <span class="technical" dir="ltr">INTERNAL HIGH-FIDELITY SIMULATION</span>
      <small>لا تعرض هذه الواجهة Provider أو Runtime خارجيًا غير موجود في البيانات الحالية.</small>
    </div>

    <section v-if="enterprise" class="inspector" data-testid="enterprise-object-context">
      <p class="rail-kicker">Selected Object</p><h2>سياق المؤسسة</h2>
      <p>{{ enterprise.description_ar || 'لا يوجد وصف إضافي مستلم لهذا التعريف.' }}</p>
      <dl class="property-list">
        <dt>Enterprise Slug</dt><dd class="technical" dir="ltr">{{ enterprise.slug }}</dd>
        <dt>Digital Twin Revision</dt><dd class="technical" dir="ltr">{{ enterprise.digital_twin_revision?.revision ?? '—' }}</dd>
        <dt>Baseline Revision</dt><dd class="technical" dir="ltr">{{ enterprise.baseline?.revision ?? '—' }}</dd>
        <dt>Twin Digest</dt><dd class="technical wrap" dir="ltr">{{ enterprise.digital_twin_revision?.digest ?? '—' }}</dd>
        <dt>Baseline Digest</dt><dd class="technical wrap" dir="ltr">{{ enterprise.baseline?.digest ?? '—' }}</dd>
      </dl>
      <div v-if="fieldEntries(enterprise.definition).length" class="inspector-group">
        <h3>خصائص التعريف</h3>
        <div class="kv-list"><div v-for="field in fieldEntries(enterprise.definition)" :key="field.key"><span class="technical" dir="ltr">{{ field.key }}</span><strong>{{ field.value }}</strong></div></div>
      </div>
    </section>

    <section v-else-if="scenario" class="inspector" data-testid="scenario-properties">
      <p class="rail-kicker">Properties &amp; Validation</p><h2>خصائص السيناريو</h2>
      <dl class="property-list">
        <dt>Revision</dt><dd class="technical" dir="ltr">{{ scenario.revision }}</dd>
        <dt>Slug</dt><dd class="technical" dir="ltr">{{ scenario.slug }}</dd>
        <dt>Baseline</dt><dd class="technical wrap" dir="ltr">{{ scenario.baseline_id }}</dd>
        <dt>Digest</dt><dd class="technical wrap" dir="ltr">{{ scenario.digest }}</dd>
      </dl>
      <div class="inspector-group"><h3>Validation</h3><div v-if="fieldEntries(scenario.validation).length" class="kv-list"><div v-for="field in fieldEntries(scenario.validation)" :key="field.key"><span class="technical" dir="ltr">{{ field.key }}</span><strong>{{ field.value }}</strong></div></div><p v-else class="truthful-unavailable">لا توجد Validation منظّمة مستلمة.</p></div>
    </section>

    <section v-else-if="lab" class="inspector" data-testid="lab-properties">
      <p class="rail-kicker">Properties</p><h2>خصائص المختبر</h2>
      <dl class="property-list">
        <dt>Revision</dt><dd class="technical" dir="ltr">{{ lab.revision }}</dd>
        <dt>Slug</dt><dd class="technical" dir="ltr">{{ lab.slug }}</dd>
        <dt>Baseline</dt><dd class="technical wrap" dir="ltr">{{ lab.baseline_id }}</dd>
        <dt>Digest</dt><dd class="technical wrap" dir="ltr">{{ lab.digest }}</dd>
      </dl>
      <div class="inspector-group"><h3>Validation</h3><div v-if="fieldEntries(lab.validation).length" class="kv-list"><div v-for="field in fieldEntries(lab.validation)" :key="field.key"><span class="technical" dir="ltr">{{ field.key }}</span><strong>{{ field.value }}</strong></div></div><p v-else class="truthful-unavailable">لا توجد Validation منظّمة مستلمة.</p></div>
    </section>

    <section v-else-if="run" class="inspector" data-testid="run-interpretation">
      <p class="rail-kicker">Interpretation</p><h2>قراءة الحالة</h2>
      <p class="interpretation-copy">{{ runInterpretation }}</p>
      <div class="interpretation-card"><small>Trace</small><strong v-if="run.runtime_state.trace_digest" class="available-mark">متاح من Runtime State</strong><strong v-else class="muted-label">غير مستلم</strong></div>
      <div class="interpretation-card"><small>Result</small><strong>{{ run.result_id ? 'تم ختم نتيجة تاريخية' : 'لا توجد نتيجة مختومة بعد' }}</strong></div>
      <p class="context-rule">هذا العمود يفسّر الحالة فقط؛ Machine State وإجراءات Operations تبقى في سطح التشغيل.</p>
    </section>

    <section v-else-if="result" class="inspector" data-testid="result-analysis">
      <p class="rail-kicker">Analytical Interpretation</p><h2>قراءة النتيجة</h2>
      <p class="interpretation-copy">{{ resultInterpretation }}</p>
      <p class="context-rule">Result ليس Evidence مقبولًا، وليس Review، وليس Mastery. أي Handoff هنا يبقى Candidate فقط.</p>
      <div class="inspector-group">
        <h3>Candidate Evidence Handoff</h3>
        <div v-if="result.candidate_evidence_handoff" class="handoff-state">
          <span class="muted-label">الحالة</span><strong class="technical" dir="ltr">{{ result.candidate_evidence_handoff.status }}</strong>
          <small v-if="result.candidate_evidence_handoff.intake_contract_ref" class="technical wrap" dir="ltr">{{ result.candidate_evidence_handoff.intake_contract_ref }}</small>
        </div>
        <form v-else class="handoff-form" @submit.prevent="emit('handoff', claim)">
          <label><span>Claim المرشح</span><textarea v-model="claim" rows="5" maxlength="1000" /></label>
          <button class="primary-action" type="submit">إعداد Candidate Handoff</button>
        </form>
      </div>
    </section>

    <p v-else class="truthful-unavailable">اختر سجلًا من الفهرس لعرض سياقه الفريد.</p>
  </aside>
</template>
