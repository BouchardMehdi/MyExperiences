import { base } from '$app/paths';

const API_ROOT = `${base}/api`;

/**
 * @typedef {Record<string, unknown>} ApiPayload
 */

/**
 * @param {string} path
 * @param {RequestInit} [options]
 * @returns {Promise<ApiPayload>}
 */
export async function apiFetch(path, options = {}) {
  const response = await fetch(`${API_ROOT}${path}`, {
    headers: {
      Accept: 'application/json',
      ...(options.headers ? Object.fromEntries(new Headers(options.headers).entries()) : {})
    },
    ...options
  });

  if (!response.ok) {
    throw new Error(`API request failed with status ${response.status}`);
  }

  /** @type {ApiPayload} */
  return response.json();
}

/**
 * @returns {Promise<ApiPayload>}
 */
export async function fetchHealth() {
  return apiFetch('/health');
}

/**
 * @returns {Promise<ApiPayload>}
 */
export async function fetchHello() {
  return apiFetch('/hello');
}
