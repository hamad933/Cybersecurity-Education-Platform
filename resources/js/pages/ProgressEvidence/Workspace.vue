<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Candidate = { id:string; capability_id:string; proposed_title:string; proposed_summary:string; state:string; source_type:string; source_id:string; source_revision:string|null; source_digest:string };
type Evidence = { id:string; capability_id:string; lifecycle_state:string; review_status:string; effective_review_decision:string; current_revision_number:number; title:string; summary:string; source_type:string; source_id:string; source_revision:string|null; source_digest:string; content_digest:string; facts:Record<string, unknown> };
type ReviewRequest = { id:string; evidence_id:string; status:string };
type Finding = { id:string; criterion_key:string; finding:string; statement:string };
type Review = { id:string; evidence_id:string; review_request_id:string; reviewer_id:string; status:string; findings:Finding[]; decision:{decision:string;rationale:string}|null };
type Mastery = { id:string; target_id:string; judgment:string; freshness_status:string; policy_revision_id:string; supporting_evidence_ids:string[]; contradicting_evidence_ids:string[]; rationale:string; content_digest:string };
type PortfolioItem = { id:string; evidence_id:string; title:string; annotation:string|null; lifecycle_state:string; effective_review_decision:string };
type Portfolio = { id:string; name:string; view_scope:string|null; grouping:string; filters:Record<string,unknown>; annotations:Record<string,unknown>; items:PortfolioItem[] };
type Surface = 'evidence'|'reviews'|'mastery'|'portfolio';
type Panel = 'intake'|'revision'|'finding'|'decision'|'mastery'|'portfolio'|'portfolio-add'|null;

const props = defineProps<{ surface:Surface; summary:Record<string,number>; candidates:Candidate[]; evidence:Evidence[]; review_requests:ReviewRequest[]; reviews:Review[]; mastery:Mastery[]; portfolios:Portfolio[] }>();
const page = usePage<{ flash?:{status?:string}; errors?:Record<string,string> }>();
const panel = ref<Panel>(null);
const candidateId = ref(props.candidates.find(v=>v.state==='CANDIDATE')?.id ?? props.candidates[0]?.id ?? '');
const evidenceId = ref(props.evidence[0]?.id ?? '');
const requestId = ref(props.review_requests.find(v=>v.status==='REQUESTED')?.id ?? props.review_requests[0]?.id ?? '');
const reviewId = ref(props.reviews.find(v=>v.status==='IN_REVIEW')?.id ?? props.reviews[0]?.id ?? '');
const masteryId = ref(props.mastery[0]?.id ?? '');
const portfolioId = ref(props.portfolios[0]?.id ?? '');
const portfolioItemId = ref(props.portfolios[0]?.items[0]?.id ?? '');
const candidate = computed(()=>props.candidates.find(v=>v.id===candidateId.value));
const selectedEvidence = computed(()=>props.evidence.find(v=>v.id===evidenceId.value));
const request = computed(()=>props.review_requests.find(v=>v.id===requestId.value));
const review = computed(()=>props.reviews.find(v=>v.id===reviewId.value));
const selectedMastery = computed(()=>props.mastery.find(v=>v.id===masteryId.value));
const selectedPortfolio = computed(()=>props.portfolios.find(v=>v.id===portfolioId.value));
const portfolioItem = computed(()=>selectedPortfolio.value?.items.find(v=>v.id===portfolioItemId.value) ?? selectedPortfolio.value?.items[0]);

