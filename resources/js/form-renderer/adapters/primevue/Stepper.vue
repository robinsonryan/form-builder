<template>
  <nav class="stepper" aria-label="Form steps" style="margin-bottom:1rem;">
    <ul style="display:flex; gap:0.5rem; list-style:none; padding:0; margin:0;">
      <li
        v-for="(s, idx) in steps"
        :key="s.id ?? idx"
        :class="{ active: idx === activeIndex }"
      >
        <button type="button" @click="$emit('navigate', idx)" :aria-current="idx === activeIndex ? 'step' : null">
          {{ idx + 1 }}. {{ s.title || s.id || 'Step ' + (idx + 1) }}
        </button>
      </li>
    </ul>
  </nav>
</template>

<script setup>
const props = defineProps({
  steps: { type: Array, default: () => [] },
  activeIndex: { type: Number, default: 0 },
});
</script>
