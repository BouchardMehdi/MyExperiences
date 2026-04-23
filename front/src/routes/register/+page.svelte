<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { setAuthSession } from '$lib/auth/session';
  import { registerUser } from '$lib/api/client';

  let firstname = '';
  let lastname = '';
  let email = '';
  let password = '';
  let passwordConfirmation = '';
  let error = '';
  let isSubmitting = false;

  async function submitRegistration() {
    if (password !== passwordConfirmation) {
      error = 'Les mots de passe ne correspondent pas.';
      return;
    }

    isSubmitting = true;
    error = '';

    try {
      const response = /** @type {{ token?: { value?: string }, user?: Record<string, unknown> }} */ (
        await registerUser({ firstname, lastname, email, password })
      );
      const token = response.token?.value;
      const user = response.user;

      if (typeof token !== 'string' || !user || typeof user !== 'object') {
        throw new Error("La reponse d'inscription est incomplete.");
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
  <title>MyExperiences | Inscription</title>
</svelte:head>

<section class="auth-shell">
  <div class="intro">
    <span class="eyebrow">Inscription</span>
    <h1>Creer un compte pour preparer les prochaines reservations.</h1>
    <p>
      On ouvre ici la premiere brique compte utilisateur. Une fois inscrit, le Bearer token est
      stocke cote front et reutilisable sur les futures routes protegees.
    </p>
  </div>

  <form class="auth-card" on:submit|preventDefault={submitRegistration}>
    <div class="split">
      <label>
        <span>Prenom</span>
        <input bind:value={firstname} autocomplete="given-name" placeholder="Camille" />
      </label>

      <label>
        <span>Nom</span>
        <input bind:value={lastname} autocomplete="family-name" placeholder="Martin" />
      </label>
    </div>

    <label>
      <span>Email</span>
      <input bind:value={email} autocomplete="email" placeholder="vous@example.com" type="email" />
    </label>

    <label>
      <span>Mot de passe</span>
      <input
        bind:value={password}
        autocomplete="new-password"
        placeholder="Au moins 8 caracteres"
        type="password"
      />
    </label>

    <label>
      <span>Confirmation</span>
      <input
        bind:value={passwordConfirmation}
        autocomplete="new-password"
        placeholder="Confirmer le mot de passe"
        type="password"
      />
    </label>

    {#if error}
      <p class="error">{error}</p>
    {/if}

    <button class="primary" disabled={isSubmitting} type="submit">
      {isSubmitting ? 'Creation...' : 'Creer mon compte'}
    </button>

    <p class="hint">
      Deja inscrit ? <a href={`${base}/login`}>Se connecter</a>
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

  .split {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
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

  @media (max-width: 520px) {
    .split {
      grid-template-columns: 1fr;
    }
  }
</style>