const intake = useForm({source_type:'SOURCE_HANDOFF',source_id:'',source_revision:'',source_digest:'',capability_id:'',title:'',summary:'',facts:{} as Record<string,unknown>,metadata:{} as Record<string,unknown>});
const revision = useForm({title:'',summary:'',facts:{} as Record<string,unknown>});
const finding = useForm({criterion_key:'',finding:'SATISFIED',statement:''});
const decision = useForm({decision:'ACCEPT',rationale:''});
const masteryForm = useForm({capability_id:'',policy_revision_id:'',judgment:'NOT_EVALUATED',freshness_status:'CURRENT',supporting_evidence_ids:[] as string[],contradicting_evidence_ids:[] as string[],rationale:''});
const portfolioForm = useForm({name:'',view_scope:'',grouping:'CAPABILITY',filters:{} as Record<string,unknown>,annotations:{} as Record<string,unknown>});
const portfolioAdd = useForm({evidence_id:'',annotation:'',sort_order:0});

const nav = [
  {key:'evidence',href:'/progress',ar:'الأدلة',en:'Evidence'},
  {key:'reviews',href:'/progress/reviews',ar:'المراجعات',en:'Reviews'},
  {key:'mastery',href:'/progress/mastery',ar:'الإتقان',en:'Mastery'},
  {key:'portfolio',href:'/progress/portfolio',ar:'الملف المهني',en:'Portfolio'},
] as const;
const digest = (value:string)=>value ? `${value.slice(0,12)}…${value.slice(-8)}` : '—';
const openRevision = ()=>{ if(!selectedEvidence.value)return; revision.title=selectedEvidence.value.title; revision.summary=selectedEvidence.value.summary; revision.facts=selectedEvidence.value.facts; panel.value='revision'; };
const openMastery = ()=>{ const evidence=selectedEvidence.value ?? props.evidence[0]; masteryForm.capability_id=evidence?.capability_id ?? selectedMastery.value?.target_id ?? ''; masteryForm.supporting_evidence_ids=evidence && ['ACCEPT','ACCEPT_WITH_LIMITATIONS'].includes(evidence.effective_review_decision) ? [evidence.id] : []; panel.value='mastery'; };
const openPortfolioAdd = ()=>{ portfolioAdd.evidence_id=selectedEvidence.value?.id ?? props.evidence[0]?.id ?? ''; panel.value='portfolio-add'; };
</script>

