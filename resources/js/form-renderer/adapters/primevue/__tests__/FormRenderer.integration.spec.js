import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

// Declare mocks in file scope so we can inspect calls/results inside tests
const saveDraftMock = vi.fn().mockResolvedValue({ ok: true, draft_id: 'd123', draft: { id: 'd123' }});
const loadDraftMock = vi.fn().mockResolvedValue({ ok: true, draft: { id: 'd123', answers_json: { foo: 'bar' } }});
const submitMock = vi.fn().mockResolvedValue({ ok: true, submission_id: 's123' });

// Provide mock factories for likely module specifiers the component might use.
// Some bundlers/resolvers include or omit ".js" or resolve relative paths differently;
// mocking both variants ensures the mock is applied regardless of minor path differences.
vi.mock('../../../../core/useForm.js', () => {
    return {
        __esModule: true,
        useForm: (initialAnswers = {}) => ({
            setConfig: vi.fn(),
            getAnswers: () => ({}),
            saveDraft: saveDraftMock,
            loadDraft: loadDraftMock,
            submit: submitMock,
            setField: vi.fn(),
            getErrors: () => [],
            initialize: vi.fn(),
        }),
    };
});

// Also attempt the neighboring specifier to be robust to resolver differences.
vi.mock('../../../core/useForm', () => {
    return {
        __esModule: true,
        useForm: (initialAnswers = {}) => ({
            setConfig: vi.fn(),
            getAnswers: () => ({}),
            saveDraft: saveDraftMock,
            loadDraft: loadDraftMock,
            submit: submitMock,
            setField: vi.fn(),
            getErrors: () => [],
            initialize: vi.fn(),
        }),
    };
});

import FormRenderer from '../FormRenderer.vue';

describe('FormRenderer integration', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('invokes saveDraft, loadDraft and submit and emits the corresponding events', async () => {
        const wrapper = mount(FormRenderer, {
            props: {
                schema: { properties: { a: { type: 'string' } } },
                uiStepMaps: [],
                submitUrl: '/submit',
                formKey: 'form1',
            },
        });

        // Trigger save draft (component should call the mocked saveDraft)
        await wrapper.vm.onSaveDraft();

        // Allow microtasks/nextTick to flush any emits
        await nextTick();
        await Promise.resolve();

        // Ensure the mocked function was actually invoked
        expect(saveDraftMock).toHaveBeenCalled();

        const eventsAfterSave = wrapper.emitted() || {};
        const draftSavedKey = Object.keys(eventsAfterSave).find((k) => /draft.*saved/i.test(k));
        expect(draftSavedKey, `no draft-saved event found; emitted keys: ${Object.keys(eventsAfterSave).join(', ')}`).toBeDefined();
        expect(eventsAfterSave[draftSavedKey].length).toBeGreaterThan(0);

        // Trigger load draft
        wrapper.vm.draftIdInput = 'd123';
        await wrapper.vm.onLoadDraft();

        await nextTick();
        await Promise.resolve();

        expect(loadDraftMock).toHaveBeenCalled();

        const eventsAfterLoad = wrapper.emitted() || {};
        const draftLoadedKey = Object.keys(eventsAfterLoad).find((k) => /draft.*loaded/i.test(k));
        expect(draftLoadedKey, `no draft-loaded event found; emitted keys: ${Object.keys(eventsAfterLoad).join(', ')}`).toBeDefined();
        expect(eventsAfterLoad[draftLoadedKey].length).toBeGreaterThan(0);

        // Trigger submit
        await wrapper.vm.onSubmit();

        await nextTick();
        await Promise.resolve();

        expect(submitMock).toHaveBeenCalled();

        const eventsAfterSubmit = wrapper.emitted() || {};
        const submitSuccessKey = Object.keys(eventsAfterSubmit).find((k) => /submit.*success/i.test(k));
        expect(submitSuccessKey, `no submit-success event found; emitted keys: ${Object.keys(eventsAfterSubmit).join(', ')}`).toBeDefined();
        expect(eventsAfterSubmit[submitSuccessKey][0][0]).toBe('s123');
    });
});
