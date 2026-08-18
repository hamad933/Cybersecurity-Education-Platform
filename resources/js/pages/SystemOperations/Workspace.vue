<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

type Surface =
  | 'health'
  | 'processing'
  | 'validation'
  | 'ai-bridge'
  | 'backups'
  | 'audit'
  | 'releases'
  | 'configuration';
type Counts = Record<string, number>;
type CheckMap = Record<string, string>;
type ReleaseCheck = { status: string; detail: string };
type ReleaseGate = { ready: boolean; checks: Record<string, ReleaseCheck> };
type ProcessingRun = {
  id: string;
  type: string;
  input_digest: string;
  status: string;
  attempt_count: number;
  max_attempts: number;
  worker_identifier: string | null;
  started_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  error_category: string | null;
  safe_error_message: string | null;
  created_at: string;
};
type OutboxMessage = {
  id: string;
  type: string;
  producer_module: string;
  correlation_id: string;
  dispatch_state: string;
  attempts: number;
  occurred_at: string;
  next_attempt_at: string | null;
  dispatched_at: string | null;
};
type PackageRecord = {
  id: string;
  package_type: string;
  schema_version?: number;
  owner_module: string;
  scope?: unknown;
  manifest?: unknown;
  package_digest: string;
  status: string;
  created_at: string;
};
type SourceImport = {
  id: string;
  original_name: string;
  detected_media_type: string;
  extension: string;
  size_bytes: number;
  sha256: string;
  status: string;
  rejection_code: string | null;
  created_at: string;
};
type Prompt = {
  id: string;
  purpose: string;
  status: string;
  current_revision: number;
  created_at: string;
};
type PromptRevision = {
  id: string;
  prompt_package_id: string;
  revision: number;
  portable_package_id: string;
  input_digest: string;
  declared_scope: unknown;
  exported_at: string;
  prompt_purpose: string;
  prompt_status: string;
  prompt_current_revision: number;
  package_type: string;
  package_digest: string;
  package_scope: unknown;
  package_manifest: unknown;
  package_status: string;
};
type AiResult = {
  id: string;
  prompt_package_revision_id: string;
  portable_package_id: string;
  result_digest: string;
  structured_result: unknown;
  status: string;
  imported_at: string;
  prompt_package_id: string;
  prompt_revision: number;
  prompt_input_digest: string;
  declared_scope: unknown;
  prompt_portable_package_id: string;
  prompt_purpose: string;
  prompt_status: string;
  returned_package_type: string;
  returned_package_digest: string;
  returned_package_scope: unknown;
  returned_package_manifest: unknown;
  returned_package_status: string;
};
type AiDecision = {
  id: string;
  imported_ai_result_id: string;
  decision: string;
  rationale: string;
  lesson_revision_id: string | null;
  decided_at: string;
};
type Backup = {
  id: string;
  portable_package_id: string;
  status: string;
  database_driver: string;
  content_digest: string;
  created_at: string;
};
type Restore = {
  id: string;
  backup_manifest_id: string;
  target_database: string;
  status: string;
  verification: unknown;
  started_at: string;
  completed_at: string | null;
};
type AuditRecord = {
  id: string;
  sequence_no: number;
  actor_identifier: string | null;
  action: string;
  target_type: string;
  target_identifier: string | null;
  correlation_id: string;
  outcome: string;
  safe_metadata: unknown;
  occurred_at: string;
  previous_hash: string | null;
  record_hash: string;
};
type WorkspaceState = {
  foundation?: { checks: CheckMap; healthy: boolean; failed_checks: string[] };
  processing?: { counts: Counts; runs?: ProcessingRun[] };
  outbox?: { counts: Counts; messages?: OutboxMessage[] };
  packages?: { counts?: Counts; records?: PackageRecord[] } | PackageRecord[];
  release_gate?: ReleaseGate;
  source_imports?: { counts: Counts; records: SourceImport[] };
  scope?: {
    technical_validation_only: boolean;
    knowledge_quality_decisions: boolean;
    canonical_knowledge_decisions: boolean;
  };
  policy?: {
    execution?: string;
    automatic_provider_enabled?: boolean;
    automatic_publish?: boolean;
    polling?: boolean;
    embeddings?: boolean;
    append_only?: boolean;
    destructive_http_actions?: boolean;
  };
  prompts?: Prompt[];
  prompt_revisions?: PromptRevision[];
  results?: AiResult[];
  decisions?: AiDecision[];
  backups?: Backup[];
  restores?: Restore[];
  safety?: { web_restore_mode: string; activation_route_available: boolean };
  chain?: { valid: boolean; count: number };
  records?: AuditRecord[];
  readiness?: ReleaseGate;
  authorization?: {
    deployment_authorized: boolean;
    deployment_workflow_available: boolean;
    scope: string;
  };
  profile?: string;
  auth_bypass?: boolean;
  force_https?: boolean;
  blob_disk?: string;
  queue_connection?: string;
  release_loopback_only?: boolean;
  ai_network_provider_enabled?: boolean;
  limits?: Record<string, number>;
};

