<template>
  <div class="show-form-page">
    <h1 style="margin-bottom:1rem;">{{ title }}</h1>

    <FormRenderer
      v-if="publishedVersion"
      :schema="publishedVersion.schema"
      :uiStepMaps="publishedVersion.ui_step_maps"
      :initialAnswers="publishedVersion.initial_answers"
      :submitUrl="publishedVersion.submit_url"
      @submit="onSubmit"
      @onSubmitError="onSubmitError"
    />

    <div v-else>
      <p>Loading form...</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import FormRenderer from '../../form-renderer/adapters/primevue/FormRenderer.vue';

const props = defineProps({
  // Inertia pages typically pass server props; accept publishedVersion when present.
  publishedVersion: { type: Object, required: false, default: null },
  title: { type: String, required: false, default: 'Form' },
});

const emit = defineEmits(['submit', 'submitError']);

function onSubmit(payload) {
  // Re-emit so parent (Inertia page wrapper) can handle submission
  emit('submit', payload);
}

function onSubmitError(errors) {
  // Re-emit any submit-related errors
  emit('submitError', errors);
}
</script>

<style scoped>
.show-form-page {
  max-width: 720px;
  margin: 0 auto;
  padding: 1rem;
}
</style>
