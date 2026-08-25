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
  <nav class="space-y-4 text-xs select-none" aria-label="التسلسل البنيوي للمكتبة">
    <!-- Main Domains -->
    <section v-for="domain in projection.domains" :key="domain.id" class="space-y-2.5">
      <!-- Domain Header -->
      <header class="flex items-center justify-between gap-2 rounded-lg bg-slate-900/80 px-2.5 py-1.5 border border-slate-800/80">
        <div class="flex items-center gap-2 min-w-0">
          <span class="text-amber-400 select-none text-sm">📁</span>
          <span class="font-bold text-slate-200 truncate">{{ domain.title_ar }}</span>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
          <bdi dir="ltr" class="font-mono text-[10px] text-slate-500">{{ domain.id }}</bdi>
          <span class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-[10px] text-slate-400">
            {{ domain.clusters.reduce((acc, c) => acc + c.capabilities.reduce((a, cap) => a + cap.items.length, 0), 0) }}
          </span>
        </div>
      </header>

      <!-- Clusters -->
      <div class="space-y-2 ms-2 border-s-2 border-slate-800/80 ps-2.5">
        <section
          v-for="cluster in domain.clusters"
          :key="cluster.id"
          class="space-y-2"
        >
          <!-- Cluster Header -->
          <div class="flex items-center justify-between gap-2 py-1 px-1.5 rounded hover:bg-slate-900/40">
            <div class="flex items-center gap-1.5 min-w-0">
              <span class="text-amber-500/80 text-xs">📂</span>
              <h4 class="font-semibold text-slate-300 truncate text-[11px]">{{ cluster.title_ar }}</h4>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
              <bdi dir="ltr" class="font-mono text-[9px] text-slate-500">{{ cluster.id }}</bdi>
              <span class="rounded bg-slate-900 px-1 py-0.2 font-mono text-[9px] text-slate-500">
                {{ cluster.capabilities.reduce((a, cap) => a + cap.items.length, 0) }}
              </span>
            </div>
          </div>

          <!-- Capabilities -->
          <div class="space-y-1.5 ms-2 border-s border-slate-800/60 ps-2">
            <section v-for="capability in cluster.capabilities" :key="capability.id" class="space-y-1">
              <div class="flex items-center justify-between gap-2 px-1 py-0.5 text-[11px]">
                <div class="flex items-center gap-1 min-w-0 text-cyan-300/90 font-medium">
                  <span class="text-[10px] text-cyan-500">⚡</span>
                  <h5 class="truncate">{{ capability.title_ar }}</h5>
                </div>
                <bdi dir="ltr" class="font-mono text-[9px] text-cyan-600/80 shrink-0">{{ capability.id }}</bdi>
              </div>

              <!-- Knowledge Units List -->
              <ul class="space-y-1 ms-1">
                <li v-for="item in capability.items" :key="item.canonical_ref.id">
                  <Link
                    :href="itemHref(item)"
                    class="focus-ring group flex items-start gap-2 rounded-lg p-2 transition-all duration-150"
                    :class="
                      item.canonical_ref.id === activeId
                        ? 'border border-sky-500/50 bg-sky-950/50 text-sky-100 shadow-sm shadow-sky-950/60'
                        : 'border border-transparent text-slate-300 hover:border-slate-800 hover:bg-slate-900/60 hover:text-slate-100'
                    "
                  >
                    <span class="mt-0.5 text-xs select-none" :class="item.canonical_ref.id === activeId ? 'text-sky-400' : 'text-slate-500 group-hover:text-slate-400'">
                      🛡️
                    </span>
                    <div class="min-w-0 flex-1">
                      <span class="block font-semibold leading-tight">{{ item.title_ar }}</span>
                      <bdi dir="ltr" class="mt-1 block font-mono text-[10px]" :class="item.canonical_ref.id === activeId ? 'text-sky-400' : 'text-slate-500 group-hover:text-slate-400'">
                        {{ item.canonical_ref.id }}
                      </bdi>
                    </div>
                  </Link>
                </li>
              </ul>
            </section>
          </div>
        </section>
      </div>
    </section>

    <!-- Unresolved Capabilities -->
    <section v-if="projection.unresolved_capabilities.length" class="space-y-2 rounded-lg border border-amber-900/40 bg-amber-950/10 p-2.5">
      <div class="flex items-center gap-1.5 text-amber-300">
        <span>⚠️</span>
        <h3 class="font-bold text-[11px]">Capabilities بانتظار سياق Domain / Cluster</h3>
      </div>
      <section
        v-for="capability in projection.unresolved_capabilities"
        :key="capability.capability_id"
        class="space-y-1"
      >
        <bdi dir="ltr" class="font-mono text-[10px] text-amber-400 block px-1">
          {{ capability.capability_id }}
        </bdi>
        <ul class="space-y-1">
          <li v-for="item in capability.items" :key="item.canonical_ref.id">
            <Link
              :href="itemHref(item)"
              class="focus-ring flex items-start gap-2 rounded-lg p-2 text-xs transition"
              :class="
                item.canonical_ref.id === activeId
                  ? 'border border-amber-500/50 bg-amber-950/40 text-amber-100'
                  : 'text-slate-300 hover:bg-slate-900/60'
              "
            >
              <span>🛡️</span>
              <div>
                <span class="block font-medium">{{ item.title_ar }}</span>
                <bdi dir="ltr" class="block font-mono text-[10px] text-slate-500">
                  {{ item.canonical_ref.id }}
                </bdi>
              </div>
            </Link>
          </li>
        </ul>
      </section>
    </section>

    <!-- Unplaced Units -->
    <section v-if="projection.unplaced.length" class="space-y-2 rounded-lg border border-slate-800 bg-slate-950/40 p-2.5">
      <h3 class="font-bold text-[11px] text-amber-300">وحدات معرفة بلا موضع بنيوي</h3>
      <ul class="space-y-1">
        <li v-for="item in projection.unplaced" :key="item.canonical_ref.id">
          <Link
            :href="itemHref(item)"
            class="focus-ring flex items-start gap-2 rounded-lg p-2 text-xs transition"
            :class="
              item.canonical_ref.id === activeId
                ? 'border border-cyan-500/50 bg-cyan-950/40 text-cyan-100'
                : 'text-slate-300 hover:bg-slate-900/60'
            "
          >
            <span>🛡️</span>
            <div>
              <span class="block font-medium">{{ item.title_ar }}</span>
              <bdi dir="ltr" class="block font-mono text-[10px] text-slate-500">
                {{ item.canonical_ref.id }}
              </bdi>
            </div>
          </Link>
        </li>
      </ul>
    </section>
  </nav>
</template>

