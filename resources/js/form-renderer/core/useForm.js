/**
 * Lightweight, framework-agnostic form state composable.
 *
 * API:
 * - initialize(initialAnswers): replace internal answers state
 * - setField(path, value): set a deep value using dot-path (e.g. "user.name")
 * - getAnswers(): returns a deep-cloned answers object
 * - setConfig(cfg): provide schema, uiStepMaps and server endpoints used by validateStep
 * - validateStep(stepId, options): run client-side Ajv validation and optionally server-side x-rule validation
 * - submit(options): perform final submission including Idempotency-Key header
 *
 * Keep implementation plain JS so it can be reused across frameworks or extracted.
 */

import { buildStepSubschema } from './buildStepSubschema.js';
import { compileSchema, validateData } from './ajvClient.js';
import { normalizeAjvErrors } from './errorMapper.js';
import apiClient from './apiClient.js';

export function useForm(initialAnswers = {}, opts = {}) {
  let answers = deepClone(initialAnswers);
  let config = opts || {};
  let lastErrors = [];
  let pendingSubmitPromise = null;

  function deepClone(value) {
    return JSON.parse(JSON.stringify(value ?? {}));
  }

  function initialize(initial = {}) {
    answers = deepClone(initial);
  }

  function setConfig(cfg = {}) {
    config = { ...(config || {}), ...cfg };
  }

  function setField(path, value) {
    if (!path) return;
    const parts = String(path).split('.');
    let cur = answers;
    for (let i = 0; i < parts.length - 1; i++) {
      const p = parts[i];
      if (cur[p] === undefined || cur[p] === null || typeof cur[p] !== 'object') {
        cur[p] = {};
      }
      cur = cur[p];
    }
    cur[parts[parts.length - 1]] = value;
  }

  function getAnswers() {
    return deepClone(answers);
  }

  function getErrors() {
    return deepClone(lastErrors);
  }

  function extractStepAnswers(stepSchema, allAnswers) {
    const props = Object.keys(stepSchema.properties || {});
    const out = {};
    for (const p of props) {
      if (Object.prototype.hasOwnProperty.call(allAnswers, p)) {
        out[p] = allAnswers[p];
      } else {
        // keep undefined for missing keys so Ajv required checks can trigger
        out[p] = undefined;
      }
    }
    return out;
  }

  /**
   * validateStep(stepId, options)
   *
   * Performs client-side Ajv validation for the step's subschema and, when configured,
   * performs a server-side validation call to catch x-rule errors that require round-trip.
   *
   * Options:
   * - skipServer: boolean to skip the server-side validation call
   * - serverPayload: optional extra payload merged into server request body
   *
   * Returns: array of normalized errors: [{ path, code, message }, ...]
   */
  async function validateStep(stepId, options = {}) {
    const { skipServer = false, serverPayload = {} } = options;
    const rootSchema = config.schema ?? {};
    const uiStepMaps = config.uiStepMaps ?? [];

    const built = buildStepSubschema(rootSchema, uiStepMaps, stepId);
    const stepSchema = built.schema ?? { type: 'object', properties: {} };

    // Compile and run Ajv against only the properties in the step
    const compiled = compileSchema(stepSchema);
    const allAnswers = getAnswers();
    const answersForValidation = extractStepAnswers(stepSchema, allAnswers);

    const rawClientErrors = validateData(compiled, answersForValidation);
    const clientErrors = normalizeAjvErrors(rawClientErrors);

    // Optionally call server-side validation endpoint for x-rules / cross-field rules
    let serverErrors = [];
    if (!skipServer && config.serverValidateUrl) {
      try {
        // Provide stepId, step schema and the subset of answers relevant to the step
        serverErrors = await apiClient.postValidate(config.serverValidateUrl, {
          stepId,
          schema: stepSchema,
          answers: answersForValidation,
          ...serverPayload,
        });
        if (!Array.isArray(serverErrors)) serverErrors = [];
      } catch (err) {
        // Non-fatal: represent network/server error as a generic validation error for the step
        serverErrors = [
          {
            path: '#',
            code: 'server_error',
            message: (err && err.message) || 'Server validation failed',
          },
        ];
      }
    }

    // Merge and deduplicate by path+code (preserve client-side order first)
    const merged = [...clientErrors, ...serverErrors];
    const seen = new Set();
    const dedup = [];
    for (const e of merged) {
      const key = `${e.path}::${e.code}`;
      if (!seen.has(key)) {
        seen.add(key);
        dedup.push(e);
      }
    }

    lastErrors = dedup;
    return deepClone(dedup);
  }

  /**
   * uuidv4()
   *
   * Small UUID v4 generator suitable for browser/node environments.
   */
  function uuidv4() {
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

  /**
   * validate(options)
   *
   * Run client-side Ajv validation for the full form or a specific step (when stepId provided).
   * Optionally perform a server-side validation call (config.serverValidateUrl) to capture x-rules.
   *
   * Options:
   * - stepId: when provided, validate only the step's subschema
   * - skipServer: boolean to skip server-side validation
   * - serverPayload: additional payload merged into server validation request
   *
   * Returns: { ok: boolean, errors: [...] } where errors are normalized via normalizeAjvErrors
   */
  async function validate(options = {}) {
    const { stepId = undefined, skipServer = false, serverPayload = {} } = options;

    // Determine schema to validate (full form or step subschema)
    let schemaToValidate = config.schema ?? {};
    if (stepId && config.uiStepMaps) {
      try {
        const built = buildStepSubschema(schemaToValidate, config.uiStepMaps, stepId);
        schemaToValidate = built.schema ?? schemaToValidate;
      } catch (err) {
        // If subschema build fails, treat as validation failure with server error
        lastErrors = [
          {
            path: '#',
            code: 'client_error',
            message: (err && err.message) || 'Failed to derive step schema',
          },
        ];
        return { ok: false, errors: deepClone(lastErrors) };
      }
    }

    // Run Ajv validation
    try {
      const compiled = compileSchema(schemaToValidate);
      const dataForValidation = stepId ? extractStepAnswers(schemaToValidate, getAnswers()) : getAnswers();
      const rawClientErrors = validateData(compiled, dataForValidation);
      const clientErrors = normalizeAjvErrors(rawClientErrors);

      // Optionally call server-side validation endpoint for x-rules / cross-field checks
      let serverErrors = [];
      if (!skipServer && config.serverValidateUrl) {
        try {
          const payload = {
            stepId,
            schema: schemaToValidate,
            answers: dataForValidation,
            ...serverPayload,
          };
          const sv = await apiClient.postValidate(config.serverValidateUrl, payload);
          if (Array.isArray(sv)) {
            serverErrors = sv;
          } else if (sv && Array.isArray(sv.errors)) {
            serverErrors = sv.errors;
          } else {
            serverErrors = [];
          }
        } catch (err) {
          serverErrors = [
            {
              path: '#',
              code: 'server_error',
              message: (err && err.message) || 'Server validation failed',
            },
          ];
        }
      }

      // Merge client + server errors (client first), dedupe by path::code
      const merged = [...clientErrors, ...serverErrors];
      const seen = new Set();
      const dedup = [];
      for (const e of merged) {
        const key = `${e.path}::${e.code}`;
        if (!seen.has(key)) {
          seen.add(key);
          dedup.push(e);
        }
      }

      lastErrors = dedup;
      return { ok: dedup.length === 0, errors: deepClone(dedup) };
    } catch (err) {
      lastErrors = [
        {
          path: '#',
          code: 'client_error',
          message: (err && err.message) || 'Validation failed',
        },
      ];
      return { ok: false, errors: deepClone(lastErrors) };
    }
  }

    /**
     * submit(options)
     *
     * Perform final submission. Includes Idempotency-Key header.
     *
     * Options:
     * - idempotencyKey: optional string to reuse a client key
     * - payload: optional extra payload merged into request body
     *
     * Returns: { ok: boolean, submission_id?: string|null, errors?: [] }
     */
    async function submit(options = {}) {
        const { idempotencyKey, payload = {} } = options;
        const key = idempotencyKey || uuidv4();

        if (!config.submitUrl) {
            throw new Error('submitUrl not configured in form config');
        }

        // Coalesce concurrent submissions: return the same pending promise if a submit is already in flight.
        if (pendingSubmitPromise) {
            return pendingSubmitPromise;
        }

        pendingSubmitPromise = (async () => {
            try {
                const res = await apiClient.postJson(
                    config.submitUrl,
                    {
                        responses: getAnswers(),
                        ...payload,
                    },
                    {
                        headers: {
                            'Idempotency-Key': key,
                        },
                    }
                );

                if (res && res.ok) {
                    lastErrors = [];
                    return { ok: true, submission_id: res.submission_id ?? null };
                }

                const errors = Array.isArray(res?.errors)
                    ? res.errors
                    : [
                        {
                            path: '#',
                            code: 'server_error',
                            message: 'Submission failed',
                        },
                    ];

                lastErrors = errors;
                return { ok: false, errors };
            } catch (err) {
                const errObj = [
                    {
                        path: '#',
                        code: 'server_error',
                        message: (err && err.message) || String(err),
                    },
                ];
                lastErrors = errObj;
                return { ok: false, errors: errObj };
            } finally {
                // Allow subsequent submissions after this one settles.
                pendingSubmitPromise = null;
            }
        })();

        return pendingSubmitPromise;
    }

    /**
     * saveDraft(options)
     *
     * Persist the current answers as a draft for the configured form key.
     *
     * Options:
     * - payload: optional extra payload merged into request body
     * - draftSaveUrl: optional override for the save endpoint
     *
     * Returns: { ok: boolean, draft_id?: string|null, draft?: object, errors?: [] }
     */
    async function saveDraft(options = {}) {
        const { payload = {}, draftSaveUrl } = options;
        const url =
            draftSaveUrl || config.draftSaveUrl || `/api/form-builder/forms/${encodeURIComponent(config.formKey ?? '')}/drafts`;

        try {
            const res = await apiClient.postJson(
                url,
                {
                    answers: getAnswers(),
                    ...payload,
                },
                {}
            );

            if (res && res.ok) {
                return { ok: true, draft_id: res.draft?.id ?? null, draft: res.draft ?? null };
            }

            const errors = Array.isArray(res?.errors)
                ? res.errors
                : [
                    {
                        path: '#',
                        code: 'server_error',
                        message: 'Save draft failed',
                    },
                ];

            lastErrors = errors;
            return { ok: false, errors };
        } catch (err) {
            const errObj = [
                {
                    path: '#',
                    code: 'server_error',
                    message: (err && err.message) || String(err),
                },
            ];
            lastErrors = errObj;
            return { ok: false, errors: errObj };
        }
    }

    /**
     * loadDraft(draftId, options)
     *
     * Fetch a saved draft and populate internal answers state.
     *
     * Options:
     * - draftLoadUrl: optional override for the load endpoint
     *
     * Returns: { ok: boolean, draft?: object, errors?: [] }
     */
    async function loadDraft(draftId, options = {}) {
        const { draftLoadUrl } = options;
        if (!draftId) throw new Error('draftId is required');

        const url =
            draftLoadUrl || config.draftLoadUrl || `/api/form-builder/forms/${encodeURIComponent(config.formKey ?? '')}/drafts/${encodeURIComponent(draftId)}`;

        try {
            // use apiClient.getJson if available
            const res = typeof apiClient.getJson === 'function' ? await apiClient.getJson(url) : await fetch(url).then((r) => r.json());

            if (res && res.ok) {
                const draft = res.draft ?? {};
                // populate answers if present
                if (draft?.answers_json) {
                    initialize(draft.answers_json);
                }
                return { ok: true, draft };
            }

            const errors = Array.isArray(res?.errors)
                ? res.errors
                : [
                    {
                        path: '#',
                        code: 'server_error',
                        message: 'Load draft failed',
                    },
                ];

            lastErrors = errors;
            return { ok: false, errors };
        } catch (err) {
            const errObj = [
                {
                    path: '#',
                    code: 'server_error',
                    message: (err && err.message) || String(err),
                },
            ];
            lastErrors = errObj;
            return { ok: false, errors: errObj };
        }
    }

    return {
        initialize,
        setConfig,
        setField,
        getAnswers,
        getErrors,
        validate,
        validateStep,
        submit,
        saveDraft,
        loadDraft,
    };
}

export default useForm;
