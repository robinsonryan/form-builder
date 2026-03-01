<template>
  <div class="file-upload">
    <label :for="inputId" style="display:block; font-weight:600;">{{ label }}</label>
    <input :id="inputId" type="file" @change="onFileChange" :disabled="uploading" />
    <div v-if="uploading" style="font-size:0.9rem; color:#666;">Uploading...</div>
    <div v-if="error" style="color:#b00020; margin-top:0.25rem;">{{ error }}</div>
    <div v-if="value && value.filename" style="margin-top:0.25rem;">
      Uploaded: {{ value.filename }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import apiClient from '../../../core/apiClient.js';

const props = defineProps({
  modelValue: { required: false },
  label: { type: String, default: 'File' },
  presignUrl: { type: String, default: '/api/form-builder/file-presign' },
  name: { type: String, required: false },
});

const emit = defineEmits(['update:modelValue']);

const inputId = `file-upload-${Math.random().toString(36).slice(2, 9)}`;
const uploading = ref(false);
const error = ref(null);
const value = computed(() => props.modelValue || null);

async function onFileChange(e) {
  const file = e.target.files && e.target.files[0];
  if (!file) return;

  uploading.value = true;
  error.value = null;

  try {
    // Request presign from server
    const presign = await apiClient.postFilePresign(props.presignUrl, {
      filename: file.name,
      content_type: file.type || 'application/octet-stream',
    });

    if (!presign || !presign.uploadUrl || !presign.key) {
      throw new Error('Invalid presign response');
    }

    // Upload the file directly to the returned uploadUrl via PUT
    const uploadRes = await fetch(presign.uploadUrl, {
      method: 'PUT',
      headers: {
        'Content-Type': file.type || 'application/octet-stream',
      },
      body: file,
    });

    if (!uploadRes.ok) {
      const text = await uploadRes.text().catch(() => 'upload failed');
      throw new Error(`Upload failed: ${text}`);
    }

    // On success, emit a file reference object suitable for answers payload
    const fileRef = {
      key: presign.key,
      filename: presign.filename || file.name,
      content_type: presign.content_type || file.type,
    };

    emit('update:modelValue', fileRef);
  } catch (err) {
    // Surface error to UI
    error.value = err?.message || String(err);
  } finally {
    uploading.value = false;
    // Clear the input so same-file uploads can be retried
    const input = document.getElementById(inputId);
    if (input) input.value = '';
  }
}
</script>

<style scoped>
.file-upload input[disabled] {
  opacity: 0.6;
}
</style>
