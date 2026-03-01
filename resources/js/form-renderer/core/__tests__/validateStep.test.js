import { beforeEach, describe, it, expect, vi } from 'vitest';

// Mock apiClient before importing useForm so the module import is stubbed within useForm
vi.mock('../apiClient.js', () => {
  return {
    default: {
      postJson: vi.fn(),
      postValidate: vi.fn(),
    },
  };
});

import { useForm } from '../useForm.js';
import apiClient from '../apiClient.js';

describe('useForm.validateStep', () => {
  beforeEach(() => {
    vi.resetAllMocks();
  });

  it('returns client-side Ajv required error for missing required property (skipServer)', async () => {
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
        required: ['email'],
        ui_schema: {},
      },
    ];

    const form = useForm({});
    form.setConfig({ schema, uiStepMaps, serverValidateUrl: '/validate' });

    const errors = await form.validateStep('step1', { skipServer: true });

    expect(Array.isArray(errors)).toBe(true);
    expect(errors.length).toBeGreaterThan(0);
    expect(errors[0].path).toBe('#/properties/email');
    expect(errors[0].code).toBe('required');
    expect(form.getErrors()).toEqual(errors);
  });

  it('merges server-side x-rule errors when serverValidateUrl is configured', async () => {
    const schema = {
      type: 'object',
      properties: {
        phone: { type: 'string' },
      },
    };

    const uiStepMaps = [
      {
        id: 'step1',
        properties: ['phone'],
        ui_schema: {},
      },
    ];

    const serverErrors = [
      { path: '#/properties/phone', code: 'phone_blocked', message: 'Phone blocked' },
    ];

    apiClient.postValidate.mockResolvedValue(serverErrors);

    const form = useForm({ phone: '555-0000' });
    form.setConfig({ schema, uiStepMaps, serverValidateUrl: '/validate' });

    const errors = await form.validateStep('step1', { skipServer: false });

    expect(apiClient.postValidate).toHaveBeenCalledTimes(1);
    expect(Array.isArray(errors)).toBe(true);
    expect(errors).toEqual(serverErrors);
    expect(form.getErrors()).toEqual(serverErrors);
  });

  it('deduplicates identical client and server errors', async () => {
    const schema = {
      type: 'object',
      properties: {
        email: { type: 'string' },
      },
      required: ['email'],
    };

    const uiStepMaps = [
      {
        id: 'step1',
        properties: ['email'],
        required: ['email'],
        ui_schema: {},
      },
    ];

    // Server returns same required error that Ajv will produce
    const serverErrors = [{ path: '#/properties/email', code: 'required', message: 'Email is required' }];
    apiClient.postValidate.mockResolvedValue(serverErrors);

    const form = useForm({});
    form.setConfig({ schema, uiStepMaps, serverValidateUrl: '/validate' });

    const errors = await form.validateStep('step1', { skipServer: false });

    // Should only contain one entry for the same path+code
    expect(errors.length).toBe(1);
    expect(errors[0].path).toBe('#/properties/email');
    expect(errors[0].code).toBe('required');
  });
});
