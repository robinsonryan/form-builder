# Form Renderer — Event flow and where validation events are handled

This note explains where validation errors are surfaced and which component emits the events so you can wire handlers in parent pages or tests.

Short summary
- `useForm` (core composable) does *not* emit DOM/Vue events. It runs validation and returns `{ ok, errors }` and stores the last errors internally.
- `FormRenderer.vue` is responsible for calling `form.validate()` and *emitting* events when validation or submit outcomes occur.
- Parent components (pages or tests) must listen to the events emitted by `FormRenderer.vue` (for example `onSubmitError`, `onSubmitSuccess`, and `submit`) to react.

Event flow (detailed)
1. User edits a field:
   - `FieldRenderer` emits `update:modelValue` which updates `localAnswers` in `FormRenderer.vue`.
   - A `watch` handler in `FormRenderer.vue` calls `form.setField(...)` to update `useForm` internal answers.

2. User submits the form:
   - `FormRenderer.vue` handles the `<form @submit.prevent="onSubmit">` event.
   - In `onSubmit()` the renderer calls `await form.validate()`.
     - If validation fails, `FormRenderer.vue` sets `lastErrors` and emits `onSubmitError` with the normalized errors array.
     - It returns early and does not call the network submit.
   - If validation passes, `FormRenderer.vue` calls `form.submit()` and depending on the response emits `onSubmitSuccess` or `onSubmitError` (and also emits a generic `submit` event with payload/errors).

Where to handle the events
- In the parent Vue component/template that renders `<FormRenderer />`.

Example: template usage (kebab-case event names are safe in DOM templates)
```vue
<template>
  <FormRenderer
    :schema="schema"
    :submitUrl="submitUrl"
    @on-submit-error="handleSubmitError"
    @on-submit-success="handleSubmitSuccess"
    @submit="handleSubmitGeneric"
  />
</template>

<script setup>
function handleSubmitError(errors) {
  // errors is an array of { path, code, message }
  console.log('Form validation/submission errors:', errors);
}

function handleSubmitSuccess(submissionId) {
  console.log('Submit succeeded, id:', submissionId);
}

function handleSubmitGeneric(payload) {
  // generic submit event includes payload or errors depending on outcome
  console.log('submit event', payload);
}
</script>
```

Example: programmatic (JS) event listener when mounting component instance
```js
// If you register event listeners programmatically (rare in SFC templates):
const vm = createApp({ /* ... */ }).mount('#app');
// or when using `h()` and mounting component instances, ensure you pass listeners in props
```

Debugging checklist if you don't see handlers firing
- Confirm the parent actually registers a handler for the events emitted (`onSubmitError` vs. `on-submit-error`).
  - In template markup prefer kebab-case: `@on-submit-error="..."`.
  - In JSX or programmatic event registration you can use the camelCase name.
- Inspect console logs in `FormRenderer.vue` or add temporary console statements where `emit('onSubmitError', lastErrors)` is called to verify execution.
- Ensure `FormRenderer` is the same component instance your parent is rendering (no duplicate components or mistaken imports).
- Check that the parent is not accidentally preventing event propagation (rare for root-level emits but possible in nested patterns).
- Confirm `form.validate()` is actually returning errors (use `console.log(validation)` in `onSubmit` if uncertain).

Notes about naming and Vue event handling
- Vue normalizes event names in DOM templates; prefer kebab-case in templates: `@on-submit-error`.
- When using SFC `<script setup>` and passing functions directly, kebab-case is the safer choice in the template.

If you'd like I can:
- Add lightweight console.debug lines in `FormRenderer.vue` where `onSubmitError`/`onSubmitSuccess` are emitted (helpful for debugging during E2E).
- Or add a small README note inside the package docs (this file) explaining the recommended parent usage with examples (already included above).
