<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { LibraryHierarchyProjection, LibraryProjectionItem } from './libraryHierarchy';

defineProps<{
  projection: LibraryHierarchyProjection;
  activeId?: string | null;
}>();

const itemHref = (item: LibraryProjectionItem) =>
  `/knowledge?object=${encodeURIComponent(item.canonical_ref.id)}`;
</script>

<template>
  <nav class="space-y-5" aria-label="التسلسل البنيوي للمكتبة">
    <section v-for="domain in projection.domains" :key="domain.id" class="space-y-3">
      <header class="space-y-1">
        <div class="flex items-center justify-between gap-2">
          <span class="text-[10px] font-bold tracking-wide text-slate-500">Domain</span>
          <bdi dir="ltr" class="font-mono text-[10px] text-slate-600">{{ domain.id }}</bdi>
        </div>
        <h3 class="text-sm font-bold text-slate-200">{{ domain.title_ar }}</h3>
      </header>

      <section
        v-for="cluster in domain.clusters"
        :key="cluster.id"
        class="border-s border-slate-800 ps-3"
      >
        <div class="flex items-start justify-between gap-2">
          <h4 class="text-xs font-bold text-slate-400">{{ cluster.title_ar }}</h4>
          <bdi dir="ltr" class="font-mono text-[10px] text-slate-600">{{ cluster.id }}</bdi>
        </div>

        <section v-for="capability in cluster.capabilities" :key="capability.id" class="mt-3">
          <div class="flex items-start justify-between gap-2">
            <h5 class="text-xs text-cyan-200">{{ capability.title_ar }}</h5>
            <bdi dir="ltr" class="font-mono text-[10px] text-cyan-700">{{ capability.id }}</bdi>
          </div>

          <ul class="mt-2 space-y-1">
            <li v-for="item in capability.items" :key="item.canonical_ref.id">
              <Link
                :href="itemHref(item)"
                class="focus-ring block rounded-lg px-2.5 py-2 text-sm"
                :class="
                  item.canonical_ref.id === activeId
                    ? 'bg-cyan-400/10 text-cyan-100'
                    : 'text-slate-300 hover:bg-slate-800'
                "
              >
                <span class="block">{{ item.title_ar }}</span>
                <bdi dir="ltr" class="mt-0.5 block font-mono text-[10px] text-slate-600">
                  {{ item.canonical_ref.id }}
                </bdi>
              </Link>
            </li>
          </ul>
        </section>
      </section>
    </section>

    <section v-if="projection.unresolved_capabilities.length" class="space-y-3">
      <h3 class="text-xs font-bold text-amber-300">Capabilities بانتظار سياق Domain / Cluster</h3>
      <section
        v-for="capability in projection.unresolved_capabilities"
        :key="capability.capability_id"
      >
        <bdi dir="ltr" class="font-mono text-xs text-amber-400">
          {{ capability.capability_id }}
        </bdi>
        <ul class="mt-2 space-y-1">
          <li v-for="item in capability.items" :key="item.canonical_ref.id">
            <Link
              :href="itemHref(item)"
              class="focus-ring block rounded-lg px-2.5 py-2 text-sm"
              :class="
                item.canonical_ref.id === activeId
                  ? 'bg-cyan-400/10 text-cyan-100'
                  : 'text-slate-300 hover:bg-slate-800'
              "
            >
              {{ item.title_ar }}
            </Link>
          </li>
        </ul>
      </section>
    </section>

    <section v-if="projection.unplaced.length">
      <h3 class="text-xs font-bold text-amber-300">وحدات معرفة بلا موضع بنيوي</h3>
      <ul class="mt-2 space-y-1">
        <li v-for="item in projection.unplaced" :key="item.canonical_ref.id">
          <Link
            :href="itemHref(item)"
            class="focus-ring block rounded-lg px-2.5 py-2 text-sm"
            :class="
              item.canonical_ref.id === activeId
                ? 'bg-cyan-400/10 text-cyan-100'
                : 'text-slate-300 hover:bg-slate-800'
            "
          >
            {{ item.title_ar }}
          </Link>
        </li>
      </ul>
    </section>
  </nav>
</template>
