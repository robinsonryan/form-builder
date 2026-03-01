import { beforeEach, describe, it, expect, vi } from 'vitest';

// Mock the apiClient module used by useForm (must be mocked before importing useForm)
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

describe('useForm.submit', () => {
  beforeEach(() => {
    vi.resetAllMocks();
  });

  it('posts answers with Idempotency-Key header and returns success', async () => {
    const form = useForm({ foo: 'bar' });
    form.setConfig({ submitUrl: '/submit' });

    apiClient.postJson.mockResolvedValue({ ok: true, submission_id: 'sub-123' });

    const res = await form.submit({ idempotencyKey: 'my-client-key' });

    expect(apiClient.postJson).toHaveBeenCalledTimes(1);

    const [url, payload, options] = apiClient.postJson.mock.calls[0];
    expect(url).toBe('/submit');
    expect(payload).toEqual({ answers: { foo: 'bar' } });
    expect(options).toHaveProperty('headers');
    expect(options.headers['Idempotency-Key']).toBe('my-client-key');

    expect(res.ok).toBe(true);
    expect(res.submission_id).toBe('sub-123');
    expect(form.getErrors()).toEqual([]);
  });

  it('returns server validation errors and stores them on failure', async () => {
    const form = useForm({ a: 1 });
    form.setConfig({ submitUrl: '/submit' });

    const serverErrors = [
      { path: '#/properties/a', code: 'unique_in_period', message: 'Already used' },
    ];
    apiClient.postJson.mockResolvedValue({ ok: false, errors: serverErrors });

    const res = await form.submit();

    expect(apiClient.postJson).toHaveBeenCalledTimes(1);
    expect(res.ok).toBe(false);
    expect(res.errors).toEqual(serverErrors);
    expect(form.getErrors()).toEqual(serverErrors);
  });

  it('handles network/server exceptions and returns a server_error shaped error', async () => {
    const form = useForm({ b: 2 });
    form.setConfig({ submitUrl: '/submit' });

    apiClient.postJson.mockRejectedValue(new Error('network-failure'));

    const res = await form.submit();

    expect(apiClient.postJson).toHaveBeenCalledTimes(1);
    expect(res.ok).toBe(false);
    expect(Array.isArray(res.errors)).toBe(true);
    expect(res.errors[0]).toHaveProperty('code', 'server_error');
    expect(form.getErrors()[0]).toHaveProperty('code', 'server_error');
  });
});