<template>
  <Head title="التقدم والأدلة" />
  <main class="workspace" dir="rtl">
    <header class="top" aria-label="أدوات سير العمل">
      <span>إجراءات سطح العمل الحالي</span>
      <div class="actions">
        <template v-if="surface==='evidence'">
          <button @click="panel='intake'">إدخال مصدر</button>
          <button :disabled="candidate?.state!=='CANDIDATE'" @click="candidate && router.post(`/progress/candidates/${candidate.id}/admit`)">اعتماد المرشح</button>
          <button :disabled="!selectedEvidence" @click="openRevision">Revision جديدة</button>
          <button :disabled="!selectedEvidence || selectedEvidence.lifecycle_state!=='ACTIVE'" @click="selectedEvidence && router.post(`/progress/evidence/${selectedEvidence.id}/review-requests`)">طلب مراجعة</button>
        </template>
        <template v-else-if="surface==='reviews'">
          <button :disabled="request?.status!=='REQUESTED'" @click="request && router.post(`/progress/review-requests/${request.id}/admit`)">قبول Review Request</button>
          <button :disabled="review?.status!=='IN_REVIEW'" @click="panel='finding'">إضافة Finding</button>
          <button :disabled="review?.status!=='IN_REVIEW' || !review.findings.length" @click="panel='decision'">تسجيل القرار</button>
        </template>
        <button v-else-if="surface==='mastery'" @click="openMastery">تقييم Capability</button>
        <template v-else>
          <button @click="panel='portfolio'">إنشاء Portfolio View</button>
          <button :disabled="!selectedPortfolio || !evidence.length" @click="openPortfolioAdd">إضافة Evidence</button>
          <button :disabled="!portfolioItem" @click="selectedPortfolio && portfolioItem && router.delete(`/progress/portfolio/${selectedPortfolio.id}/evidence/${portfolioItem.evidence_id}`)">إزالة من العرض</button>
        </template>
      </div>
    </header>

    <p v-if="page.props.flash?.status" class="flash ok">{{ page.props.flash.status }}</p>
    <p v-if="page.props.errors?.workflow" class="flash bad">{{ page.props.errors.workflow }}</p>

    <div class="layout">
      <nav class="left" aria-label="بنية التقدم والأدلة">
        <a v-for="item in nav" :key="item.key" :href="item.href" :class="{active:surface===item.key}"><span>{{ item.ar }}</span><bdi>{{ item.en }}</bdi></a>
      </nav>

      <section class="center">
        <template v-if="surface==='evidence'">
          <header class="title"><div><small>Progress &amp; Evidence · Evidence</small><h1>الأدلة المحكومة</h1></div><p>Candidate Evidence لا تصبح Evidence إلا بعد Admission صريح.</p></header>
          <h2>Candidate Evidence</h2>
          <button v-for="item in candidates" :key="item.id" class="row" :class="{selected:item.id===candidateId}" @click="candidateId=item.id">
            <span><strong>{{ item.proposed_title }}</strong><small>{{ item.proposed_summary }}</small></span><span><bdi>{{ item.capability_id }}</bdi><em>{{ item.state }}</em></span>
          </button>
          <p v-if="!candidates.length" class="empty">لا توجد Candidate Evidence.</p>

          <h2 class="separator">Canonical Evidence</h2>
          <button v-for="item in evidence" :key="item.id" class="row" :class="{selected:item.id===evidenceId}" @click="evidenceId=item.id">
            <span><strong>{{ item.title }}</strong><small>{{ item.summary }}</small><small>Revision {{ item.current_revision_number }} · SEALED</small></span>
            <span class="dimensions"><label>Lifecycle<em>{{ item.lifecycle_state }}</em></label><label>Review<em>{{ item.review_status }}</em></label><label>Decision<em>{{ item.effective_review_decision }}</em></label></span>
          </button>
          <p v-if="!evidence.length" class="empty">لا توجد Evidence محكومة بعد.</p>
        </template>

        <template v-else-if="surface==='reviews'">
          <header class="title"><div><small>Formal Evidence Review</small><h1>المراجعات</h1></div><p>Evidence facts وFindings وReview Decision سجلات منفصلة.</p></header>
          <h2>Review Requests</h2>
          <button v-for="item in review_requests" :key="item.id" class="row" :class="{selected:item.id===requestId}" @click="requestId=item.id"><strong>{{ evidence.find(e=>e.id===item.evidence_id)?.title ?? 'Evidence' }}</strong><em>{{ item.status }}</em></button>
          <h2 class="separator">Evidence Reviews</h2>
          <button v-for="item in reviews" :key="item.id" class="row" :class="{selected:item.id===reviewId}" @click="reviewId=item.id">
            <span><strong>{{ evidence.find(e=>e.id===item.evidence_id)?.title ?? 'Evidence Review' }}</strong><small>{{ item.findings.length }} Finding</small></span><span><em>{{ item.status }}</em><em v-if="item.decision">{{ item.decision.decision }}</em></span>
            <ul v-if="item.findings.length"><li v-for="entry in item.findings" :key="entry.id"><bdi>{{ entry.criterion_key }}</bdi><span>{{ entry.statement }}</span><em>{{ entry.finding }}</em></li></ul>
          </button>
          <p v-if="!review_requests.length && !reviews.length" class="empty">لا توجد مراجعات رسمية بعد.</p>
        </template>

        <template v-else-if="surface==='mastery'">
          <header class="title"><div><small>Capability Mastery</small><h1>الإتقان المحكوم</h1></div><p>Mastery Judgment وFreshness Status بُعدان مستقلان؛ نسبة الإنجاز ليست Mastery.</p></header>
          <button v-for="item in mastery" :key="item.id" class="row" :class="{selected:item.id===masteryId}" @click="masteryId=item.id">
            <span><strong><bdi>{{ item.target_id }}</bdi></strong><small>{{ item.rationale }}</small></span><span class="dimensions"><label>Judgment<em>{{ item.judgment }}</em></label><label>Freshness<em>{{ item.freshness_status }}</em></label></span>
          </button>
          <p v-if="!mastery.length" class="empty">لم تُسجّل Mastery Evaluation بعد.</p>
        </template>

        <template v-else>
          <header class="title"><div><small>Curated Projection</small><h1>Portfolio</h1></div><p>Portfolio عرض محفوظ يشير إلى Evidence الحاكم ولا يكرر مستودعه.</p></header>
          <article v-for="item in portfolios" :key="item.id" class="portfolio" :class="{selected:item.id===portfolioId}" @click="portfolioId=item.id">
            <header><h2>{{ item.name }}</h2><span>{{ item.items.length }} عنصر</span></header>
            <button v-for="entry in item.items" :key="entry.id" class="portfolio-item" :class="{selected:entry.id===portfolioItemId}" @click.stop="portfolioId=item.id;portfolioItemId=entry.id"><span><strong>{{ entry.title }}</strong><small>{{ entry.annotation || 'بدون ملاحظة' }}</small></span><span><em>{{ entry.lifecycle_state }}</em><em>{{ entry.effective_review_decision }}</em></span></button>
          </article>
          <p v-if="!portfolios.length" class="empty">لا توجد Portfolio Views محفوظة.</p>
        </template>
      </section>

      <aside class="right" aria-label="السياق الفريد">
        <template v-if="surface==='evidence' && selectedEvidence">
          <small>Pinned Source Context</small><h2>مرجع المصدر</h2>
          <dl><div><dt>Source Type</dt><dd><bdi>{{ selectedEvidence.source_type }}</bdi></dd></div><div><dt>Source ID</dt><dd><bdi>{{ selectedEvidence.source_id }}</bdi></dd></div><div><dt>Source Revision</dt><dd><bdi>{{ selectedEvidence.source_revision || '—' }}</bdi></dd></div><div><dt>Source SHA-256</dt><dd><bdi :title="selectedEvidence.source_digest">{{ digest(selectedEvidence.source_digest) }}</bdi></dd></div><div><dt>Content SHA-256</dt><dd><bdi :title="selectedEvidence.content_digest">{{ digest(selectedEvidence.content_digest) }}</bdi></dd></div></dl>
          <pre dir="ltr">{{ JSON.stringify(selectedEvidence.facts,null,2) }}</pre>
        </template>
        <template v-else-if="surface==='evidence' && candidate">
          <small>Candidate Source Context</small><h2>مرجع المصدر المثبت</h2><dl><div><dt>Source Type</dt><dd><bdi>{{ candidate.source_type }}</bdi></dd></div><div><dt>Source ID</dt><dd><bdi>{{ candidate.source_id }}</bdi></dd></div><div><dt>SHA-256</dt><dd><bdi>{{ digest(candidate.source_digest) }}</bdi></dd></div></dl>
        </template>
        <template v-else-if="surface==='reviews' && review">
          <small>Reviewer Authority</small><h2>سياق المراجعة</h2><dl><div><dt>Reviewer</dt><dd><bdi>{{ review.reviewer_id }}</bdi></dd></div><div><dt>Review Request</dt><dd><bdi>{{ review.review_request_id }}</bdi></dd></div></dl><p v-if="review.decision">{{ review.decision.rationale }}</p>
        </template>
        <template v-else-if="surface==='mastery' && selectedMastery">
          <small>Evaluation Basis</small><h2>أساس التقييم</h2><dl><div><dt>Policy Revision</dt><dd><bdi>{{ selectedMastery.policy_revision_id }}</bdi></dd></div><div><dt>Supporting Evidence</dt><dd>{{ selectedMastery.supporting_evidence_ids.length }}</dd></div><div><dt>Contradicting Evidence</dt><dd>{{ selectedMastery.contradicting_evidence_ids.length }}</dd></div><div><dt>Digest</dt><dd><bdi>{{ digest(selectedMastery.content_digest) }}</bdi></dd></div></dl>
        </template>
        <template v-else-if="surface==='portfolio' && selectedPortfolio">
          <small>View Configuration</small><h2>تكوين العرض</h2><dl><div><dt>View Scope</dt><dd>{{ selectedPortfolio.view_scope || 'غير مقيّد' }}</dd></div><div><dt>Grouping</dt><dd><bdi>{{ selectedPortfolio.grouping }}</bdi></dd></div><div><dt>Active Filters</dt><dd>{{ Object.keys(selectedPortfolio.filters).length }}</dd></div><div><dt>Curation Metadata</dt><dd>{{ Object.keys(selectedPortfolio.annotations).length }}</dd></div></dl>
        </template>
      </aside>
    </div>

    <section v-if="panel" class="bottom" aria-label="مساحة عمل مؤقتة">
      <header><div><small>Temporary Deep Workspace</small><h2>إجراء محكوم</h2></div><button @click="panel=null">إغلاق</button></header>
      <form v-if="panel==='intake'" @submit.prevent="intake.post('/progress/intake',{onSuccess:()=>panel=null})"><label>نوع المصدر<input v-model="intake.source_type" dir="ltr" required></label><label>Source ID<input v-model="intake.source_id" dir="ltr" required></label><label>Source Revision<input v-model="intake.source_revision" dir="ltr"></label><label>Source SHA-256<input v-model="intake.source_digest" dir="ltr" minlength="64" maxlength="64" required></label><label>Capability ID<input v-model="intake.capability_id" dir="ltr" required></label><label>العنوان<input v-model="intake.title" required></label><label class="wide">الملخص<textarea v-model="intake.summary" required></textarea></label><p class="wide">يحفظ الإدخال pointer + digest للمصدر؛ لا ينسخ سجل المصدر.</p><button>إنشاء Candidate Evidence</button></form>
      <form v-else-if="panel==='revision' && selectedEvidence" @submit.prevent="revision.post(`/progress/evidence/${selectedEvidence.id}/revisions`,{onSuccess:()=>panel=null})"><label>العنوان<input v-model="revision.title" required></label><label class="wide">الملخص<textarea v-model="revision.summary" required></textarea></label><p class="wide">Revision الجديدة تُختم، والتاريخ السابق لا يُعدّل.</p><button>Seal Revision</button></form>
      <form v-else-if="panel==='finding' && review" @submit.prevent="finding.post(`/progress/reviews/${review.id}/findings`,{onSuccess:()=>panel=null})"><label>Criterion Key<input v-model="finding.criterion_key" dir="ltr" required></label><label>Finding<select v-model="finding.finding"><option>SATISFIED</option><option>PARTIALLY_SATISFIED</option><option>NOT_SATISFIED</option><option>NOT_ASSESSABLE</option></select></label><label class="wide">البيان<textarea v-model="finding.statement" required></textarea></label><button>تسجيل Finding</button></form>
      <form v-else-if="panel==='decision' && review" @submit.prevent="decision.post(`/progress/reviews/${review.id}/decision`,{onSuccess:()=>panel=null})"><label>Review Decision<select v-model="decision.decision"><option>ACCEPT</option><option>ACCEPT_WITH_LIMITATIONS</option><option>MORE_EVIDENCE_REQUIRED</option><option>REJECT</option></select></label><label class="wide">المسوّغ<textarea v-model="decision.rationale" required></textarea></label><button>تسجيل القرار</button></form>
      <form v-else-if="panel==='mastery'" @submit.prevent="masteryForm.post('/progress/mastery/evaluate',{onSuccess:()=>panel=null})"><label>Capability ID<input v-model="masteryForm.capability_id" dir="ltr" required></label><label>Policy Revision<input v-model="masteryForm.policy_revision_id" dir="ltr" required></label><label>Judgment<select v-model="masteryForm.judgment"><option>NOT_EVALUATED</option><option>INSUFFICIENT_EVIDENCE</option><option>INCONCLUSIVE</option><option>NOT_MASTERED</option><option>MASTERED</option></select></label><label>Freshness<select v-model="masteryForm.freshness_status"><option>CURRENT</option><option>REVALIDATION_REQUIRED</option></select></label><label class="wide">المسوّغ<textarea v-model="masteryForm.rationale" required></textarea></label><p class="wide"><bdi>MASTERED + REVALIDATION_REQUIRED</bdi> حالة صالحة.</p><button>حفظ Mastery Evaluation</button></form>
      <form v-else-if="panel==='portfolio'" @submit.prevent="portfolioForm.post('/progress/portfolio',{onSuccess:()=>panel=null})"><label>اسم العرض<input v-model="portfolioForm.name" required></label><label>View Scope<input v-model="portfolioForm.view_scope"></label><label>Grouping<input v-model="portfolioForm.grouping" dir="ltr" required></label><p class="wide">Portfolio projection محفوظ، وليس Evidence repository ثانيًا.</p><button>إنشاء العرض</button></form>
      <form v-else-if="panel==='portfolio-add' && selectedPortfolio" @submit.prevent="portfolioAdd.post(`/progress/portfolio/${selectedPortfolio.id}/evidence`,{onSuccess:()=>panel=null})"><label class="wide">Evidence<select v-model="portfolioAdd.evidence_id"><option v-for="item in evidence" :key="item.id" :value="item.id">{{ item.title }}</option></select></label><label class="wide">ملاحظة<textarea v-model="portfolioAdd.annotation"></textarea></label><button>إضافة المرجع</button></form>
    </section>
  </main>
