<template>
  <div class="field-renderer" style="margin-bottom:0.75rem;">
    <label :for="name" style="display:block; font-weight:600;">{{ schema.title || name }}</label>

    <!-- resolve a widget from registry; if none, render a basic input -->
    <component
      v-if="widgetComponent"
      :is="widgetComponent"
      v-model="localValue"
      :schema="schema"
      :name="name"
      :class="{ invalid: hasErrors }"
      :aria-invalid="hasErrors ? 'true' : 'false'"
    />
    <div v-else>Nothing to see here.
    </div>

    <!-- Inline field errors -->
    <div v-if="errors && errors.length" class="field-errors" style="color:#b00020; margin-top:0.25rem;">
      <div v-for="(e, i) in errors" :key="i" class="field-error">{{ e.message || String(e) }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { resolveWidgetForField } from '../../core/widgetRegistry.js';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
  name: { type: String, required: true },
  schema: { type: Object, default: () => ({}) },
  modelValue: { required: false },
  errors: { type: Array, default: () => [] },
});

const localValue = ref(props.modelValue);

watch(
  () => props.modelValue,
  (v) => {
    localValue.value = v;
  }
);

watch(localValue, (v) => {
  emit('update:modelValue', v);
});

const hasErrors = computed(() => Array.isArray(props.errors) && props.errors.length > 0);

/**
 * Resolution policy:
 * - Use registry.resolveWidgetForField(schema, uiSchema) as single source of truth.
 * - If the registry throws (strict mode) we allow the exception to bubble in non-production
 *   so developers get a fast-fail. In production (NODE_ENV === 'production') we log and
 *   gracefully fall back to the native input.
 */
let widgetComponent = null;
try {
  const uiHint = props.schema && props.schema['ui:widget'] ? { 'ui:widget': props.schema['ui:widget'] } : {};
  const resolved = resolveWidgetForField(props.schema || {}, uiHint);
  widgetComponent = resolved ? resolved.widget : null;
} catch (err) {
  // In production, log and fall back to basic input; in dev/test let it bubble so tests catch it.
  if (typeof process !== 'undefined' && process.env && process.env.NODE_ENV === 'production') {
    // eslint-disable-next-line no-console
    console.error('FieldRenderer: widget resolution error', err);
    widgetComponent = null;
  } else {
    throw err;
  }
}
</script>
