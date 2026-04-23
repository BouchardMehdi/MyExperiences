<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { onMount } from 'svelte';
  import { authSession, clearAuthSession, getStoredAuthToken, updateAuthUser } from '$lib/auth/session';
  import { fetchCurrentUser } from '$lib/api/client';

  let error = '';
  let isLoading = true;
  /** @type {Record<string, any> | null} */
  let currentUser = null;

  onMount(async () => {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    try {
      const response = await fetchCurrentUser(token);

      if (response.data && typeof response.data === 'object') {
        updateAuthUser(/** @type {Record<string, unknown>} */ (response.data));
      } else {
        throw new Error('La reponse utilisateur est incomplete.');
      }
    } catch (exception) {
      clearAuthSession();
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
      await goto(`${base}/login`);
      return;
    } finally {
      isLoading = false;
    }
  });

  $: currentUser = /** @type {Record<string, any> | null} */ ($authSession.user);
</script>

<svelte:head>
  <title>MyExperiences | Mon compte</title>
</svelte:head>

{#if isLoading}
  <section class="status-panel">Chargement de votre compte...</section>
{:else if error}
  <section class="status-panel error">{error}</section>
{:else if currentUser}
  <section class="account-shell">
    <div class="account-hero">
      <span class="eyebrow">Espace personnel</span>
      <h1>{currentUser.fullName || 'Compte MyExperiences'}</h1>
      <p>
        Votre session API Bearer est active. Cette page servira de point d'entree pour les
        reservations, paiements et avis quand on branchera la suite.
      </p>
    </div>

    <div class="details-grid">
      <article class="panel">
        <span class="eyebrow">Profil</span>
        <dl>
          <div>
            <dt>Email</dt>
            <dd>{currentUser.email}</dd>
          </div>
          <div>
            <dt>Prenom</dt>
            <dd>{currentUser.firstName}</dd>
          </div>
          <div>
            <dt>Nom</dt>
            <dd>{currentUser.lastName}</dd>
          </div>
          <div>
            <dt>Roles</dt>
            <dd>{Array.isArray(currentUser.roles) ? currentUser.roles.join(', ') : 'ROLE_USER'}</dd>
          </div>
        </dl>
      </article>

      <article class="panel">
        <span class="eyebrow">Suite</span>
        <ul>
          <li>Mes reservations sera la prochaine page protegee naturelle.</li>
          <li>Le token actuel permet deja d'appeler `GET /api/me` et `POST /api/auth/logout`.</li>
          <li>La navigation principale sait maintenant si l'utilisateur est connecte.</li>
        </ul>
      </article>
    </div>
  </section>
{/if}

<style>
  .account-shell {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
  }

  .account-hero,
  .panel,
  .status-panel {
    padding: 1.5rem;
    border-radius: 1.8rem;
    background: rgba(255, 252, 248, 0.88);
    border: 1px solid rgba(139, 95, 61, 0.12);
    box-shadow: 0 20px 60px rgba(88, 54, 30, 0.08);
  }

  .details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }

  .eyebrow {
    display: inline-block;
    margin-bottom: 0.8rem;
    padding: 0.38rem 0.8rem;
    border-radius: 999px;
    background: rgba(230, 205, 180, 0.42);
    color: #875a39;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 700;
  }

  h1 {
    margin: 0;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: clamp(2.2rem, 5vw, 4rem);
    line-height: 1.04;
    color: #24160e;
  }

  p,
  li {
    line-height: 1.75;
    color: #5f5146;
  }

  dl {
    display: grid;
    gap: 0.9rem;
    margin: 0;
  }

  dt {
    margin-bottom: 0.3rem;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #7d746c;
  }

  dd {
    margin: 0;
    color: #2a2019;
    font-weight: 700;
  }

  ul {
    margin: 0;
    padding-left: 1.15rem;
  }

  .status-panel.error {
    color: #9c2f20;
    border-color: rgba(156, 47, 32, 0.16);
    background: rgba(255, 244, 241, 0.92);
  }

  @media (max-width: 860px) {
    .details-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