const props = defineProps<{ surface: Surface; state: WorkspaceState }>();
const navigation: Array<{ key: Surface; label: string; href: string }> = [
  { key: 'health', label: 'الصحة', href: '/system' },
  { key: 'processing', label: 'المعالجة', href: '/system/processing' },
  { key: 'validation', label: 'التحقق', href: '/system/validation' },
  { key: 'ai-bridge', label: 'جسر AI اليدوي', href: '/system/ai-bridge' },
  { key: 'backups', label: 'النسخ والاستعادة', href: '/system/backups' },
  { key: 'audit', label: 'التدقيق', href: '/system/audit' },
  { key: 'releases', label: 'الإصدارات', href: '/system/releases' },
  { key: 'configuration', label: 'التهيئة', href: '/system/configuration' },
];
const titles: Record<Surface, string> = {
  health: 'الصحة التشغيلية',
  processing: 'المعالجة والطوابير',
  validation: 'التحقق التقني',
  'ai-bridge': 'جسر الذكاء الاصطناعي اليدوي',
  backups: 'النسخ الاحتياطي والاستعادة',
  audit: 'الحقيقة التشغيلية وسجل التدقيق',
  releases: 'التحقق من الإصدار',
  configuration: 'تهيئة المنتج المحلية',
};
const title = computed(() => titles[props.surface]);
const sourceForm = useForm<{ source: File | null }>({ source: null });
const promptForm = useForm({ purpose: '', knowledge_unit_id: '', instruction: '' });
const aiImportForm = useForm<{ package: File | null }>({ package: null });
const backupForm = useForm({});
const restoreForm = useForm<{ package: File | null }>({ package: null });
const decisionRationales = reactive<Record<string, string>>({});
const pick = (event: Event): File | null => (event.target as HTMLInputElement).files?.[0] ?? null;
const decideAi = (resultId: string, decision: 'ACCEPT_AS_DRAFT' | 'REJECT') => {
  const rationale = (decisionRationales[resultId] ?? '').trim();
  if (!rationale) return;
  router.post(
    `/system/ai-bridge/results/${resultId}/decide`,
    { decision, rationale },
    { preserveScroll: true },
  );
};
const count = (counts: Counts | undefined, key: string): number => counts?.[key] ?? 0;
const when = (value: string | null | undefined): string =>
  value ? new Date(value).toLocaleString('ar-YE') : '—';
const short = (value: string | null | undefined, length = 12): string =>
  value ? value.slice(0, length) : '—';
const jsonText = (value: unknown): string => {
  if (value === null || value === undefined) return '—';
  if (typeof value === 'string') {
    try {
      return JSON.stringify(JSON.parse(value), null, 2);
    } catch {
      return value;
    }
  }
  return JSON.stringify(value, null, 2) ?? String(value);
};
const packagesAsRecords = computed<PackageRecord[]>(() =>
  Array.isArray(props.state.packages)
    ? props.state.packages
    : (props.state.packages?.records ?? []),
);
const packageCounts = computed<Counts>(() =>
  Array.isArray(props.state.packages) ? {} : (props.state.packages?.counts ?? {}),
);
</script>

