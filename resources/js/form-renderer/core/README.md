# Renderer Core — Widget Registry & FieldRenderer contract

This document describes the minimal expectations between the renderer-core and UI adapters (Vue / PrimeVue).

Registry API (provided by packages/form-builder/resources/js/form-renderer/core/widgetRegistry.js)
- registerWidget(name: string, widget: any)
- resolveWidget(name: string) => widget | undefined
- registerTypeMapping(typeName: string, widgetName: string)
- resolveWidgetForField(schema: object, uiSchema: object) => { name, widget } | null
- setStrictRendering(boolean)
- getStrictRendering() => boolean
- clearWidgets()

Resolution precedence (used by FieldRenderer)
1. If uiSchema contains an explicit `ui:widget`, resolveWidgetForField will attempt to resolve that exact name.
    - If strictRendering is true and the widget is unknown, resolveWidgetForField will throw.
    - If strictRendering is false and unknown, resolveWidgetForField returns null (FieldRenderer should render its fallback).
2. If no explicit widget, the registry prefers:
   a. A direct registration under the raw type key (e.g., `string`) if present.
   b. An adapter-provided type mapping (registered via registerTypeMapping).
   c. A namespaced core fallback key (e.g., `core:string` or `core:InputText`).
3. If nothing is found, resolveWidgetForField returns null and FieldRenderer should render a fallback (the core provides minimal HTML primitives).

Notes on strictRendering
- Purpose: provide fail-fast behavior for developers (catch typos or missing adapter wiring) while allowing graceful degradation in production.
- Recommended defaults:
    - Development: strictRendering = true (fail fast when ui:widget names are unknown).
    - Production: strictRendering = false (render fallback UI and avoid throwing).
- The app bootstrap should centrally set strictRendering once during app startup (see ADAPTERS.md for examples).

FieldRenderer contract (Vue adapter)
- FieldRenderer components should call resolveWidgetForField(schema, uiSchema) to obtain the widget resolver.
- If a widget is returned, FieldRenderer should render that component, passing these props:
    - modelValue (v-model)
    - schema
    - name
    - optionally uiOptions and errors
- If no widget is returned, FieldRenderer should render a minimal fallback (e.g., an <input>) to ensure forms remain usable.
- In development, prefer failing fast when an explicit ui:widget is unknown (strictRendering=true). In production, adapters may choose to log and render a fallback.

Adapter guidelines (PrimeVue)
- Adapters must register their widgets with registerWidget('prime:InputText', Component).
- Adapters may register type mappings to prefer adapter widgets site-wide:
    - registerTypeMapping('string', 'prime:InputText')
- Provide thin wrappers that adapt adapter component props/events to the core widget contract (modelValue / update:modelValue).
- Prefer adapter-registered type mappings over core primitives; this allows adapter wrappers to take precedence without changing schema.

Testing notes
- The registry is unit-tested independently (see widgetRegistry.spec.js).
- Integration tests should assert:
    - Core primitives are available by default.
    - Adapter-registered widgets and type mappings override core primitives.
    - resolveWidgetForField obeys strictRendering for explicit ui:widget values.
- FieldRenderer behavior can be tested by asserting resolveWidgetForField outputs and by mounting the Vue FieldRenderer in integration tests using @vue/test-utils.

# Adapter Integration Guide — Vue + PrimeVue

This short guide documents the recommended wiring between an app, the renderer core, and an adapter (PrimeVue).

Goals
- Make core primitives available by default (so forms can render without adapters).
- Allow adapters to override primitives by registering their widgets and type mappings.
- Provide a single place to control strictRendering policy.

Bootstrap (app entry)
- Import and set strict rendering once at app startup (set to false in production by default):

Example: app/resources/js/bootstrap.js
```javascript
import { setStrictRendering } from '../../../packages/form-builder/resources/js/form-renderer/core/widgetRegistry.js';

const isProduction =
  typeof process !== 'undefined' && process.env && process.env.NODE_ENV === 'production';

setStrictRendering(!isProduction); // true in dev, false in production

// then register core widgets (optional dynamic import)
import('../../../packages/form-builder/resources/js/form-renderer/core/registerCoreWidgets.js')
  .then((mod) => mod?.registerCoreWidgets?.())
  .catch((err) => console.warn('registerCoreWidgets missing', err));
```

Adapter registration (PrimeVue)
- Register adapter widgets and, optionally, prefer adapter widgets for base types via type mappings.

Example adapter registration:
```javascript
import { registerWidget, registerTypeMapping } from '../../../packages/form-builder/resources/js/form-renderer/core/widgetRegistry.js';
import PrimeInputText from 'primevue/inputtext';
import PrimeStepper from 'primevue/steps';

// Register adapter components
registerWidget('prime:InputText', PrimeInputText);
registerWidget('prime:Stepper', PrimeStepper);

// Prefer adapter widgets for common types
registerTypeMapping('string', 'prime:InputText');
registerTypeMapping('stepper', 'prime:Stepper');
```

FieldRenderer expectations
- FieldRenderer should call resolveWidgetForField(schema, uiSchema).
- If a widget is returned, render it passing modelValue / update:modelValue and other contract props.
- If null is returned, render a minimal fallback so the form remains usable.
