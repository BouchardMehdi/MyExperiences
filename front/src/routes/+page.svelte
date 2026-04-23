<script>
  import { base } from '$app/paths';
  import { onMount } from 'svelte';
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
</script>

<svelte:head>
  <title>MyExperiences | Accueil</title>
</svelte:head>

<section class="hero">
  <div class="hero-copy">
    <span class="eyebrow">Selection publique</span>
    <h1>Des experiences pensees pour etre reservees simplement, sans friction.</h1>
    <p>
      MyExperiences rassemble des ateliers, sorties et formats premium dans une interface
      claire. On commence ici par la vitrine publique avant d'ajouter l'authentification et la
      reservation complete.
    </p>

    <div class="hero-actions">
      <a class="primary" href={`${base}/experiences`}>Explorer les experiences</a>
      <a class="secondary" href={`${base}/register`}>Creer un compte</a>
    </div>
  </div>

  <aside class="hero-panel">
    <div>
      <strong>Ce qu'on a deja</strong>
      <p>API experiences publique, auth Bearer, detail riche et navigation front sous sous-chemin.</p>
    </div>
    <div>
      <strong>Ce qui vient ensuite</strong>
      <p>Reservation transactionnelle, paiement mock et espace utilisateur enrichi.</p>
    </div>
  </aside>
</section>

<section class="section-head">
  <div>
    <span class="eyebrow">A la une</span>
    <h2>Experiences publiees</h2>
  </div>
  <a class="section-link" href={`${base}/experiences`}>Tout voir</a>
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

<section class="footer-banner">
  <div>
    <span class="eyebrow">Navigation</span>
    <h2>Une base propre pour la suite du produit.</h2>
    <p>
      On a maintenant une home qui raconte le produit et une API experiences exploitable par le
      front. La prochaine couche pourra se brancher sans refaire cette structure.
    </p>
  </div>
  <a class="primary" href={`${base}/experiences`}>Ouvrir la liste complete</a>
</section>

<style>
  .hero,
  .footer-banner {
    display: grid;
    grid-template-columns: minmax(0, 2.1fr) minmax(280px, 1fr);
    gap: 1.2rem;
    align-items: stretch;
  }

  .hero {
    margin-top: 1rem;
    margin-bottom: 2.1rem;
  }

  .hero-copy,
  .hero-panel,
  .footer-banner {
    border-radius: 2rem;
    border: 1px solid rgba(144, 95, 60, 0.12);
    box-shadow: 0 25px 70px rgba(89, 56, 31, 0.08);
  }

  .hero-copy {
    padding: clamp(1.6rem, 4vw, 3rem);
    background:
      linear-gradient(135deg, rgba(112, 59, 25, 0.95), rgba(175, 95, 53, 0.88)),
      #8e4f2c;
    color: #fff8f2;
  }

  .hero-panel {
    display: grid;
    gap: 1rem;
    padding: 1.4rem;
    background: rgba(255, 250, 245, 0.84);
  }

  .hero-panel div,
  .footer-banner div {
    padding: 1rem 1.1rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.7);
  }

  .footer-banner {
    margin-top: 2.2rem;
    padding: 1.2rem;
    background: linear-gradient(180deg, rgba(236, 245, 240, 0.9), rgba(255, 251, 247, 0.88));
  }

  .eyebrow {
    display: inline-block;
    margin-bottom: 0.8rem;
    padding: 0.42rem 0.8rem;
    border-radius: 999px;
    background: rgba(255, 248, 239, 0.16);
    color: inherit;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    font-weight: 700;
  }

  .section-head {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  h1,
  h2 {
    margin: 0;
    font-family: Georgia, 'Times New Roman', serif;
    line-height: 1.04;
  }

  h1 {
    max-width: 13ch;
    font-size: clamp(2.6rem, 7vw, 5rem);
  }

  h2 {
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    color: #24160e;
  }

  p,
  .hero-panel p {
    margin: 0;
    line-height: 1.75;
    color: inherit;
  }

  .hero-copy p {
    max-width: 60ch;
    margin-top: 1rem;
    color: rgba(255, 247, 240, 0.88);
    font-size: 1.05rem;
  }

  .hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.85rem;
    margin-top: 1.5rem;
  }

  .primary,
  .secondary,
  .section-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 3rem;
    padding: 0.8rem 1.1rem;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 700;
  }

  .primary {
    background: #fff8f1;
    color: #7b4525;
  }

  .secondary,
  .section-link {
    background: rgba(255, 248, 239, 0.72);
    color: #7a5337;
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
  }

  .status-panel {
    padding: 1.35rem;
    border-radius: 1.25rem;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(120, 90, 66, 0.12);
    color: #5a473a;
  }

  .status-panel.error {
    color: #9c2f20;
    border-color: rgba(156, 47, 32, 0.16);
    background: rgba(255, 244, 241, 0.92);
  }

  @media (max-width: 860px) {
    .hero,
    .footer-banner {
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
