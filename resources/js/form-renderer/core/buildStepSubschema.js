/**
 * buildStepSubschema(schema, uiStepMaps, stepId)
 *
 * Lightweight implementation that:
 * - Returns the full schema when no uiStepMaps entry is found for the step.
 * - Projects the schema to include only the listed properties for the step.
 * - Preserves a step-level `required` array when provided by the uiStepMaps entry
 *   (falls back to filtering the original schema.required to only properties included
 *    in the step projection).
 *
 * This yields a valid JSON Schema object that Ajv can compile for step-level validation.
 */
export function buildStepSubschema(schema = {}, uiStepMaps = [], stepId) {
  // Find the mapping for the requested step
  const map = Array.isArray(uiStepMaps) ? uiStepMaps.find((m) => m.id === stepId) : undefined;

  if (!map) {
    // Fallback: return the full schema as a safe default
    return { schema };
  }

  // Project only listed properties if present
  const properties = {};
  if (Array.isArray(map.properties)) {
    for (const p of map.properties) {
      if (schema?.properties?.[p]) {
        properties[p] = schema.properties[p];
      }
    }
  }

  // Determine required[] for the projected subschema:
  // - Prefer map.required (explicit per-step required list) filtered to included properties
  // - Otherwise, filter the root schema.required to included properties
  let required = [];
  if (Array.isArray(map.required)) {
    required = map.required.filter((r) => Object.prototype.hasOwnProperty.call(properties, r));
  } else if (Array.isArray(schema?.required)) {
    required = schema.required.filter((r) => Object.prototype.hasOwnProperty.call(properties, r));
  }

  const subschema = {
    type: 'object',
    properties,
  };

  if (required.length > 0) {
    subschema.required = required;
  }

  return {
    schema: subschema,
    ui: map.ui_schema ?? null,
  };
}
