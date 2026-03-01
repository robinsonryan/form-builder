<template>
  <div class="core-stepper">
    <nav class="core-stepper-nav" aria-label="Form steps">
      <button
        v-for="(s, idx) in steps"
        :key="s.id ?? idx"
        :class="['core-stepper-button', { active: idx === activeIndex }]"
        @click="goTo(idx)"
        type="button"
      >
        {{ idx + 1 }}. {{ s.title ?? s.id ?? 'Step' }}
      </button>
    </nav>

    <div class="core-stepper-content">
      <slot :step="currentStep" :index="activeIndex">
        <!-- default rendering: show title and a message -->
        <h3 v-if="currentStep && currentStep.title">{{ currentStep.title }}</h3>
        <div v-else-if="currentStep">Step {{ activeIndex + 1 }}</div>
      </slot>
    </div>

    <div class="core-stepper-controls">
      <button type="button" @click="prev" :disabled="activeIndex <= 0">Previous</button>
      <button type="button" @click="next" :disabled="activeIndex >= steps.length - 1">Next</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  steps: { type: Array, default: () => [] },
  activeIndex: { type: Number, default: 0 },
});
const emit = defineEmits(['update:activeIndex', 'next', 'prev']);

const currentStep = computed(() => props.steps[props.activeIndex] ?? null);

function goTo(idx) {
  emit('update:activeIndex', idx);
}

function next() {
  const nextIdx = Math.min(props.activeIndex + 1, props.steps.length - 1);
  emit('update:activeIndex', nextIdx);
  emit('next', nextIdx);
}

function prev() {
  const prevIdx = Math.max(props.activeIndex - 1, 0);
  emit('update:activeIndex', prevIdx);
  emit('prev', prevIdx);
}
</script>

<style scoped>
.core-stepper-nav {
  display: flex;
  gap: 8px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}
.core-stepper-button {
  padding: 6px 10px;
  border: 1px solid #ddd;
  background: white;
  cursor: pointer;
}
.core-stepper-button.active {
  background: #f0f0f0;
  font-weight: 700;
}
.core-stepper-content {
  margin-bottom: 12px;
}
.core-stepper-controls {
  display: flex;
  justify-content: space-between;
}
</style>
