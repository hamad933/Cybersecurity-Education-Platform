<script setup lang="ts">
type StructureItem = {
  id: string;
  title: string;
  subtitle?: string;
  state?: string;
};

defineProps<{
  title: string;
  description: string;
  items: StructureItem[];
  selectedId: string | null;
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
  </div>
</template>
