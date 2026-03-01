<template>
  <div class="core-textarea">
    <label v-if="schema && schema.title" class="core-label">{{ schema.title }}</label>
    <textarea
      :name="name"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="core-textarea-input"
      v-bind="uiOptions?.attrs || {}"
    ></textarea>
    <div v-if="errors && errors.length" class="core-errors" aria-live="polite">
      <div v-for="(e, i) in errors" :key="i" class="core-error">{{ e.message ?? e }}</div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: [String, Number], default: '' },
  schema: { type: Object, default: () => ({}) },
  name: { type: String, default: '' },
  errors: { type: Array, default: () => [] },
  uiOptions: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);
</script>

<style scoped>
.core-label {
  display: block;
  font-weight: 600;
  margin-bottom: 4px;
}
.core-textarea-input {
  width: 100%;
  min-height: 100px;
  padding: 6px 8px;
  box-sizing: border-box;
}
.core-errors {
  margin-top: 6px;
  color: #c53030;
}
.core-error {
  font-size: 12px;
}
</style>
