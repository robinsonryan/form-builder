import { registerWidget, registerTypeMapping } from '../../core/widgetRegistry.js';
import { createInputWrapper } from './wrappers/createInputWrapper.js';

/**
 * registerPrimeVueWidgets(components = {}, options = {})
 *
 * components: an object with PrimeVue components to register, e.g.:
 *   { InputText, Textarea, Checkbox, Stepper }
 *
 * options:
 *   - string: override the type mapping target for 'string' (defaults to 'prime:InputText' when supplied)
 *
 * Usage:
 *   import { InputText, Textarea, Checkbox, Steps } from 'primevue';
 *   import { registerPrimeVueWidgets } from '.../registerPrimeVueWidgets.js';
 *
 *   registerPrimeVueWidgets({
 *     InputText,
 *     Textarea,
 *     Checkbox,
 *     Stepper: Steps
 *   });
 *
 * Notes:
 * - This adapter registers both "prime:..." and "primevue:..." aliases for each widget so consumers
 *   that expect either naming convention will resolve the PrimeVue adapters.
 */
export function registerPrimeVueWidgets(components = {}, options = {}) {
  if (components.InputText) {
    const wrapped = createInputWrapper(components.InputText);
    registerWidget('prime:InputText', wrapped);
    // Alias for alternative naming conventions
    registerWidget('primevue:InputText', wrapped);
  }
  if (components.Textarea) {
    const wrapped = createInputWrapper(components.Textarea);
    registerWidget('prime:Textarea', wrapped);
    registerWidget('primevue:Textarea', wrapped);
  }
  if (components.Checkbox) {
    const wrapped = createInputWrapper(components.Checkbox);
    registerWidget('prime:Checkbox', wrapped);
    registerWidget('primevue:Checkbox', wrapped);
  }
  if (components.Stepper) {
    // Stepper usually needs full control, so register the component directly.
    registerWidget('prime:Stepper', components.Stepper);
    registerWidget('primevue:Stepper', components.Stepper);
  }

  // Register sensible type mappings if requested. Keep the original behaviour:
  const mapStringTo = options.string ?? (components.InputText ? 'prime:InputText' : undefined);
  if (mapStringTo) {
    registerTypeMapping('string', mapStringTo);
  }
}

export default registerPrimeVueWidgets;