</template>

<style scoped>
:global(body){margin:0;background:#090d14;color:#e8eef7;font-family:Inter,"Noto Sans Arabic",system-ui,sans-serif}:global(*){box-sizing:border-box}.workspace{min-height:100vh;padding:18px;background:#090d14}.top{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:14px 16px;border:1px solid #293448;border-radius:14px;background:#0f1621}.top>span{color:#93a2b6;font-size:12px}.actions{display:flex;flex-wrap:wrap;gap:7px}.actions button,.bottom header button{border:1px solid #3a4960;border-radius:9px;background:#172131;color:#eef4fb;padding:8px 11px;cursor:pointer}button:disabled{opacity:.4;cursor:not-allowed}button:focus-visible,a:focus-visible,input:focus-visible,textarea:focus-visible,select:focus-visible{outline:3px solid #70c8d4;outline-offset:2px}.flash{padding:10px 13px;margin:10px 0 0;border:1px solid;border-radius:9px}.ok{border-color:#38675f}.bad{border-color:#814653}.layout{display:grid;grid-template-columns:180px minmax(0,1fr) 300px;gap:12px;margin-top:12px;align-items:start}.left,.center,.right{border:1px solid #273143;border-radius:14px;background:#0d131d}.left{padding:9px;position:sticky;top:12px}.left a{display:flex;justify-content:space-between;gap:8px;padding:11px;border-radius:9px;text-decoration:none;color:#a4b2c5}.left a.active{background:#172333;color:#fff;box-shadow:inset -3px 0 #70c8d4}.left bdi,.row bdi,.right bdi,.bottom bdi{direction:ltr;unicode-bidi:isolate;font-family:ui-monospace,monospace;font-size:10px}.center{padding:18px;min-width:0}.title{display:flex;justify-content:space-between;gap:22px;align-items:end;margin-bottom:18px}.title small,.right>small,.bottom header small{color:#70c8d4;font-family:ui-monospace,monospace}.title h1{margin:5px 0 0;font-size:25px}.title p{margin:0;max-width:420px;color:#8e9db1;line-height:1.7}.center h2{font-size:14px;margin:14px 0 7px}.separator{padding-top:18px;border-top:1px solid #202a38}.row{width:100%;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;text-align:right;margin:7px 0;padding:13px;border:1px solid #29364a;border-radius:11px;background:#111925;color:inherit;cursor:pointer}.row.selected,.portfolio.selected,.portfolio-item.selected{border-color:#63b6c2}.row strong,.row small{display:block}.row small{color:#8494a9;margin-top:4px}.row>span:last-child{display:flex;gap:6px;align-items:center}.row em,.portfolio-item em{font-style:normal;font-family:ui-monospace,monospace;font-size:9px;direction:ltr;unicode-bidi:isolate;border:1px solid #41536d;border-radius:999px;padding:3px 6px;color:#d2e7eb}.dimensions label{display:grid;gap:4px;color:#738399;font-size:9px}.row ul{grid-column:1/-1;list-style:none;padding:8px 0 0;margin:0;border-top:1px solid #202a38}.row li{display:grid;grid-template-columns:130px 1fr auto;gap:8px;padding:4px;color:#aeb9c8;font-size:11px}.empty{padding:16px;border:1px dashed #36445a;border-radius:10px;color:#7f8fa4;text-align:center}.portfolio{padding:12px;margin:9px 0;border:1px solid #29364a;border-radius:11px}.portfolio>header{display:flex;justify-content:space-between;align-items:center}.portfolio h2{margin:0}.portfolio header span{color:#7f8fa3;font-size:11px}.portfolio-item{width:100%;display:grid;grid-template-columns:1fr auto;gap:10px;margin-top:7px;padding:10px;border:1px solid #253145;border-radius:9px;background:#101722;color:inherit;text-align:right}.portfolio-item strong,.portfolio-item small{display:block}.portfolio-item small{color:#7f8fa3;margin-top:3px}.right{padding:15px;position:sticky;top:12px}.right h2{font-size:18px;margin:5px 0 10px}.right dl{margin:0}.right dl div{padding:7px 0;border-bottom:1px solid #202a38}.right dt{color:#738399;font-size:9px;text-transform:uppercase}.right dd{margin:4px 0 0;word-break:break-all}.right pre{max-height:220px;overflow:auto;background:#080c12;padding:9px;border-radius:8px;font-size:9px;color:#a9c4ce;text-align:left}.right p{color:#95a4b8;line-height:1.7}.bottom{margin-top:12px;padding:16px;border:1px solid #3a485d;border-radius:14px;background:#101823}.bottom>header{display:flex;justify-content:space-between}.bottom h2{margin:4px 0 0}.bottom form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;max-width:920px;margin-top:12px}.bottom label{display:grid;gap:5px;color:#9aa9bd;font-size:11px}.bottom .wide{grid-column:1/-1}.bottom input,.bottom textarea,.bottom select{width:100%;border:1px solid #344359;border-radius:8px;background:#0a1019;color:#edf3fb;padding:9px}.bottom textarea{min-height:80px}.bottom form>button{justify-self:start;border:1px solid #6ac0cc;border-radius:8px;background:#a5e2ea;color:#071117;padding:8px 13px;font-weight:800}.bottom p{margin:0;color:#8292a7;font-size:11px}@media(max-width:1050px){.layout{grid-template-columns:170px 1fr}.right{grid-column:2;position:static}.top{align-items:stretch;flex-direction:column}}@media(max-width:700px){.layout{grid-template-columns:1fr}.left,.right{grid-column:1;position:static}.left{display:grid;grid-template-columns:1fr 1fr}.title{align-items:start;flex-direction:column}.row,.portfolio-item{grid-template-columns:1fr}.row li{grid-template-columns:1fr}.bottom form{grid-template-columns:1fr}.bottom .wide{grid-column:1}}
</style>
