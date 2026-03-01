<template>
  <div class="core-checkbox">
    <label class="core-checkbox-label">
      <input
        type="checkbox"
        :name="name"
        :checked="Boolean(modelValue)"
        @change="$emit('update:modelValue', $event.target.checked)"
        v-bind="uiOptions?.attrs || {}"
      />
      <span v-if="schema && schema.title" class="core-checkbox-title">{{ schema.title }}</span>
    </label>
    <div v-if="errors && errors.length" class="core-errors" aria-live="polite">
      <div v-for="(e, i) in errors" :key="i" class="core-error">{{ e.message ?? e }}</div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  schema: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  errors: { type: Array, default: () => [] },
  uiOptions: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);
</script>

<style scoped>
.core-checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
}
.core-checkbox-title {
  font-weight: 600;
}
.core-errors {
  margin-top: 6px;
  color: #c53030;
}
.core-error {
  font-size: 12px;
}
</style>
