<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { get } from 'svelte/store';
  import { onMount } from 'svelte';
  import {
    authSession,
    clearAuthSession,
    getStoredAuthToken,
    initializeAuthSession,
    updateAuthUser
  } from '$lib/auth/session';
  import { fetchCurrentUser, logoutUser } from '$lib/api/client';

  let isHydratingSession = true;

  onMount(() => {
    void hydrateSession();
  });

  async function hydrateSession() {
    initializeAuthSession();

    const token = getStoredAuthToken();

    if (!token) {
      isHydratingSession = false;
      return;
    }

    try {
      const response = await fetchCurrentUser(token);

      if (response.data && typeof response.data === 'object') {
        updateAuthUser(/** @type {Record<string, unknown>} */ (response.data));
      } else {
        clearAuthSession();
      }
    } catch {
      clearAuthSession();
    } finally {
      isHydratingSession = false;
    }
  }

  $: currentUser = /** @type {Record<string, any> | null} */ ($authSession.user);
  $: homeHref = currentUser ? `${base}/space` : `${base}/`;

  async function handleLogout() {
    const token = get(authSession).token;

    try {
      if (typeof token === 'string' && token) {
        await logoutUser(token);
      }
    } catch {
    } finally {
      clearAuthSession();
      await goto(`${base}/`);
    }
  }

  /**
   * @param {Record<string, any> | null | undefined} user
   */
  function isOrganizerUser(user) {
    if (!user || !Array.isArray(user.roles)) {
      return false;
    }

    return user.roles.includes('ROLE_ORGANIZER') || user.roles.includes('ROLE_ADMIN');
  }

  /**
   * @param {Record<string, any> | null | undefined} user
   */
  function isAdminUser(user) {
    if (!user || !Array.isArray(user.roles)) {
      return false;
    }

    return user.roles.includes('ROLE_ADMIN');
  }
  </script>

<svelte:head>
  <meta
    name="description"
    content="MyExperiences rassemble des experiences reservees en ligne, avec un espace public inspire, un compte personnel et des outils organisateur."
  />
</svelte:head>

