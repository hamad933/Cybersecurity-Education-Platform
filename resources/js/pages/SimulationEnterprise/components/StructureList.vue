<script setup lang="ts">
type StructureItem = {
  id: string;
  title: string;
  subtitle?: string;
  state?: string;
};

type StructureGroup = {
  label: string;
  kind: string;
  items: Array<{ id: string; label: string; meta?: string }>;
};

defineProps<{
  title: string;
  description: string;
  items: StructureItem[];
  selectedId: string | null;
  groups: StructureGroup[];
}>();

const emit = defineEmits<{ select: [id: string] }>();
</script>

<template>
  <div class="sim-structure" data-testid="structure-list">
    <div class="sim-panel-heading">
      <p class="sim-kicker">LEFT · STRUCTURE</p>
      <h2>{{ title }}</h2>
      <p>{{ description }}</p>
    </div>

    <div v-if="items.length" class="sim-structure__items">
      <button
        v-for="item in items"
        :key="item.id"
        type="button"
        class="sim-structure-item"
        :class="{ 'sim-structure-item--selected': item.id === selectedId }"
        :aria-pressed="item.id === selectedId"
        @click="emit('select', item.id)"
      >
        <span>{{ item.title }}</span>
        <small v-if="item.subtitle" class="sim-technical">{{ item.subtitle }}</small>
        <b v-if="item.state" class="sim-structure-item__state">{{ item.state }}</b>
      </button>
    </div>
    <p v-else class="sim-muted">لا توجد سجلات منشورة في هذا النطاق.</p>

    <section
      v-for="group in groups"
      :key="group.kind"
      class="sim-structure-group"
      :data-structure-kind="group.kind"
    >
      <header>
        <span>{{ group.label }}</span>
        <b>{{ group.items.length }}</b>
      </header>
      <ol v-if="group.items.length">
        <li v-for="(item, index) in group.items" :key="item.id">
          <span class="sim-structure-rail" aria-hidden="true">{{ index + 1 }}</span>
          <span>{{ item.label }}</span>
          <small v-if="item.meta" class="sim-technical">{{ item.meta }}</small>
        </li>
      </ol>
      <p v-else class="sim-muted">لا توجد عناصر ضمن هذا الفرع.</p>
    </section>
  </div>
</template>
