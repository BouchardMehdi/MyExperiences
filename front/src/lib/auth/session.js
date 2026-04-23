import { browser } from '$app/environment';
import { writable } from 'svelte/store';

const STORAGE_KEY = 'myexperiences.auth.token';

/** @typedef {{ token: string | null, user: Record<string, unknown> | null, ready: boolean }} AuthSession */

export const authSession = writable(
  /** @type {AuthSession} */ ({
    token: null,
    user: null,
    ready: false
  })
);

export function initializeAuthSession() {
  const token = browser ? window.localStorage.getItem(STORAGE_KEY) : null;

  authSession.set({
    token,
    user: null,
    ready: true
  });
}

/**
 * @param {string} token
 * @param {Record<string, unknown>} user
 */
export function setAuthSession(token, user) {
  if (browser) {
    window.localStorage.setItem(STORAGE_KEY, token);
  }

  authSession.set({
    token,
    user,
    ready: true
  });
}

export function clearAuthSession() {
  if (browser) {
    window.localStorage.removeItem(STORAGE_KEY);
  }

  authSession.set({
    token: null,
    user: null,
    ready: true
  });
}

export function getStoredAuthToken() {
  if (!browser) {
    return null;
  }

  return window.localStorage.getItem(STORAGE_KEY);
}

/**
 * @param {Record<string, unknown>} user
 */
export function updateAuthUser(user) {
  authSession.update((session) => ({
    ...session,
    user,
    ready: true
  }));
}
