/**
 * normalizeAjvErrors(ajvErrors)
 *
 * Convert Ajv error objects to the canonical { path, code, message } format:
 * - path: JSON Pointer style starting with '#', using '/properties/<name>' for object properties
 * - code: short keyword derived from Ajv error keyword (e.g. 'required')
 * - message: human-friendly message (falls back to Ajv's message)
 *
 * Notes:
 * - For "required" errors Ajv sets instancePath to the parent object and params.missingProperty
 *   to the missing name; we combine those to point directly at the missing property.
 * - This mapper is intentionally simple and easy to extend.
 */

function toJsonPointer(instancePath = '', missingProperty) {
  // instancePath from Ajv looks like "/a/b/0/c" where tokens are raw object keys or array indexes.
  const tokens = instancePath.split('/').filter(Boolean);
  let ptr = '#';

  for (const t of tokens) {
    // numeric tokens represent array indices; keep them as-is
    if (/^\d+$/.test(t)) {
      ptr += `/${t}`;
    } else {
      // properties on objects are represented with an extra '/properties/<name>' per project convention
      ptr += `/properties/${t}`;
    }
  }

  if (typeof missingProperty === 'string' && missingProperty.length > 0) {
    ptr += `/properties/${missingProperty}`;
  }

  return ptr === '#' ? '#/' : ptr;
}

const KEYWORD_MAP = {
  required: 'required',
  type: 'type',
  minLength: 'minLength',
  maxLength: 'maxLength',
  pattern: 'pattern',
  format: 'format',
  minimum: 'minimum',
  maximum: 'maximum',
  enum: 'enum',
};

/**
 * normalizeAjvErrors
 */
export function normalizeAjvErrors(errors = []) {
  if (!Array.isArray(errors) || errors.length === 0) return [];

  return errors.map((err) => {
    const keyword = err.keyword || (err?.params && err.params.missingProperty ? 'required' : 'validation');
    const code = KEYWORD_MAP[keyword] ?? keyword;

    // Build a JSON Pointer path that points at the schema property that failed.
    // For required errors, Ajv provides params.missingProperty which we append.
    const instancePath = err.instancePath ?? err.dataPath ?? '';
    const path = toJsonPointer(instancePath, err.keyword === 'required' ? err.params?.missingProperty : undefined);

    const message = err.message ?? JSON.stringify(err.params ?? {});

    return {
      path,
      code,
      message,
    };
  });
}