<template>
  <Head :title="`النظام والعمليات — ${title}`" />
  <div class="system-workspace" dir="rtl">
    <header class="workspace-top">
      <div>
        <p class="eyebrow" dir="ltr">CEP / SYSTEM &amp; OPERATIONS</p>
        <h1>{{ title }}</h1>
        <p class="subtitle">
          حالة تشغيلية فعلية من خدمات CEP وقاعدة البيانات؛ لا مؤشرات توضيحية أو ادعاءات نشر.
        </p>
      </div>
      <div class="top-actions" aria-label="أدوات المساحة الحالية">
        <a v-if="surface === 'validation'" href="#source-import" class="tool-link">
          استيراد للتحقق
        </a>
        <a v-if="surface === 'ai-bridge'" href="#manual-ai-export" class="tool-link">
          تجهيز Prompt
        </a>
        <a v-if="surface === 'backups'" href="#backup-create" class="tool-link">إنشاء Backup</a>
        <span
          v-if="!['validation', 'ai-bridge', 'backups'].includes(surface)"
          class="read-only-chip"
        >
          قراءة وفحص
        </span>
      </div>
    </header>

    <div class="workspace-grid">
      <nav class="workspace-left" aria-label="بنية النظام والعمليات">
        <p class="rail-label">البنية</p>
        <a
          v-for="item in navigation"
          :key="item.key"
          :href="item.href"
          :aria-current="surface === item.key ? 'page' : undefined"
          :class="['rail-link', { active: surface === item.key }]"
        >
          {{ item.label }}
        </a>
      </nav>

      <main class="workspace-center">
        <template v-if="surface === 'health' && state.foundation">
          <section class="hero-state">
            <div>
              <p class="section-kicker">الحالة التقنية</p>
              <h2>
                {{
                  state.foundation.healthy
                    ? 'المكوّنات الأساسية اجتازت فحوص الصحة'
                    : 'توجد فحوص أساسية تتطلب الانتباه'
                }}
              </h2>
            </div>
            <span :class="['state-pill', state.foundation.healthy ? 'ok' : 'danger']" dir="ltr">
              {{ state.foundation.healthy ? 'HEALTHY' : 'ATTENTION' }}
            </span>
          </section>
          <section class="panel">
            <h2>فحوص المنصة</h2>
            <div class="status-grid">
              <article
                v-for="(status, name) in state.foundation.checks"
                :key="name"
                class="status-card"
              >
                <bdi dir="ltr">{{ name }}</bdi>
                <span :class="['state-pill', status === 'ok' ? 'ok' : 'danger']" dir="ltr">
                  {{ status }}
                </span>
              </article>
            </div>
          </section>
          <section class="panel">
            <h2>الحمل التشغيلي المرصود</h2>
            <div class="metric-strip">
              <div>
                <span>قيد المعالجة</span><strong>{{ count(state.processing?.counts, 'running') }}</strong>
              </div>
              <div>
                <span>بانتظار المعالجة</span><strong>{{ count(state.processing?.counts, 'pending') }}</strong>
              </div>
              <div>
                <span>فشل معالجة</span><strong>{{ count(state.processing?.counts, 'failed') }}</strong>
              </div>
              <div>
                <span>رسائل Outbox فاشلة</span><strong>{{ count(state.outbox?.counts, 'failed') }}</strong>
              </div>
              <div>
                <span>حزم مرفوضة</span><strong>{{ count(packageCounts, 'rejected') }}</strong>
              </div>
            </div>
          </section>
        </template>

        <template v-else-if="surface === 'processing'">
          <section class="panel">
            <p class="section-kicker" dir="ltr">REQUESTED → EXECUTED / CURRENT STATE</p>
            <h2>المعالجة الفعلية مع ربط الطلب بالتنفيذ</h2>
            <div class="metric-strip compact">
              <div>
                <span>Pending</span><strong>{{ count(state.processing?.counts, 'pending') }}</strong>
              </div>
              <div>
                <span>Running</span><strong>{{ count(state.processing?.counts, 'running') }}</strong>
              </div>
              <div>
                <span>Completed</span><strong>{{ count(state.processing?.counts, 'completed') }}</strong>
              </div>
              <div>
                <span>Failed</span><strong>{{ count(state.processing?.counts, 'failed') }}</strong>
              </div>
            </div>
            <div class="record-list">
              <article v-for="run in state.processing?.runs ?? []" :key="run.id" class="trace-card">
                <div class="trace-heading">
                  <div>
                    <strong><bdi dir="ltr">{{ run.type }}</bdi></strong>
                    <small><bdi dir="ltr">{{ run.id }}</bdi></small>
                  </div>
                  <span class="state-pill" dir="ltr">{{ run.status }}</span>
                </div>
                <dl class="trace-grid">
                  <div>
                    <dt>REQUESTED · input digest</dt>
                    <dd class="mono break-all" dir="ltr">{{ run.input_digest }}</dd>
                  </div>
                  <div>
                    <dt>EXECUTED · attempts</dt>
                    <dd dir="ltr">{{ run.attempt_count }} / {{ run.max_attempts }}</dd>
                  </div>
                  <div>
                    <dt>CAPTURED · worker</dt>
                    <dd class="mono" dir="ltr">{{ run.worker_identifier || '—' }}</dd>
                  </div>
                  <div>
                    <dt>CURRENT STATE · timing</dt>
                    <dd>
                      {{ when(run.started_at) }} → {{ when(run.completed_at || run.cancelled_at) }}
                    </dd>
                  </div>
                </dl>
                <p v-if="run.safe_error_message" class="diagnostic">
                  <bdi dir="ltr">{{ run.error_category || 'uncategorized' }}</bdi> ·
                  {{ run.safe_error_message }}
                </p>
              </article>
              <p v-if="(state.processing?.runs ?? []).length === 0" class="empty">
                لا توجد Processing Runs مسجلة.
              </p>
            </div>
          </section>
          <section class="panel">
            <p class="section-kicker">Transactional Outbox</p>
            <h2>حالة التسليم الملتقطة</h2>
            <div class="record-list">
              <article
                v-for="message in state.outbox?.messages ?? []"
                :key="message.id"
                class="record-row"
              >
                <div>
                  <strong><bdi dir="ltr">{{ message.type }}</bdi></strong>
                  <small dir="ltr">correlation={{ message.correlation_id }}</small>
                </div>
                <span class="state-pill" dir="ltr">{{ message.dispatch_state }}</span>
              </article>
              <p v-if="(state.outbox?.messages ?? []).length === 0" class="empty">
                لا توجد Outbox messages مسجلة.
              </p>
            </div>
          </section>
        </template>

        <template v-else-if="surface === 'validation'">
          <section id="source-import" class="panel">
            <p class="section-kicker">Technical ingestion</p>
            <h2>استيراد مصدر للتحقق التقني</h2>
            <p class="muted">
              يفحص الملف ويسجّل نتيجة الاستيراد؛ لا يصدر قرارًا عن جودة المعرفة أو صحة الادعاءات.
            </p>
            <form
              class="inline-form"
              @submit.prevent="
                sourceForm.post('/system/validation/sources/import', {
                  forceFormData: true,
                  preserveScroll: true,
                })
              "
            >
              <input
                type="file"
                accept=".txt,.md,.json,.pdf"
                required
                @change="sourceForm.source = pick($event)"
              />
              <button :disabled="sourceForm.processing">تحقق وسجّل</button>
            </form>
          </section>
          <section class="panel">
            <p class="section-kicker" dir="ltr">CAPTURED PACKAGE CONTEXT</p>
            <h2>حزم Portable Packages</h2>
            <div class="record-list">
              <article v-for="pkg in packagesAsRecords" :key="pkg.id" class="trace-card">
                <div class="trace-heading">
                  <div>
                    <strong><bdi dir="ltr">{{ pkg.package_type }}</bdi></strong>
                    <small class="mono" dir="ltr">{{ pkg.id }}</small>
                  </div>
                  <span class="state-pill" dir="ltr">{{ pkg.status }}</span>
                </div>
                <dl class="trace-grid">
                  <div>
                    <dt>Package digest</dt>
                    <dd class="mono break-all" dir="ltr">{{ pkg.package_digest }}</dd>
                  </div>
                  <div>
                    <dt>Owner / schema</dt>
                    <dd dir="ltr">{{ pkg.owner_module }} / v{{ pkg.schema_version ?? '—' }}</dd>
                  </div>
                </dl>
                <details class="context-disclosure">
                  <summary>Scope وManifest المسجلان</summary>
                  <h3>Scope</h3>
                  <pre dir="ltr">{{ jsonText(pkg.scope) }}</pre>
                  <h3>Manifest</h3>
                  <pre dir="ltr">{{ jsonText(pkg.manifest) }}</pre>
                </details>
              </article>
              <p v-if="packagesAsRecords.length === 0" class="empty">لا توجد حزم مسجلة.</p>
            </div>
          </section>
          <section class="panel">
            <h2>نتائج استيراد المصادر</h2>
            <div class="record-list">
              <article
                v-for="item in state.source_imports?.records ?? []"
                :key="item.id"
                class="record-row"
              >
                <div>
                  <strong>{{ item.original_name }}</strong>
                  <small>
                    <bdi dir="ltr">{{ item.detected_media_type }}</bdi> · {{ item.size_bytes }} bytes
                  </small>
                  <small class="mono break-all" dir="ltr">sha256={{ item.sha256 }}</small>
                  <small v-if="item.rejection_code">
                    <bdi dir="ltr">{{ item.rejection_code }}</bdi>
                  </small>
                </div>
                <span class="state-pill" dir="ltr">{{ item.status }}</span>
              </article>
              <p v-if="(state.source_imports?.records ?? []).length === 0" class="empty">
                لا توجد عمليات استيراد مسجلة.
              </p>
            </div>
          </section>
        </template>

        <template v-else-if="surface === 'ai-bridge'">
          <section id="manual-ai-export" class="panel">
            <p class="section-kicker" dir="ltr">MANUAL_ONLY / PROVIDER-NEUTRAL</p>
            <h2>تجهيز حزمة Prompt</h2>
            <p class="muted">
              يتم التصدير فقط. التنفيذ يحدث يدويًا خارج CEP، ثم تعاد النتيجة كحزمة للتحقق والقرار
              البشري.
            </p>
            <form
              class="form-grid"
              @submit.prevent="
                promptForm.post('/system/ai-bridge/prompts/export', { preserveScroll: true })
              "
            >
              <label>الغرض<input v-model="promptForm.purpose" maxlength="120" required /></label>
              <label>
                <span dir="ltr">Knowledge Unit ID</span>
                <input v-model="promptForm.knowledge_unit_id" dir="ltr" maxlength="80" required />
              </label>
              <label class="full">
                التعليمات<textarea v-model="promptForm.instruction" maxlength="10000" required />
              </label>
              <button :disabled="promptForm.processing">إنشاء حزمة التصدير</button>
            </form>
          </section>
          <section class="panel">
            <h2>استيراد نتيجة يدوية</h2>
            <form
              class="inline-form"
              @submit.prevent="
                aiImportForm.post('/system/ai-bridge/results/import', {
                  forceFormData: true,
                  preserveScroll: true,
                })
              "
            >
              <input
                type="file"
                accept=".zip"
                required
                @change="aiImportForm.package = pick($event)"
              />
              <button :disabled="aiImportForm.processing">تحقق واستورد</button>
            </form>
          </section>

          <section class="panel">
            <p class="section-kicker" dir="ltr">REQUESTED</p>
            <h2>سياق الطلب وProvenance الموثوق</h2>
            <div class="record-list">
              <article
                v-for="revision in state.prompt_revisions ?? []"
                :key="revision.id"
                class="trace-card"
              >
                <div class="trace-heading">
                  <div>
                    <strong>{{ revision.prompt_purpose }}</strong>
                    <small dir="ltr">
                      prompt={{ revision.prompt_package_id }} · revision={{ revision.revision }}
                    </small>
                  </div>
                  <span class="state-pill" dir="ltr">{{ revision.prompt_status }}</span>
                </div>
                <dl class="trace-grid">
                  <div>
                    <dt>Prompt revision identity</dt>
                    <dd class="mono" dir="ltr">{{ revision.id }}</dd>
                  </div>
                  <div>
                    <dt>Portable package identity</dt>
                    <dd class="mono" dir="ltr">{{ revision.portable_package_id }}</dd>
                  </div>
                  <div class="wide">
                    <dt>Input digest</dt>
                    <dd class="mono break-all" dir="ltr">{{ revision.input_digest }}</dd>
                  </div>
                  <div class="wide">
                    <dt>Package digest</dt>
                    <dd class="mono break-all" dir="ltr">{{ revision.package_digest }}</dd>
                  </div>
                </dl>
                <details class="context-disclosure">
                  <summary>Declared scope، target، method وpackage context</summary>
                  <h3>Declared scope</h3>
                  <pre dir="ltr">{{ jsonText(revision.declared_scope) }}</pre>
                  <h3>Package scope</h3>
                  <pre dir="ltr">{{ jsonText(revision.package_scope) }}</pre>
                  <h3>Package manifest</h3>
                  <pre dir="ltr">{{ jsonText(revision.package_manifest) }}</pre>
                </details>
              </article>
              <p v-if="(state.prompt_revisions ?? []).length === 0" class="empty">
                لا توجد Prompt revisions مسجلة.
              </p>
            </div>
          </section>

          <section class="panel">
            <p class="section-kicker" dir="ltr">RETURNED / CURRENT STATE</p>
            <h2>النتيجة الكاملة قبل القرار البشري</h2>
            <p class="muted">
              لا تُعرض معاينة مختصرة لاتخاذ القرار. افتح سجل النتيجة لمراجعة proposal الكامل
              وProvenance ثم تظهر أدوات القرار.
            </p>
            <div class="record-list">
              <article
                v-for="result in state.results ?? []"
                :key="result.id"
                class="trace-card result-card"
              >
                <div class="trace-heading">
                  <div>
                    <strong>Returned result</strong>
                    <small class="mono" dir="ltr">{{ result.id }}</small>
                  </div>
                  <span class="state-pill" dir="ltr">{{ result.status }}</span>
                </div>
                <dl class="trace-grid">
                  <div>
                    <dt>Requested prompt</dt>
                    <dd dir="ltr">{{ result.prompt_package_id }} / r{{ result.prompt_revision }}</dd>
                  </div>
                  <div>
                    <dt>Returned package</dt>
                    <dd class="mono" dir="ltr">{{ result.portable_package_id }}</dd>
                  </div>
                  <div class="wide">
                    <dt>Requested input digest</dt>
                    <dd class="mono break-all" dir="ltr">{{ result.prompt_input_digest }}</dd>
                  </div>
                  <div class="wide">
                    <dt>Returned result digest</dt>
                    <dd class="mono break-all" dir="ltr">{{ result.result_digest }}</dd>
                  </div>
                </dl>
                <details class="proposal-review">
                  <summary>مراجعة proposal الكامل وProvenance قبل القرار</summary>
                  <div class="review-section">
                    <h3>Structured result — materially complete</h3>
                    <pre class="proposal-payload" dir="ltr">{{
                      jsonText(result.structured_result)
                    }}</pre>
                  </div>
                  <div class="review-section">
                    <h3>Declared request scope</h3>
                    <pre dir="ltr">{{ jsonText(result.declared_scope) }}</pre>
                  </div>
                  <div class="review-section">
                    <h3>Returned package scope</h3>
                    <pre dir="ltr">{{ jsonText(result.returned_package_scope) }}</pre>
                  </div>
                  <div class="review-section">
                    <h3>Returned package manifest</h3>
                    <pre dir="ltr">{{ jsonText(result.returned_package_manifest) }}</pre>
                  </div>
                  <dl class="trace-grid">
                    <div>
                      <dt>Prompt revision identity</dt>
                      <dd class="mono" dir="ltr">{{ result.prompt_package_revision_id }}</dd>
                    </div>
                    <div>
                      <dt>Prompt package identity</dt>
                      <dd class="mono" dir="ltr">{{ result.prompt_portable_package_id }}</dd>
                    </div>
                    <div>
                      <dt>Returned package type</dt>
                      <dd dir="ltr">{{ result.returned_package_type }}</dd>
                    </div>
                    <div>
                      <dt>Returned package state</dt>
                      <dd dir="ltr">{{ result.returned_package_status }}</dd>
                    </div>
                    <div class="wide">
                      <dt>Returned package digest</dt>
                      <dd class="mono break-all" dir="ltr">{{ result.returned_package_digest }}</dd>
                    </div>
                  </dl>
                  <template v-if="result.status === 'pending_review'">
                    <label class="decision-rationale">
                      مبرر القرار البشري
                      <textarea v-model="decisionRationales[result.id]" maxlength="2000" required />
                    </label>
                    <div class="decision-actions">
                      <button type="button" @click="decideAi(result.id, 'ACCEPT_AS_DRAFT')">
                        قبول كمسودة
                      </button>
                      <button
                        type="button"
                        class="danger-button"
                        @click="decideAi(result.id, 'REJECT')"
                      >
                        رفض
                      </button>
                    </div>
                  </template>
                </details>
              </article>
              <p v-if="(state.results ?? []).length === 0" class="empty">
                لا توجد نتائج AI مستوردة.
              </p>
            </div>
          </section>

          <section v-if="(state.decisions ?? []).length > 0" class="panel">
            <p class="section-kicker" dir="ltr">CAPTURED HUMAN DECISIONS</p>
            <h2>القرارات المسجلة</h2>
            <div class="record-list">
              <article
                v-for="decision in state.decisions ?? []"
                :key="decision.id"
                class="record-row"
              >
                <div>
                  <strong><bdi dir="ltr">{{ decision.decision }}</bdi></strong>
                  <small dir="ltr">result={{ decision.imported_ai_result_id }}</small>
                  <p>{{ decision.rationale }}</p>
                </div>
                <small>{{ when(decision.decided_at) }}</small>
              </article>
            </div>
          </section>
        </template>

        <template v-else-if="surface === 'backups'">
          <section id="backup-create" class="panel">
            <p class="section-kicker">Verified logical backup</p>
            <h2>إنشاء نسخة احتياطية موثّقة</h2>
            <p class="muted">
              تستخدم خدمة Backup الحالية وتنتج Manifest وحزمة قابلة للتحقق. لا يُعرض نجاح قبل اكتمال
              الخدمة.
            </p>
            <form @submit.prevent="backupForm.post('/system/backups', { preserveScroll: true })">
              <button :disabled="backupForm.processing">إنشاء Backup</button>
            </form>
          </section>
          <section class="panel">
            <h2>النسخ المسجلة</h2>
            <div class="record-list">
              <article v-for="backup in state.backups ?? []" :key="backup.id" class="record-row">
                <div>
                  <strong><bdi dir="ltr">{{ backup.database_driver }}</bdi></strong>
                  <small>
                    {{ when(backup.created_at) }} · digest
                    <bdi dir="ltr">{{ short(backup.content_digest) }}</bdi>
                  </small>
                </div>
                <div class="row-actions">
                  <span class="state-pill" dir="ltr">{{ backup.status }}</span>
                  <a :href="`/system/packages/${backup.portable_package_id}`">تنزيل الحزمة</a>
                </div>
              </article>
              <p v-if="(state.backups ?? []).length === 0" class="empty">
                لا توجد نسخ احتياطية مسجلة.
              </p>
            </div>
          </section>
          <details class="danger-zone">
            <summary>استعادة مرحلية — فتح الإجراء المقيد</summary>
            <div class="danger-body">
              <p>واجهة الويب تقوم بـ staging والتحقق فقط. لا يوجد تفعيل Restore عبر HTTP.</p>
              <form
                class="inline-form"
                @submit.prevent="
                  restoreForm.post('/system/backups/restores/stage', {
                    forceFormData: true,
                    preserveScroll: true,
                  })
                "
              >
                <input
                  type="file"
                  accept=".zip"
                  required
                  @change="restoreForm.package = pick($event)"
                />
                <button :disabled="restoreForm.processing">Stage + Verify</button>
              </form>
            </div>
          </details>
          <section class="panel">
            <h2>Restore Runs</h2>
            <div class="record-list">
              <article v-for="restore in state.restores ?? []" :key="restore.id" class="trace-card">
                <div class="trace-heading">
                  <div>
                    <strong><bdi dir="ltr">{{ restore.target_database }}</bdi></strong>
                    <small>{{ when(restore.started_at) }}</small>
                  </div>
                  <span class="state-pill" dir="ltr">{{ restore.status }}</span>
                </div>
                <details class="context-disclosure">
                  <summary>Verification المسجل</summary>
                  <pre dir="ltr">{{ jsonText(restore.verification) }}</pre>
                </details>
              </article>
              <p v-if="(state.restores ?? []).length === 0" class="empty">
                لا توجد Restore Runs مسجلة.
              </p>
            </div>
          </section>
        </template>

        <template v-else-if="surface === 'audit'">
          <section class="hero-state">
            <div>
              <p class="section-kicker">Hash-chained audit</p>
              <h2>
                {{
                  state.chain?.valid
                    ? 'سلسلة التدقيق متماسكة'
                    : 'تعذر إثبات تكامل سلسلة التدقيق'
                }}
              </h2>
            </div>
            <span :class="['state-pill', state.chain?.valid ? 'ok' : 'danger']" dir="ltr">
              {{ state.chain?.valid ? 'VALID' : 'INVALID' }}
            </span>
          </section>
          <section class="panel">
            <p class="section-kicker" dir="ltr">CAPTURED CHRONOLOGY / INVESTIGATION CONTEXT</p>
            <h2>السجل التاريخي القابل للتحقيق</h2>
            <div class="record-list">
              <article
                v-for="record in state.records ?? []"
                :key="record.id"
                class="trace-card audit-card"
              >
                <div class="trace-heading">
                  <div>
                    <strong>
                      <bdi dir="ltr">#{{ record.sequence_no }} · {{ record.action }}</bdi>
                    </strong>
                    <small>{{ when(record.occurred_at) }}</small>
                  </div>
                  <span class="state-pill" dir="ltr">{{ record.outcome }}</span>
                </div>
                <dl class="trace-grid">
                  <div>
                    <dt>Actor identifier</dt>
                    <dd class="mono" dir="ltr">{{ record.actor_identifier || '—' }}</dd>
                  </div>
                  <div>
                    <dt>Target type</dt>
                    <dd class="mono" dir="ltr">{{ record.target_type }}</dd>
                  </div>
                  <div>
                    <dt>Target identifier</dt>
                    <dd class="mono" dir="ltr">{{ record.target_identifier || '—' }}</dd>
                  </div>
                  <div>
                    <dt>Correlation ID</dt>
                    <dd class="mono" dir="ltr">{{ record.correlation_id }}</dd>
                  </div>
                </dl>
                <details class="context-disclosure">
                  <summary>Safe metadata وHash chain context</summary>
                  <h3>Safe metadata</h3>
                  <pre dir="ltr">{{ jsonText(record.safe_metadata) }}</pre>
                  <dl class="trace-grid">
                    <div class="wide">
                      <dt>Previous hash</dt>
                      <dd class="mono break-all" dir="ltr">
                        {{ record.previous_hash || 'GENESIS' }}
                      </dd>
                    </div>
                    <div class="wide">
                      <dt>Record hash</dt>
                      <dd class="mono break-all" dir="ltr">{{ record.record_hash }}</dd>
                    </div>
                  </dl>
                </details>
              </article>
              <p v-if="(state.records ?? []).length === 0" class="empty">لا توجد سجلات تدقيق.</p>
            </div>
          </section>
        </template>

        <template v-else-if="surface === 'releases'">
          <section class="hero-state">
            <div>
              <p class="section-kicker">Release validation gate</p>
              <h2>
                {{
                  state.readiness?.ready
                    ? 'بوابة التحقق لا تحتوي FAIL'
                    : 'بوابة التحقق تحتوي عائقًا'
                }}
              </h2>
            </div>
            <span :class="['state-pill', state.readiness?.ready ? 'ok' : 'danger']" dir="ltr">
              {{ state.readiness?.ready ? 'READY' : 'BLOCKED' }}
            </span>
          </section>
          <section class="panel">
            <h2>فحوص الإصدار</h2>
            <div class="status-grid">
              <article
                v-for="(check, name) in state.readiness?.checks ?? {}"
                :key="name"
                class="status-card wide"
              >
                <div>
                  <bdi dir="ltr">{{ name }}</bdi>
                  <p>{{ check.detail }}</p>
                </div>
                <span class="state-pill" dir="ltr">{{ check.status }}</span>
              </article>
            </div>
          </section>
          <section class="panel">
            <p class="section-kicker" dir="ltr">CAPTURED PACKAGE / FOLLOW-UP CONTEXT</p>
            <h2>حزم مرتبطة بالتحقق</h2>
            <p class="muted">
              هذه بيانات الحزمة المسجلة نفسها: الهوية، النطاق، Manifest والحالة. لا تمنح صلاحية
              Deployment.
            </p>
            <div class="record-list">
              <article v-for="pkg in packagesAsRecords" :key="pkg.id" class="trace-card">
                <div class="trace-heading">
                  <div>
                    <strong><bdi dir="ltr">{{ pkg.package_type }}</bdi></strong>
                    <small class="mono" dir="ltr">{{ pkg.id }}</small>
                  </div>
                  <span class="state-pill" dir="ltr">{{ pkg.status }}</span>
                </div>
                <dl class="trace-grid">
                  <div>
                    <dt>Owner / schema</dt>
                    <dd dir="ltr">{{ pkg.owner_module }} / v{{ pkg.schema_version ?? '—' }}</dd>
                  </div>
                  <div>
                    <dt>Captured at</dt>
                    <dd>{{ when(pkg.created_at) }}</dd>
                  </div>
                  <div class="wide">
                    <dt>Package digest</dt>
                    <dd class="mono break-all" dir="ltr">{{ pkg.package_digest }}</dd>
                  </div>
                </dl>
                <details class="context-disclosure">
                  <summary>Recorded target/scope وManifest</summary>
                  <h3>Scope</h3>
                  <pre dir="ltr">{{ jsonText(pkg.scope) }}</pre>
                  <h3>Manifest</h3>
                  <pre dir="ltr">{{ jsonText(pkg.manifest) }}</pre>
                </details>
              </article>
              <p v-if="packagesAsRecords.length === 0" class="empty">لا توجد حزم مسجلة.</p>
            </div>
          </section>
        </template>

        <template v-else-if="surface === 'configuration'">
          <section class="panel">
            <p class="section-kicker">Whitelisted local product configuration</p>
            <h2>القيم التشغيلية غير السرية</h2>
            <dl class="config-list">
              <div>
                <dt>Profile</dt>
                <dd><bdi dir="ltr">{{ state.profile }}</bdi></dd>
              </div>
              <div>
                <dt>Queue connection</dt>
                <dd><bdi dir="ltr">{{ state.queue_connection }}</bdi></dd>
              </div>
              <div>
                <dt>Blob disk</dt>
                <dd><bdi dir="ltr">{{ state.blob_disk }}</bdi></dd>
              </div>
              <div>
                <dt>Auth bypass</dt>
                <dd dir="ltr">{{ state.auth_bypass }}</dd>
              </div>
              <div>
                <dt>Force HTTPS</dt>
                <dd dir="ltr">{{ state.force_https }}</dd>
              </div>
              <div>
                <dt>Release loopback only</dt>
                <dd dir="ltr">{{ state.release_loopback_only }}</dd>
              </div>
              <div>
                <dt>AI network provider</dt>
                <dd dir="ltr">{{ state.ai_network_provider_enabled }}</dd>
              </div>
            </dl>
          </section>
          <section class="panel">
            <h2>حدود الأحجام</h2>
            <dl class="config-list">
              <div v-for="(value, key) in state.limits ?? {}" :key="key">
                <dt><bdi dir="ltr">{{ key }}</bdi></dt>
                <dd dir="ltr">{{ value }} bytes</dd>
              </div>
            </dl>
          </section>
        </template>
      </main>

      <aside class="workspace-right" aria-label="السياق الفريد">
        <template v-if="surface === 'health'">
          <p class="rail-label">التأثير</p>
          <h2>أثر الحالة التشغيلية</h2>
          <p v-if="state.release_gate?.ready">لا توجد بوابة Release بحالة FAIL وفق الفحص الحالي.</p>
          <p v-else>
            حالة Release validation الحالية تمنع اعتبار الحزمة جاهزة حتى معالجة فحوص FAIL.
          </p>
        </template>
        <template v-else-if="surface === 'processing'">
          <p class="rail-label">Traceability</p>
          <h2>Requested ≠ Executed</h2>
          <p>
            Input digest يعرّف الطلب، بينما الحالة والمحاولات والعامل والأوقات تصف ما حدث فعليًا.
          </p>
        </template>
        <template v-else-if="surface === 'validation'">
          <p class="rail-label">حد الملكية</p>
          <h2>تحقق تقني فقط</h2>
          <p>
            التحقق هنا يختبر البنية، النوع، provenance، hashes وحدود الحزمة. قرار جودة المعرفة يبقى
            خارج W05.
          </p>
        </template>
        <template v-else-if="surface === 'ai-bridge'">
          <p class="rail-label">سياسة التنفيذ</p>
          <h2><bdi dir="ltr">{{ state.policy?.execution }}</bdi></h2>
          <p>لا API provider، لا polling، لا embeddings، ولا نشر تلقائي. القرار النهائي إنساني.</p>
          <p v-if="state.policy?.automatic_provider_enabled" class="danger-text">
            الإعداد الحالي يشير إلى provider شبكي مفعّل ويحتاج مراجعة.
          </p>
        </template>
        <template v-else-if="surface === 'backups'">
          <p class="rail-label">سلامة الاستعادة</p>
          <h2><bdi dir="ltr">{{ state.safety?.web_restore_mode }}</bdi></h2>
          <p>الإجراء عبر الويب ينتهي عند staging + verification؛ التفعيل المدمر غير معروض.</p>
        </template>
        <template v-else-if="surface === 'audit'">
          <p class="rail-label">الحقيقة التاريخية</p>
          <h2>Append-only</h2>
          <p>
            Actor وTarget وCorrelation وSafe metadata والـhashes معروضة للتحقيق؛ لا توجد أوامر تعديل
            للسجل.
          </p>
        </template>
        <template v-else-if="surface === 'releases'">
          <p class="rail-label">حد التفويض</p>
          <h2>لا Deployment</h2>
          <p>المعروض هو package/release validation وfollow-up context فقط، دون صلاحية نشر إنتاجي.</p>
        </template>
        <template v-else-if="surface === 'configuration'">
          <p class="rail-label">نطاق التهيئة</p>
          <h2>تشغيل المنتج المحلي</h2>
          <p>تظهر فقط قيم تشغيلية مسموحة وغير سرية. لا مفاتيح API ولا إعدادات حساب استهلاكية.</p>
        </template>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.system-workspace {
  min-height: 100vh;
  background: #071017;
  color: #e8f0f5;
  font-family: 'Noto Sans Arabic', 'Segoe UI', sans-serif;
  padding: 24px;
}
.workspace-top {
  display: flex;
  justify-content: space-between;
  gap: 24px;
  align-items: flex-start;
  border: 1px solid #23313b;
  background: #0b161e;
  padding: 24px;
  border-radius: 18px;
  margin-bottom: 16px;
}
.eyebrow,
.section-kicker,
.rail-label {
  margin: 0 0 8px;
  color: #70d4d1;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}
.workspace-top h1 {
  margin: 0;
  font-size: 28px;
}
.subtitle {
  margin: 8px 0 0;
  color: #9fb0bd;
  max-width: 760px;
  line-height: 1.8;
}
.top-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}
.tool-link,
.read-only-chip,
button {
  border: 1px solid #2e6264;
  background: #123236;
  color: #dffafa;
  border-radius: 10px;
  padding: 9px 14px;
  font-weight: 800;
  text-decoration: none;
  cursor: pointer;
}
.read-only-chip {
  cursor: default;
  border-color: #33434f;
  background: #101c25;
  color: #9fb0bd;
}
.workspace-grid {
  display: grid;
  grid-template-columns: 200px minmax(0, 1fr) 260px;
  gap: 16px;
}
.workspace-left,
.workspace-right,
.workspace-center {
  border: 1px solid #22313c;
  background: #09141c;
  border-radius: 16px;
}
.workspace-left {
  padding: 16px;
  align-self: start;
  position: sticky;
  top: 16px;
}
.rail-link {
  display: block;
  color: #aebbc5;
  text-decoration: none;
  padding: 11px 12px;
  border-radius: 9px;
  margin: 3px 0;
}
.rail-link.active {
  background: #12343a;
  color: #e8ffff;
  border-inline-start: 3px solid #67d1cf;
}
.workspace-center {
  padding: 18px;
  min-width: 0;
}
.workspace-right {
  padding: 18px;
  align-self: start;
  position: sticky;
  top: 16px;
  color: #b5c1ca;
  line-height: 1.8;
}
.workspace-right h2 {
  color: #eef7fa;
  margin: 4px 0 12px;
}
.panel,
.hero-state,
.danger-zone {
  border: 1px solid #263741;
  background: #0d1922;
  border-radius: 14px;
  padding: 18px;
  margin-bottom: 14px;
}
.hero-state {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
}
.hero-state h2,
.panel h2 {
  margin: 0 0 14px;
  font-size: 20px;
}
.status-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}
.status-card {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  border: 1px solid #243945;
  background: #0a151d;
  border-radius: 11px;
  padding: 13px;
}
.status-card.wide {
  align-items: flex-start;
}
.status-card p {
  margin: 6px 0 0;
  color: #96a8b5;
  font-size: 13px;
  line-height: 1.6;
}
.state-pill {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
  border: 1px solid #3b505e;
  background: #111f28;
  border-radius: 999px;
  padding: 4px 9px;
  font-size: 11px;
  font-weight: 900;
  color: #cbd8df;
}
.state-pill.ok {
  border-color: #2e6e61;
  color: #9be3ce;
  background: #0d2924;
}
.state-pill.danger {
  border-color: #7d4347;
  color: #ffc0c1;
  background: #2a1518;
}
.metric-strip {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 10px;
}
.metric-strip.compact {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-bottom: 16px;
}
.metric-strip div,
.trace-grid div,
.config-list div {
  border: 1px solid #253842;
  background: #0a151d;
  border-radius: 10px;
  padding: 12px;
}
.metric-strip span {
  display: block;
  color: #8fa2af;
  font-size: 12px;
}
.metric-strip strong {
  display: block;
  font-size: 24px;
  margin-top: 5px;
}
.record-list {
  display: grid;
  gap: 9px;
}
.record-row,
.trace-card {
  border: 1px solid #243741;
  background: #0a151d;
  border-radius: 11px;
  padding: 13px;
}
.record-row {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  align-items: center;
}
.record-row small,
.trace-card small {
  display: block;
  color: #8195a2;
  margin-top: 5px;
}
.trace-heading {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
}
.trace-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
  margin: 12px 0 0;
}
.trace-grid .wide {
  grid-column: 1/-1;
}
.trace-grid dt {
  color: #8195a2;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
}
.trace-grid dd {
  margin: 6px 0 0;
  line-height: 1.6;
}
.context-disclosure,
.proposal-review {
  margin-top: 12px;
  border: 1px solid #31505c;
  border-radius: 10px;
  padding: 12px;
  background: #08131a;
}
.context-disclosure summary,
.proposal-review summary,
.danger-zone summary {
  cursor: pointer;
  font-weight: 900;
  color: #d8f2f2;
}
.context-disclosure h3,
.proposal-review h3 {
  margin: 14px 0 8px;
  font-size: 13px;
  color: #9fb8c3;
}
pre {
  overflow: auto;
  max-height: 420px;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  background: #050c11;
  border: 1px solid #20333d;
  border-radius: 9px;
  padding: 12px;
  color: #c9dce4;
  font: 12px/1.65 ui-monospace, SFMono-Regular, Menlo, monospace;
}
.proposal-payload {
  max-height: 640px;
  border-color: #35636a;
}
.review-section + .review-section {
  margin-top: 12px;
}
.decision-rationale {
  display: grid;
  gap: 8px;
  margin-top: 14px;
  font-weight: 800;
}
.decision-actions {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}
.row-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}
.row-actions a {
  color: #8de5e0;
}
.muted {
  color: #95a7b3;
  line-height: 1.8;
}
.diagnostic {
  color: #d9b896;
  border-inline-start: 2px solid #755b3c;
  padding-inline-start: 10px;
}
.inline-form,
.form-grid {
  display: flex;
  gap: 10px;
  align-items: end;
  margin-top: 14px;
}
.inline-form input[type='file'] {
  flex: 1;
}
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
}
.form-grid label {
  display: grid;
  gap: 6px;
  color: #aebec8;
}
.form-grid .full {
  grid-column: 1/-1;
}
.form-grid button {
  justify-self: start;
}
input,
textarea {
  width: 100%;
  box-sizing: border-box;
  background: #071119;
  border: 1px solid #334754;
  color: #e7f0f4;
  border-radius: 9px;
  padding: 10px;
  font: inherit;
}
textarea {
  min-height: 88px;
  resize: vertical;
}
.danger-button {
  border-color: #71434a;
  background: #2a161a;
  color: #ffc5c8;
}
.danger-zone {
  border-color: #654d2e;
  background: #211a0f;
}
.danger-body {
  margin-top: 14px;
}
.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}
.break-all {
  overflow-wrap: anywhere;
  word-break: break-all;
}
.config-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}
.config-list dt {
  color: #879aa7;
  font-size: 12px;
}
.config-list dd {
  margin: 6px 0 0;
  font-weight: 800;
}
.danger-text {
  color: #ffb6b8;
}
.empty {
  color: #788d99;
  text-align: center;
  padding: 22px;
}
@media (max-width: 1100px) {
  .workspace-grid {
    grid-template-columns: 180px minmax(0, 1fr);
  }
  .workspace-right {
    grid-column: 1/-1;
    position: static;
  }
  .metric-strip {
    grid-template-columns: repeat(3, 1fr);
  }
}
@media (max-width: 760px) {
  .system-workspace {
    padding: 12px;
  }
  .workspace-top {
    display: block;
  }
  .top-actions {
    margin-top: 16px;
  }
  .workspace-grid {
    grid-template-columns: 1fr;
  }
  .workspace-left {
    position: static;
  }
  .rail-link {
    display: inline-block;
  }
  .metric-strip,
  .metric-strip.compact,
  .status-grid,
  .config-list,
  .trace-grid {
    grid-template-columns: 1fr;
  }
  .trace-grid .wide {
    grid-column: auto;
  }
  .form-grid {
    grid-template-columns: 1fr;
  }
  .form-grid .full {
    grid-column: auto;
  }
}
@media (max-width: 480px) {
  .hero-state,
  .record-row,
  .trace-heading {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
