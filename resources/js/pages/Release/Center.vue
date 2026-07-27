<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import AppShell from '../../components/AppShell.vue';
import StateLabel from '../../components/StateLabel.vue';

type Check = { status: string; detail: string };
type QueueItem = {
  kind: string;
  knowledge_unit_id: string;
  case_id: string | null;
  score: number;
  reason_code: string;
  reason: string;
  source_reference: string;
};
type SearchResult = {
  type: string;
  id: string;
  title_ar: string;
  title_en: string;
  facets: Record<string, unknown>;
};
type SourceImport = {
  id: string;
  original_name: string;
  status: string;
  sha256: string;
  created_at: string;
};
type AiResult = { id: string; status: string; result_digest: string; imported_at: string };
type Backup = {
  id: string;
  portable_package_id: string;
  status: string;
  content_digest: string;
  created_at: string;
};

const props = defineProps<{
  readiness: { ready: boolean; checks: Record<string, Check> };
  dailyQueue: QueueItem[];
  query: string;
  searchResults: SearchResult[];
  sourceImports: SourceImport[];
  aiResults: AiResult[];
  backups: Backup[];
  manualAiPolicy: { execution: string; automatic_provider: boolean; automatic_publish: boolean };
}>();

const MANUAL_AI_EXECUTION = 'MANUAL_ONLY' as const;

const sourceForm = useForm<{ source: File | null }>({ source: null });
const promptForm = useForm({ purpose: '', knowledge_unit_id: 'KU-AD-02', instruction: '' });
const aiImportForm = useForm<{ package: File | null }>({ package: null });
const evidenceForm = useForm<{ package: File | null }>({ package: null });
const restoreForm = useForm<{ package: File | null }>({ package: null });
const backupForm = useForm({});

const pick = (event: Event): File | null => (event.target as HTMLInputElement).files?.[0] ?? null;
</script>

