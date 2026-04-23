import { base } from '$app/paths';

const API_ROOT = `${base}/api`;

/**
 * @typedef {Record<string, unknown>} ApiPayload
 */

/**
 * @param {string | null | undefined} token
 * @returns {HeadersInit | undefined}
 */
export function createAuthHeaders(token) {
  if (!token) {
    return undefined;
  }

  return {
    Authorization: `Bearer ${token}`
  };
}

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

  const contentType = response.headers.get('content-type') || '';
  const payload = contentType.includes('application/json') ? await response.json() : null;

  if (!response.ok) {
    const message =
      payload &&
      typeof payload === 'object' &&
      'error' in payload &&
      payload.error &&
      typeof payload.error === 'object' &&
      'message' in payload.error &&
      typeof payload.error.message === 'string'
        ? payload.error.message
        : `API request failed with status ${response.status}`;

    throw new Error(message);
  }

  /** @type {ApiPayload} */
  return payload || {};
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

/**
 * @param {{ location?: string; maxPrice?: string; price?: string; date?: string }} [filters]
 * @returns {Promise<ApiPayload>}
 */
export async function fetchExperiences(filters = {}) {
  const params = new URLSearchParams();

  if (filters.location) {
    params.set('location', filters.location);
  }

  if (filters.maxPrice || filters.price) {
    params.set('maxPrice', filters.maxPrice || filters.price || '');
  }

  if (filters.date) {
    params.set('date', filters.date);
  }

  const query = params.toString();

  return apiFetch(`/experiences${query ? `?${query}` : ''}`);
}

/**
 * @param {string | number} id
 * @returns {Promise<ApiPayload>}
 */
export async function fetchExperienceById(id) {
  return apiFetch(`/experiences/${id}`);
}

/**
 * @param {{ email: string; password: string; firstname: string; lastname: string }} payload
 * @returns {Promise<ApiPayload>}
 */
export async function registerUser(payload) {
  return apiFetch('/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
}

/**
 * @param {{ email: string; password: string }} payload
 * @returns {Promise<ApiPayload>}
 */
export async function loginUser(payload) {
  return apiFetch('/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  });
}

/**
 * @param {string} token
 * @returns {Promise<ApiPayload>}
 */
export async function fetchCurrentUser(token) {
  return apiFetch('/me', {
    headers: createAuthHeaders(token)
  });
}

/**
 * @param {string} token
 * @returns {Promise<ApiPayload>}
 */
export async function logoutUser(token) {
  return apiFetch('/auth/logout', {
    method: 'POST',
    headers: createAuthHeaders(token)
  });
}

/**
 * @param {string} token
 * @returns {Promise<ApiPayload>}
 */
export async function fetchMyBookings(token) {
  return apiFetch('/bookings', {
    headers: createAuthHeaders(token)
  });
}

/**
 * @param {string} token
 * @param {{ slotId: number | string; seats: number | string }} payload
 * @returns {Promise<ApiPayload>}
 */
export async function createBooking(token, payload) {
  return apiFetch('/bookings', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...createAuthHeaders(token)
    },
    body: JSON.stringify(payload)
  });
}

/**
 * @param {string} token
 * @param {number | string} bookingId
 * @returns {Promise<ApiPayload>}
 */
export async function cancelBooking(token, bookingId) {
  return apiFetch(`/bookings/${bookingId}/cancel`, {
    method: 'POST',
    headers: createAuthHeaders(token)
  });
}
