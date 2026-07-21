<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

import type { SharedProps } from '../types';

const page = usePage<SharedProps>();
</script>

<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,#123247_0%,#020617_48%)]">
    <a class="skip-link" href="#main-content">تجاوز إلى المحتوى</a>
    <header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
        <div>
          <p class="text-xs font-semibold tracking-[0.2em] text-cyan-300">CYBERSECURITY LEARNING</p>
          <p class="mt-1 text-lg font-bold">منصة التعلّم ومحاكاة العمليات</p>
        </div>
        <div class="flex items-center gap-3 text-sm">
          <span
            class="rounded-full border border-emerald-700 bg-emerald-950 px-3 py-1 text-emerald-200"
            >محلي فقط</span
          >
          <span
            class="hidden rounded-full border border-slate-700 px-3 py-1 text-slate-300 sm:inline"
            >{{ page.props.environment.name }}</span
          >
          <Link
            v-if="page.props.auth.owner"
            href="/logout"
            method="post"
            as="button"
            class="focus-ring rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800"
          >
            خروج {{ page.props.auth.owner.display_name }}
          </Link>
        </div>
      </div>
    </header>
    <main id="main-content" class="mx-auto max-w-7xl px-4 py-8 sm:px-6" tabindex="-1">
      <slot />
    </main>
  </div>
</template>
