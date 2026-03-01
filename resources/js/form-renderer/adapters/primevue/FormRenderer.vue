<template>
    <form class="form-renderer" @submit.prevent="onSubmit" novalidate>

        <div v-if="errorsByField['#'] && errorsByField['#'].length" :id="globalErrorsId" class="global-errors" role="alert" style="color:#b00020; margin-bottom:0.75rem;">
            <div v-for="(e, i) in errorsByField['#']" :key="i" class="global-error">{{ e.message || String(e) }}</div>
        </div>

        <div v-if="schema && Object.keys(schema.properties || {}).length === 0">
            <p>No fields to render.</p>
        </div>

        <div v-else>
            <FieldRenderer
                v-for="(propName, idx) in propertyNames"
                :key="propName"
                :name="propName"
                :schema="schema.properties[propName]"
                v-model="localAnswers[propName]"
                :errors="errorsByField[propName] || []"
            />
        </div>

        <div style="margin-top:1rem; display:flex; gap:0.5rem; align-items:center;">
            <button type="submit" :disabled="submitting">
                {{ submitting ? 'Submitting...' : 'Submit' }}
            </button>
        </div>
    </form>
</template>

<script setup>
import { reactive, computed, toRefs, ref, watch } from 'vue';
import FieldRenderer from './FieldRenderer.vue';
import { useForm } from '../../core/useForm.js';

const props = defineProps({
    schema: { type: Object, default: () => ({ properties: {} }) },
    uiStepMaps: { type: Array, default: () => [] },
    submitUrl: { type: String, default: '' }, // server submit endpoint
    initialAnswers: { type: Object, default: () => ({}) },
    formKey: { type: String, default: '' },
    versionId: { type: String, default: '' },
});

const emit = defineEmits(['onSubmitSuccess', 'onSubmitError']);

const form = useForm(props.initialAnswers || {});

form.setConfig({
    schema: props.schema,
    uiStepMaps: props.uiStepMaps,
    submitUrl: props.submitUrl,
    formKey: props.formKey,
    versionId: props.versionId,
});

const localAnswers = reactive({ ...form.getAnswers() });
const submitting = ref(false);
const lastErrors = ref([]);
// generate a stable id for the global error container for aria-describedby
const globalErrorsId = `form-errors-${Math.random().toString(36).slice(2,9)}`;

/**
 * findFocusable(el)
 * Helper to find a focusable descendant inside an element.
 */
function findFocusable(el) {
    if (!el) return null;
    return el.querySelector('input,select,textarea,button,[tabindex]:not([tabindex="-1"])');
}

/**
 * focusFirstInvalid(formEl)
 *
 * Focus the first invalid control inside the form (preferring aria-invalid="true"),
 * set its aria-describedby to the global errors container id, and scroll it into view.
 */
function focusFirstInvalid(formEl) {
    try {
        const root = formEl || document.querySelector('form.form-renderer');
        if (!root) return;
        let invalidEl = root.querySelector('[aria-invalid="true"]') || root.querySelector('.invalid');
        if (!invalidEl) return;
        // If the invalid element is a wrapper, try to find a focusable descendant.
        const focusable = (invalidEl.matches && (invalidEl.matches('input,select,textarea,button') ? invalidEl : null)) || findFocusable(invalidEl) || invalidEl;
        if (focusable && typeof focusable.focus === 'function') {
            try {
                focusable.focus({ preventScroll: false });
            } catch (e) {
                // Some environments may not support options; fall back
                focusable.focus();
            }
            focusable.setAttribute('aria-describedby', globalErrorsId);
            try {
                focusable.scrollIntoView({ block: 'center', behavior: 'smooth' });
            } catch (_) {
                // ignore scroll errors in test envs
            }
        }
    } catch (e) {
        // eslint-disable-next-line no-console
        console.debug('focusFirstInvalid error', e);
    }
}

const propertyNames = computed(() => Object.keys(props.schema.properties || {}));

function mapErrorsToFields(errors) {
    const byField = {};
    if (!Array.isArray(errors)) return byField;
    for (const e of errors) {
        const path = e.path || '';
        // Expecting paths like '#/properties/<name>'
        const m = path.match(/^#\/properties\/([^\/]+)/);
        if (m) {
            const name = m[1];
            if (!byField[name]) byField[name] = [];
            byField[name].push(e);
        } else {
            // global or unknown -> attach to '#' key
            if (!byField['#']) byField['#'] = [];
            byField['#'].push(e);
        }
    }
    return byField;
}

const errorsByField = computed(() => mapErrorsToFields(lastErrors.value));

/**
 * Keep the simple reactive localAnswers in sync with the form state.
 * When a field changes in the UI we update the core useForm answers.
 */
watch(
    localAnswers,
    (nv) => {
        for (const key of Object.keys(nv || {})) {
            form.setField(key, nv[key]);
        }
    },
    { deep: true }
);

function syncToForm(name, value) {
    form.setField(name, value);
}

async function onSubmit(event) {
    // Prevent duplicate submissions
    if (submitting.value) return;

    // Run client (and optional server) validation before attempting submit
    const validation = await form.validate();
    if (!validation.ok) {
        lastErrors.value = Array.isArray(validation.errors) ? validation.errors : [];
        emit('onSubmitError', lastErrors.value);
        // Focus the first invalid control and set its aria-describedby to the global error container.
        focusFirstInvalid(event?.target);
        return;
    }

    submitting.value = true;
    try {
        const res = await form.submit();
        if (res.ok) {
            lastErrors.value = [];
            // Emit both the success event and a generic 'submit' event with current answers
            // so higher-level pages/consumers get the payload for demo/E2E purposes.
            emit('onSubmitSuccess', res.submission_id ?? null);
            try {
                // publish the current answers snapshot
                const payload = typeof form.getAnswers === 'function' ? form.getAnswers() : {};
                emit('submit', payload);
            } catch (e) {
                // fallback: still signal success even if payload extraction fails
                emit('submit', {});
            }
        } else {
            lastErrors.value = Array.isArray(res.errors) ? res.errors : [];
            emit('onSubmitError', lastErrors.value);
            // also emit a submit event with errors for consumers that listen generically
            emit('submit', { errors: lastErrors.value });
            // focus first invalid if server returned errors
            focusFirstInvalid(event?.target);
        }
    } catch (err) {
        lastErrors.value = [
            {
                path: '#',
                code: 'client_error',
                message: err?.message || String(err),
            },
        ];
        emit('onSubmitError', lastErrors.value);
        emit('submit', { errors: lastErrors.value });
        focusFirstInvalid(event?.target);
    } finally {
        submitting.value = false;
    }
}

</script>
