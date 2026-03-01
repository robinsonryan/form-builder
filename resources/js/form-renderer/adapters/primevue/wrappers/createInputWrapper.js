import { h, defineComponent, computed } from 'vue';

/**
 * createInputWrapper
 *
 * Wrap a PrimeVue input-like component so it conforms to the renderer-core widget contract:
 * props: { modelValue, schema, name, uiOptions, errors, id, class, className, ariaLabel }
 * emits: ['update:modelValue']
 *
 * The wrapper forwards modelValue <-> update:modelValue and spreads uiOptions as props/attrs.
 * It also exposes class/className props and sets ARIA attributes for accessibility:
 *  - id (falls back to name)
 *  - aria-invalid when there are errors
 *  - aria-describedby referencing a standard "<name>-error" id when errors exist
 *
 * This keeps the wrapper lightweight while enabling consistent accessibility and theming.
 */
export function createInputWrapper(PrimeComponent) {
  return defineComponent({
    name: 'PrimeInputWrapper',
    props: {
      modelValue: { required: false },
      schema: { type: Object, default: () => ({}) },
      name: { type: String, default: '' },
      uiOptions: { type: Object, default: () => ({}) },
      errors: { type: Array, default: () => [] },
      id: { type: String, required: false },
      class: { type: [String, Object, Array], required: false },
      className: { type: [String, Object, Array], required: false },
      ariaLabel: { type: String, required: false },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
      function onUpdate(v) {
        emit('update:modelValue', v);
      }

      const computedId = computed(() => props.id ?? props.name ?? undefined);
      const hasErrors = computed(() => Array.isArray(props.errors) && props.errors.length > 0);
      const describedBy = computed(() => (hasErrors.value && props.name ? `${props.name}-error` : undefined));
      const mergedClass = computed(() => {
        // Allow callers to provide className or class; prefer explicit uiOptions.class last so uiOptions can override
        const uiClass = props.uiOptions && props.uiOptions.class ? props.uiOptions.class : undefined;
        return [props.className || undefined, props.class || undefined, uiClass].filter(Boolean);
      });

      return () =>
        h(
          PrimeComponent,
          {
            modelValue: props.modelValue,
            // PrimeVue uses update:modelValue event; we provide handler that re-emits core contract
            'onUpdate:modelValue': onUpdate,
            // identity + accessibility
            id: computedId.value,
            class: mergedClass.value,
            'aria-label': props.ariaLabel,
            'aria-invalid': hasErrors.value ? 'true' : undefined,
            'aria-describedby': describedBy.value,
            // forward uiOptions as-is (may include attrs/props); uiOptions is spread last so callers can override id/class if desired
            ...props.uiOptions,
          },
          // pass through default slot content if any (keeps wrapper flexible)
          // Note: using null here means no slot content by default.
          null
        );
    },
  });
}

export default createInputWrapper;