<template>
  <Head title="مركز إصدار V1" />
  <AppShell>
    <header class="max-w-4xl" aria-labelledby="release-title">
      <p class="text-sm font-bold text-cyan-300" dir="ltr">TASK-010 / V1 RELEASE</p>
      <h1 id="release-title" class="mt-2 text-3xl font-bold sm:text-4xl">
        مركز التكامل والتشغيل المحلي
      </h1>
      <p class="mt-3 leading-8 text-slate-300">
        يجمع هذا المركز البحث، قائمة اليوم، الاستيراد الآمن، جسر الذكاء الاصطناعي اليدوي، النسخ
        الاحتياطي، والتحقق من جاهزية الإصدار. لا توجد موصلات إنتاجية أو نشر تلقائي أو مزوّد ذكاء
        اصطناعي شبكي.
      </p>
    </header>

    <section
      class="mt-8 rounded-2xl border border-slate-700 bg-slate-900/70 p-6"
      aria-labelledby="readiness-heading"
    >
      <div class="flex flex-wrap items-center justify-between gap-4">
        <h2 id="readiness-heading" class="text-2xl font-bold">جاهزية الإصدار</h2>
        <StateLabel :status="props.readiness.ready ? 'PASS' : 'FAIL'" />
      </div>
      <dl class="mt-5 grid gap-4 lg:grid-cols-2">
        <div
          v-for="(check, name) in props.readiness.checks"
          :key="name"
          class="rounded-xl border border-slate-700 p-4"
        >
          <dt class="flex flex-wrap items-center justify-between gap-3">
            <bdi dir="ltr" class="font-semibold text-cyan-200">{{ name }}</bdi>
            <StateLabel :status="check.status" />
          </dt>
          <dd class="mt-3 text-sm leading-6 text-slate-300">{{ check.detail }}</dd>
        </div>
      </dl>
    </section>

    <div class="mt-8 grid gap-8 xl:grid-cols-2">
      <section
        class="rounded-2xl border border-slate-700 bg-slate-900/70 p-6"
        aria-labelledby="queue-heading"
      >
        <h2 id="queue-heading" class="text-2xl font-bold">قائمة اليوم المفسّرة</h2>
        <ol class="mt-5 space-y-4">
          <li
            v-for="item in props.dailyQueue"
            :key="`${item.kind}-${item.knowledge_unit_id}-${item.case_id}`"
            class="rounded-xl border border-slate-700 p-4"
          >
            <div class="flex flex-wrap justify-between gap-3">
              <bdi dir="ltr" class="font-bold text-cyan-200">{{ item.knowledge_unit_id }}</bdi>
              <span>الأولوية {{ item.score }}</span>
            </div>
            <p class="mt-2">{{ item.reason }}</p>
            <p class="mt-2 text-xs text-slate-400" dir="ltr">
              {{ item.reason_code }} · {{ item.source_reference }}
            </p>
          </li>
          <li v-if="props.dailyQueue.length === 0" class="text-slate-400">
            لا توجد مراجعات مستحقة حالياً.
          </li>
        </ol>
      </section>

      <section
        class="rounded-2xl border border-slate-700 bg-slate-900/70 p-6"
        aria-labelledby="search-heading"
      >
        <h2 id="search-heading" class="text-2xl font-bold">البحث المحلي</h2>
        <form class="mt-5 flex gap-3" method="get" action="/release">
          <label class="sr-only" for="release-search">عبارة البحث</label>
          <input
            id="release-search"
            name="q"
            class="form-input"
            :value="props.query"
            maxlength="200"
          />
          <button class="focus-ring rounded-lg bg-cyan-400 px-5 font-bold text-slate-950">
            بحث
          </button>
        </form>
        <ul class="mt-5 space-y-3">
          <li
            v-for="result in props.searchResults"
            :key="`${result.type}-${result.id}`"
            class="rounded-xl border border-slate-700 p-4"
          >
            <p class="font-bold">{{ result.title_ar || result.title_en }}</p>
            <p class="mt-1 text-sm text-slate-400" dir="ltr">{{ result.type }} · {{ result.id }}</p>
          </li>
        </ul>
      </section>
    </div>

    <section class="mt-8 grid gap-6 lg:grid-cols-3" aria-label="عمليات الإصدار المقيدة">
      <form
        class="rounded-2xl border border-slate-700 bg-slate-900/70 p-5"
        @submit.prevent="sourceForm.post('/release/sources/import', { forceFormData: true })"
      >
        <h2 class="text-xl font-bold">استيراد مصدر</h2>
        <p class="mt-2 text-sm text-slate-400">
          أنواع مسموحة فقط: <bdi dir="ltr">TXT, MD, JSON, PDF</bdi>.
        </p>
        <input
          class="focus-ring mt-4 block w-full"
          type="file"
          accept=".txt,.md,.json,.pdf"
          required
          @change="sourceForm.source = pick($event)"
        />
        <button
          class="focus-ring mt-4 rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950"
          :disabled="sourceForm.processing"
        >
          تحقق وسجّل
        </button>
      </form>

      <form
        class="rounded-2xl border border-slate-700 bg-slate-900/70 p-5"
        @submit.prevent="promptForm.post('/release/ai/prompts/export')"
      >
        <h2 class="text-xl font-bold">تصدير Prompt يدوي</h2>
        <label class="mt-3 block text-sm" for="purpose">الغرض</label>
        <input
          id="purpose"
          v-model="promptForm.purpose"
          class="form-input mt-1"
          maxlength="120"
          required
        />
        <label class="mt-3 block text-sm" for="ku">Knowledge Unit</label>
        <input
          id="ku"
          v-model="promptForm.knowledge_unit_id"
          class="form-input direction-ltr mt-1"
          maxlength="80"
          required
        />
        <label class="mt-3 block text-sm" for="instruction">التعليمات</label>
        <textarea
          id="instruction"
          v-model="promptForm.instruction"
          class="form-input mt-1 min-h-28"
          maxlength="10000"
          required
        />
        <button class="focus-ring mt-4 rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950">
          إنشاء الحزمة
        </button>
      </form>

      <form
        class="rounded-2xl border border-slate-700 bg-slate-900/70 p-5"
        @submit.prevent="aiImportForm.post('/release/ai/results/import', { forceFormData: true })"
      >
        <h2 class="text-xl font-bold">استيراد نتيجة AI</h2>
        <p class="mt-2 text-sm text-slate-400">تُحفظ كاقتراح بانتظار قرار بشري؛ لا نشر تلقائي.</p>
        <input
          class="focus-ring mt-4 block w-full"
          type="file"
          accept=".zip"
          required
          @change="aiImportForm.package = pick($event)"
        />
        <button class="focus-ring mt-4 rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950">
          تحقق واستورد
        </button>
      </form>

      <form
        class="rounded-2xl border border-slate-700 bg-slate-900/70 p-5"
        @submit.prevent="evidenceForm.post('/release/evidence/import', { forceFormData: true })"
      >
        <h2 class="text-xl font-bold">استيراد Evidence خارجي</h2>
        <p class="mt-2 text-sm text-slate-400">
          يُمنع وسم الأدلة الخارجية بأنها <bdi dir="ltr">SIMULATED</bdi>.
        </p>
        <input
          class="focus-ring mt-4 block w-full"
          type="file"
          accept=".zip"
          required
          @change="evidenceForm.package = pick($event)"
        />
        <button class="focus-ring mt-4 rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950">
          استيراد للمراجعة
        </button>
      </form>

      <form
        class="rounded-2xl border border-slate-700 bg-slate-900/70 p-5"
        @submit.prevent="backupForm.post('/release/backups')"
      >
        <h2 class="text-xl font-bold">نسخة احتياطية منطقية</h2>
        <p class="mt-2 text-sm text-slate-400">
          تشمل قاعدة البيانات والـBlobs مع Hashes، وتستبعد Sessions وQueue runtime والأسرار.
        </p>
        <button class="focus-ring mt-4 rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950">
          إنشاء نسخة موثّقة
        </button>
      </form>

      <form
        class="rounded-2xl border border-slate-700 bg-slate-900/70 p-5"
        @submit.prevent="restoreForm.post('/release/restores/stage', { forceFormData: true })"
      >
        <h2 class="text-xl font-bold">فحص Restore مرحلي</h2>
        <p class="mt-2 text-sm text-slate-400">
          واجهة الويب تتحقق وتُرحّل فقط؛ التطبيق الفعلي محصور بقاعدة
          <bdi dir="ltr">*_restore_drill</bdi>.
        </p>
        <input
          class="focus-ring mt-4 block w-full"
          type="file"
          accept=".zip"
          required
          @change="restoreForm.package = pick($event)"
        />
        <button class="focus-ring mt-4 rounded-lg bg-cyan-400 px-4 py-2 font-bold text-slate-950">
          تحقق دون تفعيل
        </button>
      </form>
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-3" aria-label="سجلات الإصدار">
      <article class="rounded-2xl border border-slate-700 p-5">
        <h2 class="text-xl font-bold">آخر المصادر</h2>
        <ul class="mt-4 space-y-3 text-sm">
          <li v-for="item in props.sourceImports" :key="item.id">
            <StateLabel :status="item.status" /> <span class="ms-2">{{ item.original_name }}</span>
          </li>
        </ul>
      </article>
      <article class="rounded-2xl border border-slate-700 p-5">
        <h2 class="text-xl font-bold">نتائج AI</h2>
        <ul class="mt-4 space-y-3 text-sm">
          <li v-for="item in props.aiResults" :key="item.id">
            <StateLabel :status="item.status" />
            <bdi dir="ltr" class="ms-2">{{ item.result_digest.slice(0, 12) }}</bdi>
          </li>
        </ul>
      </article>
      <article class="rounded-2xl border border-slate-700 p-5">
        <h2 class="text-xl font-bold">النسخ الاحتياطية</h2>
        <ul class="mt-4 space-y-3 text-sm">
          <li v-for="item in props.backups" :key="item.id">
            <StateLabel :status="item.status" />
            <a
              class="focus-ring ms-2 underline"
              :href="`/release/packages/${item.portable_package_id}`"
              >تنزيل</a
            >
          </li>
        </ul>
      </article>
    </section>

    <aside
      class="mt-8 rounded-xl border border-amber-700 bg-amber-950/40 p-5"
      aria-label="حدود جسر الذكاء الاصطناعي"
    >
      <strong>حد ثابت:</strong>
      <bdi dir="ltr" class="ms-2">{{ MANUAL_AI_EXECUTION }}</bdi>
      — لا مزوّد تلقائي: {{ props.manualAiPolicy.automatic_provider ? 'نعم' : 'لا' }}، ولا نشر
      تلقائي: {{ props.manualAiPolicy.automatic_publish ? 'نعم' : 'لا' }}.
    </aside>
  </AppShell>
</template>
