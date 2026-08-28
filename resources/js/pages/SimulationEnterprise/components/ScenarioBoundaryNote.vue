<template>
  <aside class="sim-boundary-note" data-testid="scenario-boundary-note">
    <div class="sim-boundary-note__mark" aria-hidden="true">SC</div>
    <div>
      <strong>السيناريو عقد قابل للنقل، وليس بنية تحتية ثابتة</strong>
      <p>
        يحدد السيناريو Environment Contract ومتطلبات التنفيذ. عند التهيئة فقط يُختار هدف داخلي
        متوافق وتُنشأ منه حالة المحاكاة الأولية للتشغيل؛ لا يحمل السيناريو Baseline دائمًا ولا يعتمد
        على مزوّد خارجي. Runtime Snapshot يبقى التقاطًا تاريخيًا داخل Run، وليس ناتج التهيئة.
      </p>
    </div>
  </aside>
</template>
