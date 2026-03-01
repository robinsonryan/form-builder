import {
  registerWidget,
  registerTypeMapping,
} from './widgetRegistry.js';

import InputText from './coreWidgets/InputText.vue';
import Textarea from './coreWidgets/Textarea.vue';
import Checkbox from './coreWidgets/Checkbox.vue';
import Stepper from './coreWidgets/Stepper.vue';

/**
 * registerCoreWidgets
 *
 * Registers a minimal set of Vue-based core widgets into the widget registry.
 * These are purposely tiny implementations intended as sensible defaults for
 * demos/tests and are easily overridden by adapters.
 */
export function registerCoreWidgets() {
  // Namespaced registrations
  registerWidget('core:InputText', InputText);
  registerWidget('core:Textarea', Textarea);
  registerWidget('core:Checkbox', Checkbox);
  registerWidget('core:Stepper', Stepper);

  // Non-namespaced fallbacks: map JSON Schema base types to core widgets.
  // resolveWidgetForField prefers explicit ui:widget, then type mappings,
  // then raw type key ('string'), then 'core:string'. Registering the raw type
  // key here allows simple schemas (type: 'string') to render.
  registerWidget('string', InputText);
  registerWidget('textarea', Textarea); // if UI schema uses 'textarea' as widget name
  registerWidget('boolean', Checkbox);

  // Register a sensible default type mapping so adapters and app-level code can
  // remap primitives to namespaced widgets. This maps the JSON Schema 'string'
  // type to the core namespaced widget by default.
  registerTypeMapping('string', 'core:InputText');
}

export default registerCoreWidgets;
