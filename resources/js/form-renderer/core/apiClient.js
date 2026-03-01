/**
 * Tiny fetch wrapper used by renderer-core for endpoint interactions.
 * Keeps surface area minimal so it can be swapped for axios/fetch wrappers in apps.
 */

export async function postJson(url, payload = {}, options = {}) {
    // Build headers with sensible defaults for Laravel JSON endpoints.
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(options.headers || {}),
    };

    // Try to attach CSRF token:
    // - Prefer a meta tag (if present).
    // - Fallback to reading the XSRF-TOKEN cookie and set X-XSRF-TOKEN to match axios behavior.
    try {
        if (typeof document !== 'undefined') {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute('content') && !headers['X-CSRF-TOKEN'] && !headers['X-XSRF-TOKEN']) {
                headers['X-CSRF-TOKEN'] = meta.getAttribute('content');
            } else {
                const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
                if (m && !headers['X-XSRF-TOKEN'] && !headers['X-CSRF-TOKEN']) {
                    headers['X-XSRF-TOKEN'] = decodeURIComponent(m[1]);
                }
            }
        }
    } catch (e) {
        // ignore in non-browser or if cookie/parsing is not available
    }

    const fetchOpts = {
        method: 'POST',
        headers,
        body: JSON.stringify(payload),
        credentials: (options.fetchOptions && options.fetchOptions.credentials) || 'same-origin',
        ...(options.fetchOptions || {}),
    };

    const res = await fetch(url, fetchOpts);
    const text = await res.text();

    // Try to interpret response as JSON. If parsing fails, surface an informative error
    // including a snippet of the body so HTML error pages are obvious during debugging.
    try {
        const contentType = res.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const json = text ? JSON.parse(text) : {};
            if (!res.ok) {
                const err = new Error('Network response was not ok');
                err.response = json;
                throw err;
            }
            return json;
        }

        // If content-type is not json, still try to parse (some servers return json without header).
        if (text && text.trim().length > 0) {
            const maybeJson = JSON.parse(text);
            if (!res.ok) {
                const err = new Error('Network response was not ok');
                err.response = maybeJson;
                throw err;
            }
            return maybeJson;
        }

        // Non-JSON empty response but OK
        if (res.ok) {
            return text;
        }

        // Non-OK non-JSON response: surface body
        const err = new Error(text || 'Network response was not ok');
        err.response = text;
        throw err;
    } catch (err) {
        if (err instanceof SyntaxError) {
            const snippet = (text || '').slice(0, 2000);
            const e = new Error(`Invalid JSON response from ${url} (status ${res.status}): ${snippet}`);
            e.status = res.status;
            e.text = text;
            throw e;
        }
        throw err;
    }
}

/**
 * postValidate(url, payload, options)
 *
 * Convenience helper for server-side validation endpoints. Expects the server to return
 * a JSON body that may include an "errors" array (matching the FE { path, code, message } shape).
 * Returns the errors array (or an empty array when none).
 */
export async function postValidate(url, payload = {}, options = {}) {
    const res = await postJson(url, payload, options);
    // If server returns { errors: [...] } normalize by returning the array, or return empty array.
    if (res && Array.isArray(res.errors)) {
        return res.errors;
    }
    return [];
}

/**
 * postFilePresign(url, payload, options)
 *
 * Request a presigned upload URL and metadata for direct-to-storage uploads.
 * Returns the server JSON as-is (expected shape: { ok: true, uploadUrl, key, filename, content_type }).
 */
export async function postFilePresign(url, payload = {}, options = {}) {
    const res = await postJson(url, payload, options);
    return res;
}

/**
 * getJson(url, options)
 *
 * Minimal GET helper that returns parsed JSON and throws on non-OK responses.
 */
export async function getJson(url, options = {}) {
    const headers = {
        'Accept': 'application/json',
        ...(options.headers || {}),
    };

    // Attach CSRF cookie->header if present (useful if GET endpoints require it for some flows)
    try {
        if (typeof document !== 'undefined') {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.getAttribute('content') && !headers['X-CSRF-TOKEN'] && !headers['X-XSRF-TOKEN']) {
                headers['X-CSRF-TOKEN'] = meta.getAttribute('content');
            } else {
                const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
                if (m && !headers['X-XSRF-TOKEN'] && !headers['X-CSRF-TOKEN']) {
                    headers['X-XSRF-TOKEN'] = decodeURIComponent(m[1]);
                }
            }
        }
    } catch (e) {
        // ignore
    }

    const fetchOpts = {
        method: 'GET',
        headers,
        credentials: (options.fetchOptions && options.fetchOptions.credentials) || 'same-origin',
        ...(options.fetchOptions || {}),
    };

    const res = await fetch(url, fetchOpts);
    const text = await res.text();

    try {
        const contentType = res.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const json = text ? JSON.parse(text) : {};
            if (!res.ok) {
                const err = new Error('Network response was not ok');
                err.response = json;
                throw err;
            }
            return json;
        }

        if (text && text.trim().length > 0) {
            const maybeJson = JSON.parse(text);
            if (!res.ok) {
                const err = new Error('Network response was not ok');
                err.response = maybeJson;
                throw err;
            }
            return maybeJson;
        }

        if (res.ok) {
            return text;
        }

        const err = new Error(text || 'Network response was not ok');
        err.response = text;
        throw err;
    } catch (err) {
        if (err instanceof SyntaxError) {
            const snippet = (text || '').slice(0, 2000);
            const e = new Error(`Invalid JSON response from ${url} (status ${res.status}): ${snippet}`);
            e.status = res.status;
            e.text = text;
            throw e;
        }
        throw err;
    }
}

export default {
    postJson,
    postValidate,
    postFilePresign,
    getJson,
};
