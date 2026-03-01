/**
 * Widget registry for renderer-core (Vue-focused helpers).
 *
 * Public API:
 * - registerWidget(name, widget)
 * - resolveWidget(name) -> widget | undefined
 * - registerTypeMapping(typeName, widgetName)
 * - resolveWidgetForField(schema, uiSchema) -> { name, widget } | null
 * - setStrictRendering(boolean)
 * - clearWidgets()
 *
 * Precedence:
 * 1. explicit ui:widget -> must be registered (throws in strict mode)
 * 2. adapter type mapping (registered via registerTypeMapping)
 * 3. fallback registry entries like 'core:<type>' or '<type>'
 *
 * This module remains framework-agnostic about the widget value itself (it can be a
 * Vue component, a render function, or any resolver). Adapters should register
 * wrapped Vue components that conform to the contract expected by the renderer.
 */

import { markRaw } from 'vue';
const _registry = new Map();
const _typeMappings = new Map();

let _strictRendering =
  !(typeof process !== 'undefined' && process.env && process.env.NODE_ENV === 'production');

export function setStrictRendering(value) {
  _strictRendering = Boolean(value);
}

export function getStrictRendering() {
  return _strictRendering;
}

export function registerWidget(name, widget) {
  if (!name) throw new Error('widget name is required');
  // Prevent Vue from making component objects reactive which can cause warnings
  // and unnecessary performance overhead when components are later used in templates.
  _registry.set(String(name), markRaw(widget));
}

export function resolveWidget(name) {
  if (name == null) return undefined;
  return _registry.get(String(name));
}

export function registerTypeMapping(typeName, widgetName) {
  if (!typeName) throw new Error('typeName is required');
  if (!widgetName) throw new Error('widgetName is required');
  _typeMappings.set(String(typeName), String(widgetName));
}

export function resolveTypeMapping(typeName) {
  return _typeMappings.get(String(typeName));
}

/**
 * resolveWidgetForField
 *
 * schema: JSON Schema fragment for the field (may include `type`)
 * uiSchema: UI schema fragment for the field (may include 'ui:widget')
 *
 * Returns:
 *  - { name, widget } when a widget resolver is found
 *  - null when none found (and strict rules allow degradation)
 *
 * Throws when ui:widget is explicit and unknown while strictRendering=true.
 */
export function resolveWidgetForField(schema = {}, uiSchema = {}) {
  const explicit = uiSchema && uiSchema['ui:widget'];
  if (explicit) {
    const widget = resolveWidget(explicit);
    if (widget) return { name: String(explicit), widget };
    if (_strictRendering) {
      throw new Error(`Unknown explicit ui:widget "${explicit}"`);
    }
    return null;
  }

  const schemaType = schema && schema.type;
  if (schemaType) {
    // 1) Adapter-provided type mapping (e.g., 'string' -> 'prime:InputText').
    // If a mapping exists it takes precedence. If the mapped widget is not registered,
    // return null to avoid silently falling back when an adapter explicitly indicated a preference.
    const mapped = resolveTypeMapping(schemaType);
    if (mapped !== undefined) {
      const mappedWidget = resolveWidget(mapped);
      if (mappedWidget) return { name: String(mapped), widget: mappedWidget };
      return null;
    }

    // 2) Prefer a direct registration under the raw type name (e.g., 'string').
    const byType = resolveWidget(schemaType);
    if (byType) return { name: String(schemaType), widget: byType };

    // 3) Then try the namespaced core:<type> key (e.g., 'core:string').
    const coreKey = `core:${schemaType}`;
    const byCore = resolveWidget(coreKey);
    if (byCore) return { name: coreKey, widget: byCore };
  }

  // Nothing found
  return null;
}

export function clearWidgets() {
  _registry.clear();
  _typeMappings.clear();
}

export default {
  registerWidget,
  resolveWidget,
  registerTypeMapping,
  resolveTypeMapping,
  resolveWidgetForField,
  setStrictRendering,
  getStrictRendering,
  clearWidgets,
};
