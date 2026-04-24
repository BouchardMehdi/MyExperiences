<script>
  import { base } from '$app/paths';
  import { onMount } from 'svelte';
  import { authSession } from '$lib/auth/session';
  import ExperienceCard from '$lib/components/ExperienceCard.svelte';
  import { fetchExperiences } from '$lib/api/client';

  /** @type {any[]} */
  let experiences = [];
  let isLoading = true;
  let error = '';

  onMount(async () => {
    try {
      const response = await fetchExperiences();
      experiences = Array.isArray(response.data) ? response.data.slice(0, 3) : [];
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      isLoading = false;
    }
  });

  $: isLoggedIn = !!$authSession.user;
  $: primaryHref = isLoggedIn ? `${base}/space` : `${base}/experiences`;
  $: primaryLabel = isLoggedIn ? 'Ouvrir mon espace' : 'Explorer les experiences';
  $: secondaryHref = isLoggedIn ? `${base}/account` : `${base}/register`;
  $: secondaryLabel = isLoggedIn ? 'Voir mon compte' : 'Creer un compte';
</script>

<svelte:head>
  <title>MyExperiences | Experiences a vivre</title>
</svelte:head>

<section class="hero">
  <div class="hero-copy">
    <span class="eyebrow">Collection d experiences</span>
    <h1>Des sorties, ateliers et moments rares a reserver sans friction.</h1>
    <p>
      MyExperiences aide a trouver une activite qui donne envie de sortir de chez soi, puis a la
      reserver dans un parcours simple. On met en avant des formats humains, des creneaux clairs
      et une experience fluide du premier clic jusqu au jour J.
    </p>

    <div class="hero-actions">
      <a class="primary" href={primaryHref}>{primaryLabel}</a>
      <a class="secondary" href={secondaryHref}>{secondaryLabel}</a>
      {#if !isLoggedIn}
        <a class="tertiary" href={`${base}/login`}>Se connecter</a>
      {/if}
    </div>
  </div>

  <aside class="hero-panel">
    <article>
      <strong>Ce que l on propose</strong>
      <p>Des experiences publiees avec prix, disponibilites, avis et reservation en ligne.</p>
    </article>
    <article>
      <strong>Pour qui</strong>
      <p>Des curieux, des voyageurs de proximite et des organisateurs qui veulent remplir leurs creneaux.</p>
    </article>
    <article>
      <strong>Pourquoi maintenant</strong>
      <p>Parce qu une bonne idee sortie doit se reserver aussi facilement qu elle se raconte.</p>
    </article>
  </aside>
</section>

<section class="promise-grid">
  <article class="promise-card">
    <span class="eyebrow soft">Simple</span>
    <h2>Une vitrine claire pour choisir vite</h2>
    <p>Lieu, prix, date, description, avis et prochains creneaux sont lisibles sans parcourir dix pages.</p>
  </article>

  <article class="promise-card highlight">
    <span class="eyebrow soft">Fiable</span>
    <h2>Un parcours complet de la reservation au paiement</h2>
    <p>Compte personnel, suivi des reservations, paiements mock et futur espace organisateur avancent sur la meme base.</p>
  </article>

  <article class="promise-card">
    <span class="eyebrow soft">Humain</span>
    <h2>Des formats qui donnent envie de sortir pour de vrai</h2>
    <p>Ateliers, degustations, balades et experiences intimistes porteuses de souvenirs, pas juste de remplissage.</p>
  </article>
</section>

<section class="section-head">
  <div>
    <span class="eyebrow">A decouvrir</span>
    <h2>Experiences mises en avant</h2>
  </div>
  <a class="section-link" href={`${base}/experiences`}>Voir tout le catalogue</a>
</section>

{#if isLoading}
  <section class="status-panel">Chargement des experiences...</section>
{:else if error}
  <section class="status-panel error">{error}</section>
{:else if experiences.length === 0}
  <section class="status-panel">Aucune experience publiee pour le moment.</section>
{:else}
  <section class="grid">
    {#each experiences as experience (experience.id)}
      <ExperienceCard {experience} />
    {/each}
  </section>
{/if}

<section class="cta-banner">
  <div>
    <span class="eyebrow">Passer a l action</span>
    <h2>Choisis une experience maintenant, complete ton compte ensuite.</h2>
    <p>
      La page catalogue reste le meilleur point d entree pour tester le produit, filtrer les propositions
      et ouvrir les details avant de reserver.
    </p>
  </div>

  <div class="cta-actions">
    <a class="primary" href={`${base}/experiences`}>Aller aux experiences</a>
    {#if isLoggedIn}
      <a class="secondary" href={`${base}/space`}>Retrouver mes reservations</a>
    {:else}
      <a class="secondary" href={`${base}/register`}>Creer mon compte</a>
    {/if}
  </div>
</section>

<style>
  .hero {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(320px, 1fr);
    gap: 1rem;
    margin-top: 1rem;
  }

  .hero-copy,
  .hero-panel,
  .promise-card,
  .cta-banner,
  .status-panel {
    border-radius: 2rem;
    border: 1px solid rgba(112, 71, 45, 0.12);
    box-shadow: 0 24px 70px rgba(66, 40, 19, 0.08);
  }

  .hero-copy {
    padding: clamp(1.8rem, 4vw, 3.3rem);
    background:
      radial-gradient(circle at top right, rgba(255, 210, 175, 0.24), transparent 32%),
      linear-gradient(140deg, #4d2f23 0%, #8f4e2e 52%, #cc7a45 100%);
    color: #fff8f2;
  }

  .hero-panel {
    display: grid;
    gap: 0.85rem;
    padding: 1rem;
    background: rgba(255, 251, 246, 0.82);
    backdrop-filter: blur(12px);
  }

  .hero-panel article,
  .cta-banner div {
    padding: 1rem 1.1rem;
    border-radius: 1.3rem;
    background: rgba(255, 255, 255, 0.72);
  }

  .hero-panel strong {
    display: block;
    margin-bottom: 0.4rem;
    color: #2d1b13;
  }

  .promise-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
    margin: 1.2rem 0 2rem;
  }

  .promise-card {
    padding: 1.35rem;
    background: rgba(255, 251, 246, 0.84);
  }

  .promise-card.highlight {
    background:
      linear-gradient(180deg, rgba(236, 247, 241, 0.95), rgba(255, 251, 246, 0.88));
  }

  .section-head {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .eyebrow {
    display: inline-flex;
    align-items: center;
    min-height: 2rem;
    margin-bottom: 0.8rem;
    padding: 0.38rem 0.82rem;
    border-radius: 999px;
    background: rgba(255, 243, 231, 0.14);
    color: inherit;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-weight: 700;
  }

  .eyebrow.soft {
    background: rgba(228, 199, 174, 0.32);
    color: #905f3f;
  }

  h1,
  h2 {
    margin: 0;
    font-family: 'Constantia', Georgia, serif;
    line-height: 1.02;
  }

  h1 {
    max-width: 12ch;
    font-size: clamp(2.8rem, 7vw, 5.2rem);
  }

  h2 {
    font-size: clamp(1.8rem, 3vw, 2.6rem);
    color: #231710;
  }

  p {
    margin: 0;
    line-height: 1.72;
    color: inherit;
  }

  .hero-copy p {
    max-width: 60ch;
    margin-top: 1rem;
    color: rgba(255, 247, 240, 0.88);
    font-size: 1.03rem;
  }

  .hero-actions,
  .cta-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem;
    margin-top: 1.5rem;
  }

  .primary,
  .secondary,
  .tertiary,
  .section-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 3rem;
    padding: 0.8rem 1.1rem;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 700;
    transition:
      transform 180ms ease,
      box-shadow 180ms ease,
      background 180ms ease;
  }

  .primary:hover,
  .secondary:hover,
  .tertiary:hover,
  .section-link:hover {
    transform: translateY(-1px);
  }

  .primary {
    background: #fff7ee;
    color: #8a4326;
    box-shadow: 0 16px 34px rgba(44, 21, 9, 0.18);
  }

  .secondary,
  .section-link {
    background: rgba(255, 245, 235, 0.72);
    color: #734d36;
  }

  .tertiary {
    border: 1px solid rgba(255, 247, 240, 0.22);
    color: #fff7ef;
    background: transparent;
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
    gap: 1rem;
    align-items: stretch;
  }

  .status-panel {
    padding: 1.35rem;
    background: rgba(255, 251, 246, 0.84);
    color: #5a473a;
  }

  .status-panel.error {
    color: #9c2f20;
    background: rgba(255, 244, 241, 0.92);
    border-color: rgba(156, 47, 32, 0.16);
  }

  .cta-banner {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(260px, 1fr);
    gap: 1rem;
    margin-top: 2rem;
    padding: 1rem;
    background: linear-gradient(180deg, rgba(237, 246, 241, 0.92), rgba(255, 251, 246, 0.88));
  }

  @media (max-width: 980px) {
    .hero,
    .promise-grid,
    .cta-banner {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 640px) {
    .section-head {
      align-items: start;
      flex-direction: column;
    }
  }
</style>