<div class="app-shell">
  <div class="aurora aurora-one"></div>
  <div class="aurora aurora-two"></div>

  <header class="site-header">
    <a class="brand" href={homeHref}>
      <span class="brand-mark">ME</span>
      <span class="brand-copy">
        <strong>MyExperiences</strong>
        <small>Experiences reservees, moments a raconter</small>
      </span>
    </a>

    <nav class="site-nav">
      {#if currentUser}
        <a href={`${base}/space`}>Mon espace</a>
        <a href={`${base}/experiences`}>Experiences</a>
        {#if isOrganizerUser(currentUser)}
          <a href={`${base}/organizer`}>Organisateur</a>
        {/if}
        {#if isAdminUser(currentUser)}
          <a href={`${base}/admin`}>Admin</a>
        {/if}
        <a href={`${base}/account`}>Mon compte</a>
        <button on:click={handleLogout} type="button">Se deconnecter</button>
      {:else}
        <a href={`${base}/`}>Accueil</a>
        <a href={`${base}/experiences`}>Experiences</a>
        <a href={`${base}/login`}>Connexion</a>
        <a class="accent-link" href={`${base}/register`}>Creer un compte</a>
      {/if}
    </nav>
  </header>

  {#if isHydratingSession}
    <div class="session-banner">Verification de la session en cours...</div>
  {/if}

  <main class="content">
    <slot />
  </main>
</div>

<style>
  :global(:root) {
    --page-bg: #f7efe5;
    --surface: rgba(255, 251, 246, 0.84);
    --surface-strong: rgba(255, 250, 244, 0.96);
    --border-soft: rgba(112, 71, 45, 0.12);
    --shadow-soft: 0 24px 70px rgba(66, 40, 19, 0.08);
    --text-main: #20150f;
    --text-soft: #685347;
    --accent: #b45a34;
    --accent-dark: #8a3f25;
    --accent-pale: rgba(224, 166, 131, 0.18);
    --mint: #1f7e5c;
  }

  :global(body) {
    margin: 0;
    min-height: 100vh;
    font-family: 'Aptos', 'Segoe UI', sans-serif;
    background:
      radial-gradient(circle at top left, rgba(255, 213, 176, 0.42), transparent 28%),
      radial-gradient(circle at 88% 12%, rgba(157, 215, 196, 0.32), transparent 24%),
      linear-gradient(180deg, #fff9f2 0%, var(--page-bg) 48%, #f2e7da 100%);
    color: var(--text-main);
  }

  :global(*) {
    box-sizing: border-box;
  }

  .app-shell {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
  }

  .aurora {
    position: absolute;
    border-radius: 999px;
    filter: blur(24px);
    opacity: 0.5;
    pointer-events: none;
  }

  .aurora-one {
    top: 2rem;
    left: -4rem;
    width: 16rem;
    height: 16rem;
    background: rgba(255, 196, 148, 0.28);
  }

  .aurora-two {
    top: 8rem;
    right: -3rem;
    width: 14rem;
    height: 14rem;
    background: rgba(161, 223, 202, 0.2);
  }

  .site-header {
    position: sticky;
    top: 0;
    z-index: 20;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    max-width: 1220px;
    margin: 0 auto;
    padding: 1.2rem 1.4rem 0;
  }

  .brand {
    display: inline-flex;
    align-items: center;
    gap: 0.9rem;
    text-decoration: none;
    color: inherit;
    backdrop-filter: blur(12px);
  }

  .brand-mark {
    display: inline-grid;
    place-items: center;
    width: 3rem;
    height: 3rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--accent-dark), #d27b42);
    color: #fffaf4;
    font-weight: 800;
    letter-spacing: 0.08em;
    box-shadow: 0 14px 28px rgba(138, 63, 37, 0.24);
  }

  .brand-copy {
    display: grid;
    gap: 0.12rem;
  }

  .brand-copy strong {
    font-family: 'Constantia', Georgia, serif;
    font-size: 1.15rem;
  }

  .brand-copy small {
    color: var(--text-soft);
    font-size: 0.82rem;
  }

  .site-nav {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    justify-content: center;
    padding: 0.45rem;
    border-radius: 999px;
    background: rgba(255, 251, 246, 0.75);
    border: 1px solid var(--border-soft);
    box-shadow: var(--shadow-soft);
    backdrop-filter: blur(16px);
  }

  .site-nav a,
  .site-nav button {
    min-height: 2.85rem;
    padding: 0.75rem 1rem;
    border-radius: 999px;
    text-decoration: none;
    color: #5c4738;
    font-weight: 700;
    background: transparent;
    border: 0;
    font: inherit;
    cursor: pointer;
    transition:
      background 180ms ease,
      color 180ms ease,
      transform 180ms ease;
  }

  .site-nav a:hover,
  .site-nav button:hover {
    background: rgba(224, 193, 166, 0.32);
    color: var(--accent-dark);
    transform: translateY(-1px);
  }

  .site-nav .accent-link {
    background: var(--accent);
    color: #fff9f2;
  }

  .site-nav .accent-link:hover {
    background: var(--accent-dark);
    color: #fff9f2;
  }

  .session-banner {
    max-width: 1220px;
    margin: 0.8rem auto 0;
    padding: 0.95rem 1.15rem;
    border-radius: 1rem;
    background: rgba(255, 251, 246, 0.82);
    border: 1px solid var(--border-soft);
    color: var(--text-soft);
    backdrop-filter: blur(10px);
  }

  .content {
    position: relative;
    max-width: 1220px;
    margin: 0 auto;
    padding: 1.35rem 1.4rem 4rem;
  }

  @media (max-width: 860px) {
    .site-header {
      position: static;
      flex-direction: column;
      align-items: stretch;
    }

    .site-nav {
      justify-content: center;
    }
  }
</style>
