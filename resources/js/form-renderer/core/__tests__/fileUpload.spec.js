import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import FileUpload from '../../adapters/primevue/widgets/FileUpload.vue';

describe('FileUpload widget', () => {
    let originalFetch;

    beforeEach(() => {
        originalFetch = global.fetch;
        vi.resetAllMocks();
    });

    afterEach(() => {
        global.fetch = originalFetch;
    });

    it('requests presign, uploads file and emits file reference on success', async () => {
        // Mock presign POST (apiClient.postFilePresign -> postJson -> fetch POST)
        global.fetch = vi.fn((url, opts) => {
            // Presign POST
            if (opts && opts.method === 'POST') {
                return Promise.resolve({
                    ok: true,
                    headers: { get: () => 'application/json' },
                    json: async () => ({
                        ok: true,
                        uploadUrl: 'https://fake-storage.example/upload/abc',
                        key: 'uploads/abc/example.txt',
                        filename: 'example.txt',
                        content_type: 'text/plain',
                    }),
                });
            }

            // Upload PUT
            if (opts && opts.method === 'PUT') {
                return Promise.resolve({
                    ok: true,
                    text: async () => 'ok',
                });
            }

            return Promise.reject(new Error('unexpected fetch call'));
        });

        const wrapper = mount(FileUpload, {
            props: {
                modelValue: null,
                presignUrl: '/api/form-builder/file-presign',
            },
        });

        // Create a fake File
        const file = new File(['hello'], 'example.txt', { type: 'text/plain' });

        // Call the component's onFileChange handler directly
        await wrapper.vm.onFileChange({ target: { files: [file] } });

        // Expect an update:modelValue event with the file reference
        const events = wrapper.emitted()['update:modelValue'];
        expect(Array.isArray(events)).toBe(true);
        expect(events.length).toBeGreaterThan(0);
        const fileRef = events[0][0];
        expect(fileRef).toMatchObject({
            key: 'uploads/abc/example.txt',
            filename: 'example.txt',
            content_type: 'text/plain',
        });
    });

    it('surfaces an error and does not emit when upload fails', async () => {
        // Presign succeeds
        global.fetch = vi.fn((url, opts) => {
            if (opts && opts.method === 'POST') {
                return Promise.resolve({
                    ok: true,
                    headers: { get: () => 'application/json' },
                    json: async () => ({
                        ok: true,
                        uploadUrl: 'https://fake-storage.example/upload/abc',
                        key: 'uploads/abc/example.txt',
                        filename: 'example.txt',
                        content_type: 'text/plain',
                    }),
                });
            }

            // Simulate upload failure
            if (opts && opts.method === 'PUT') {
                return Promise.resolve({
                    ok: false,
                    text: async () => 'upload-rejected',
                });
            }

            return Promise.reject(new Error('unexpected fetch call'));
        });

        const wrapper = mount(FileUpload, {
            props: {
                modelValue: null,
                presignUrl: '/api/form-builder/file-presign',
            },
        });

        const file = new File(['hello'], 'example.txt', { type: 'text/plain' });

        await wrapper.vm.onFileChange({ target: { files: [file] } });

        // Should not have emitted an update
        const events = wrapper.emitted()['update:modelValue'];
        expect(events).toBeUndefined();

        // Should display an upload error message
        expect(wrapper.html()).toContain('Upload failed');
    });
});

/*
RECOMMENDED_ADDITIONS:
- packages/form-builder/resources/js/form-renderer/adapters/primevue/__tests__/FormRenderer.integration.spec.js
  Purpose: integration test to exercise FormRenderer's save/load/submit flows and event emissions.

- packages/form-builder/resources/js/form-renderer/core/__tests__/submit.spec.js
  Purpose: unit tests for useForm.submit() (mock apiClient.postJson) if not already present.

Add these tests when you want broader coverage of adapter-level wiring and submit behavior.
*/
