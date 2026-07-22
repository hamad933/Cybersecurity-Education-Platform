<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{ ownerExists: boolean }>();

const form = useForm({ email: '', password: '', remember: false });

function submit() {
  form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
  <Head title="تسجيل الدخول" />
  <main
    class="grid min-h-screen place-items-center overflow-x-hidden bg-[radial-gradient(circle_at_top,#164e63_0%,#020617_48%)] px-4 py-10"
    dir="rtl"
  >
    <section
      class="box-border w-[calc(100vw-2rem)] max-w-md min-w-0 rounded-2xl border border-slate-700 bg-slate-950/90 p-6 shadow-2xl sm:p-8"
      aria-labelledby="login-title"
    >
      <div class="mb-8">
        <span
          class="rounded-full border border-emerald-700 bg-emerald-950 px-3 py-1 text-xs text-emerald-200"
          >تشغيل محلي فقط</span
        >
        <h1 id="login-title" class="mt-5 text-3xl font-bold">دخول مالك المنصة</h1>
        <p class="mt-2 text-sm leading-6 text-slate-400">بوابة محلية محمية لإدارة أساس المنصة.</p>
      </div>

      <div
        v-if="!ownerExists"
        class="mb-6 rounded-xl border border-amber-700/70 bg-amber-950/40 p-4 text-sm leading-6 text-amber-100"
        role="status"
      >
        لا يوجد مالك بعد. أنشئ المالك مرة واحدة عبر الطرفية:
        <code
          class="direction-ltr mt-2 block rounded bg-slate-950 px-3 py-2 text-left text-cyan-200"
          >php artisan owner:create</code
        >
      </div>

      <form class="space-y-5" @submit.prevent="submit">
        <div>
          <label for="email" class="mb-2 block text-sm font-medium">البريد الإلكتروني</label>
          <input
            id="email"
            v-model="form.email"
            class="form-input focus-ring"
            dir="ltr"
            type="email"
            autocomplete="username"
            required
            autofocus
          />
          <p v-if="form.errors.email" class="mt-2 text-sm text-rose-300" role="alert">
            {{ form.errors.email }}
          </p>
        </div>
        <div>
          <label for="password" class="mb-2 block text-sm font-medium">كلمة المرور</label>
          <input
            id="password"
            v-model="form.password"
            class="form-input focus-ring"
            dir="ltr"
            type="password"
            autocomplete="current-password"
            required
          />
        </div>
        <label class="flex items-center gap-3 text-sm text-slate-300">
          <input v-model="form.remember" type="checkbox" class="focus-ring size-4 rounded" />
          تذكّر الجلسة على هذا الجهاز المحلي
        </label>
        <button
          class="focus-ring w-full rounded-xl bg-cyan-400 px-4 py-3 font-bold text-slate-950 transition hover:bg-cyan-300 disabled:opacity-50"
          :disabled="form.processing"
        >
          {{ form.processing ? 'جارٍ التحقق…' : 'دخول آمن' }}
        </button>
      </form>
    </section>
  </main>
</template>
