/**
 * Optional idempotency helper.
 *
 * Provides small utilities to generate and persist an Idempotency-Key client-side.
 * This is intentionally lightweight and uses localStorage when available.
 */

export function generateKey() {
  if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
    const arr = new Uint8Array(16);
    crypto.getRandomValues(arr);
    arr[6] = (arr[6] & 0x0f) | 0x40;
    arr[8] = (arr[8] & 0x3f) | 0x80;
    return [...arr]
      .map((b) => b.toString(16).padStart(2, '0'))
      .join('')
      .replace(/^(.{8})(.{4})(.{4})(.{4})(.{12})$/, '$1-$2-$3-$4-$5');
  }

  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

export function saveKey(key) {
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem('form:idempotency_key', String(key));
    }
  } catch {
    // ignore
  }
}

export function loadKey() {
  try {
    if (typeof localStorage !== 'undefined') {
      return localStorage.getItem('form:idempotency_key');
    }
  } catch {
    // ignore
  }
  return null;
}

export function clearKey() {
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('form:idempotency_key');
    }
  } catch {
    // ignore
  }
}
