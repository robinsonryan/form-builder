import Ajv from 'ajv';
import addFormats from 'ajv-formats';

/**
 * createAjvInstance(options)
 *
 * Create an Ajv instance configured for typical form validation usage:
 * - allErrors: collect all errors
 * - strict: disabled (to be forgiving for evolving schemas); can be enabled via options
 * - format validators (email, date, etc.) enabled by default via ajv-formats unless
 *   options.enableFormats === false.
 *
 * Note: this file now depends on the ajv-formats package being installed in the app.
 */
export function createAjvInstance(options = {}) {
  const ajvOptions = options.ajvOptions ?? {};
  const ajv = new Ajv({ allErrors: true, strict: false, ...ajvOptions });

  // enable ajv-formats unless explicitly disabled
  if (options.enableFormats !== false) {
    addFormats(ajv);
  }

  return ajv;
}

/**
 * compileSchema(schema, options)
 *
 * Compile the provided JSON Schema using Ajv and return an object containing
 * the compiled validate function and the Ajv instance.
 *
 * Usage:
 * const compiled = compileSchema(schema);
 * const errors = validateData(compiled, data);
 */
export function compileSchema(schema, options = {}) {
  // forward full options object to createAjvInstance so callers can control
  // ajvOptions and enableFormats flag via the same options parameter.
  const ajv = createAjvInstance(options);
  const validate = ajv.compile(schema);
  return { validate, ajv };
}

/**
 * validateData(compiled, data)
 *
 * Run the compiled Ajv validate against data and return the raw Ajv errors array
 * (empty array when validation passes).
 */
export function validateData(compiled, data) {
  const { validate } = compiled;
  const ok = validate(data);
  return ok ? [] : (validate.errors || []);
}
