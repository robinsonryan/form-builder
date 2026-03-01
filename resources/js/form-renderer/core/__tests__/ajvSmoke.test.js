import { test, expect } from 'vitest';
import { buildStepSubschema } from '../buildStepSubschema';
import { compileSchema, validateData } from '../ajvClient';
import { normalizeAjvErrors } from '../errorMapper';

test('Ajv validates a step subschema and returns a normalized required error', () => {
  const schema = {
    type: 'object',
    properties: {
      email: { type: 'string', format: 'email' },
      name: { type: 'string' },
    },
    required: ['email', 'name'],
  };

  const uiStepMaps = [
    {
      id: 'step1',
      properties: ['email'],
      ui_schema: {},
      // explicit per-step required list (optional)
      required: ['email'],
    },
  ];

  // Build step subschema for step1 (should only include 'email')
  const { schema: stepSchema } = buildStepSubschema(schema, uiStepMaps, 'step1');

  // Compile and validate an empty payload (missing required 'email')
  const compiled = compileSchema(stepSchema);
  const rawErrors = validateData(compiled, {});

  // Ensure Ajv returned at least one error
  expect(rawErrors.length).toBeGreaterThan(0);

  // Normalize and assert on the first error
  const normalized = normalizeAjvErrors(rawErrors);
  expect(normalized.length).toBeGreaterThan(0);
  expect(normalized[0]).toHaveProperty('path');
  expect(normalized[0]).toHaveProperty('code');
  expect(normalized[0]).toHaveProperty('message');

  // Required error should point at the email property
  expect(normalized[0].path).toBe('#/properties/email');
  expect(normalized[0].code).toBe('required');
});
