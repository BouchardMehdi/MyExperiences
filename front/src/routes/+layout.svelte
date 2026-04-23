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

  onMount(async () => {
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
  });

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
</script>

<svelte:head>
  <meta
    name="description"
    content="MyExperiences, une selection d'experiences reservees en ligne depuis un frontend SvelteKit branche sur une API Symfony."
  />
</svelte:head>

<div class="app-shell">
  <header class="site-header">
    <a class="brand" href={`${base}/`}>
      <span class="brand-mark">ME</span>
      <span class="brand-copy">
        <strong>MyExperiences</strong>
        <small>Escapades, ateliers et moments memorables</small>
      </span>
    </a>

    <nav class="site-nav">
      <a href={`${base}/`}>Accueil</a>
      <a href={`${base}/experiences`}>Experiences</a>

      {#if $authSession.user}
        {#if isOrganizerUser($authSession.user)}
          <a href={`${base}/organizer`}>Organisateur</a>
        {/if}
        <a href={`${base}/account`}>Mon compte</a>
        <button on:click={handleLogout} type="button">Se deconnecter</button>
      {:else}
        <a href={`${base}/login`}>Connexion</a>
        <a href={`${base}/register`}>Inscription</a>
      {/if}
    </nav>
  </header>

  {#if isHydratingSession}
    <div class="session-banner">Verification de la session...</div>
  {/if}

  <main class="content">
    <slot />
  </main>
</div>

<style>
  :global(body) {
    margin: 0;
    min-height: 100vh;
    font-family: 'Trebuchet MS', 'Aptos', sans-serif;
    background:
      radial-gradient(circle at top left, rgba(255, 205, 150, 0.45), transparent 28%),
      radial-gradient(circle at right 18%, rgba(214, 235, 226, 0.8), transparent 22%),
      linear-gradient(180deg, #fffaf4 0%, #f7eee2 48%, #f4f1ec 100%);
    color: #24160e;
  }

  :global(*) {
    box-sizing: border-box;
  }

  .app-shell {
    min-height: 100vh;
  }

  .site-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1.5rem;
    max-width: 1180px;
    margin: 0 auto;
    padding: 1.3rem 1.4rem 0;
  }

  .brand {
    display: inline-flex;
    align-items: center;
    gap: 0.9rem;
    text-decoration: none;
    color: inherit;
  }

  .brand-mark {
    display: inline-grid;
    place-items: center;
    width: 2.9rem;
    height: 2.9rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, #9d5b35, #da8b4d);
    color: #fffdf8;
    font-weight: 800;
    letter-spacing: 0.06em;
  }

  .brand-copy {
    display: grid;
    gap: 0.12rem;
  }

  strong {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.1rem;
  }

  small {
    color: #7a6555;
    font-size: 0.82rem;
  }

  .site-nav {
    display: inline-flex;
    gap: 0.5rem;
    padding: 0.4rem;
    border-radius: 999px;
    background: rgba(255, 250, 244, 0.88);
    border: 1px solid rgba(139, 95, 61, 0.12);
    box-shadow: 0 10px 24px rgba(85, 53, 33, 0.05);
    flex-wrap: wrap;
    justify-content: center;
  }

  .site-nav a,
  .site-nav button {
    padding: 0.7rem 1rem;
    border-radius: 999px;
    text-decoration: none;
    color: #5c4738;
    font-weight: 700;
    background: transparent;
    border: 0;
    font: inherit;
    cursor: pointer;
  }

  .site-nav a:hover,
  .site-nav button:hover {
    background: rgba(224, 193, 166, 0.3);
  }

  .session-banner {
    max-width: 1180px;
    margin: 0.8rem auto 0;
    padding: 0.9rem 1.2rem;
    border-radius: 1rem;
    background: rgba(255, 250, 244, 0.92);
    border: 1px solid rgba(139, 95, 61, 0.12);
    color: #6b5849;
  }

  .content {
    max-width: 1180px;
    margin: 0 auto;
    padding: 1.25rem 1.4rem 4rem;
  }

  @media (max-width: 720px) {
    .site-header {
      flex-direction: column;
      align-items: stretch;
    }

    .site-nav {
      justify-content: center;
    }
  }
</style>
