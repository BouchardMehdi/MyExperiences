<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { getStoredAuthToken, setAuthSession } from '$lib/auth/session';
  import { fetchCurrentUser, loginUser } from '$lib/api/client';
  import { onMount } from 'svelte';

  let email = '';
  let password = '';
  let error = '';
  let isSubmitting = false;

  onMount(async () => {
    const token = getStoredAuthToken();

    if (!token) {
      return;
    }

    try {
      const response = await fetchCurrentUser(token);

      if (response.data && typeof response.data === 'object') {
        setAuthSession(token, /** @type {Record<string, unknown>} */ (response.data));
        await goto(`${base}/account`);
      }
    } catch {
    }
  });

  async function submitLogin() {
    isSubmitting = true;
    error = '';

    try {
      const response = /** @type {{ token?: { value?: string }, user?: Record<string, unknown> }} */ (
        await loginUser({ email, password })
      );
      const token = response.token?.value;
      const user = response.user;

      if (typeof token !== 'string' || !user || typeof user !== 'object') {
        throw new Error('La reponse de connexion est incomplete.');
      }

      setAuthSession(token, user);
      await goto(`${base}/account`);
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      isSubmitting = false;
    }
  }
</script>

<svelte:head>
  <title>MyExperiences | Connexion</title>
</svelte:head>

<section class="auth-shell">
  <div class="intro">
    <span class="eyebrow">Connexion</span>
    <h1>Retrouver votre espace personnel.</h1>
    <p>
      L'API auth est maintenant active. Cette page cree une session Bearer cote front et ouvre la
      porte aux prochaines features reservees aux utilisateurs connectes.
    </p>
  </div>

  <form class="auth-card" on:submit|preventDefault={submitLogin}>
    <label>
      <span>Email</span>
      <input bind:value={email} autocomplete="email" placeholder="vous@example.com" type="email" />
    </label>

    <label>
      <span>Mot de passe</span>
      <input bind:value={password} autocomplete="current-password" placeholder="********" type="password" />
    </label>

    {#if error}
      <p class="error">{error}</p>
    {/if}

    <button class="primary" disabled={isSubmitting} type="submit">
      {isSubmitting ? 'Connexion...' : 'Se connecter'}
    </button>

    <p class="hint">
      Pas encore de compte ? <a href={`${base}/register`}>Creer un compte</a>
    </p>
  </form>
</section>

<style>
  .auth-shell {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.9fr);
    gap: 1.2rem;
    align-items: start;
    margin-top: 1rem;
  }

  .intro,
  .auth-card {
    padding: 1.5rem;
    border-radius: 1.8rem;
    background: rgba(255, 252, 248, 0.88);
    border: 1px solid rgba(139, 95, 61, 0.12);
    box-shadow: 0 20px 60px rgba(88, 54, 30, 0.08);
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

  p {
    margin: 1rem 0 0;
    line-height: 1.75;
    color: #5f5146;
  }

  .auth-card {
    display: grid;
    gap: 1rem;
  }

  label {
    display: grid;
    gap: 0.45rem;
  }

  span {
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #866854;
    font-weight: 700;
  }

  input {
    min-height: 3rem;
    padding: 0.85rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(143, 108, 82, 0.22);
    background: #fffdf9;
    color: #291d16;
    font: inherit;
  }

  .primary {
    min-height: 3rem;
    padding: 0.8rem 1rem;
    border: 0;
    border-radius: 999px;
    background: #8d5430;
    color: #fff9f1;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
  }

  .primary:disabled {
    opacity: 0.7;
    cursor: wait;
  }

  .error {
    margin: 0;
    padding: 0.9rem 1rem;
    border-radius: 1rem;
    background: rgba(255, 244, 241, 0.92);
    color: #9c2f20;
    border: 1px solid rgba(156, 47, 32, 0.16);
  }

  .hint {
    margin: 0;
  }

  .hint a {
    color: #8d5430;
    font-weight: 700;
  }

  @media (max-width: 860px) {
    .auth-shell {
      grid-template-columns: 1fr;
    }
  }
</style>
