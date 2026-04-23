<script>
  import { base } from '$app/paths';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { onDestroy } from 'svelte';
  import ExperienceCard from '$lib/components/ExperienceCard.svelte';
  import { fetchExperiences } from '$lib/api/client';

  /** @type {any[]} */
  let experiences = [];
  let isLoading = true;
  let error = '';
  let total = 0;

  let location = '';
  let maxPrice = '';
  let date = '';

  let currentSearch = '';

  const unsubscribe = page.subscribe(($page) => {
    const nextSearch = $page.url.searchParams.toString();

    if (nextSearch === currentSearch) {
      return;
    }

    currentSearch = nextSearch;
    syncFormWithUrl($page.url.searchParams);
    void loadExperiences();
  });

  onDestroy(unsubscribe);

  /**
   * @param {URLSearchParams} searchParams
   */
  function syncFormWithUrl(searchParams) {
    location = searchParams.get('location') || '';
    maxPrice = searchParams.get('maxPrice') || searchParams.get('price') || '';
    date = searchParams.get('date') || '';
  }

  async function loadExperiences() {
    isLoading = true;
    error = '';

    try {
      const response = await fetchExperiences({
        location,
        maxPrice,
        date
      });

      experiences = Array.isArray(response.data) ? response.data : [];
      /** @type {{ total?: unknown } | null} */
      const meta = response.meta && typeof response.meta === 'object' ? response.meta : null;
      total = meta && typeof meta.total === 'number' ? meta.total : experiences.length;
    } catch (exception) {
      experiences = [];
      total = 0;
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      isLoading = false;
    }
  }

  async function applyFilters() {
    const params = new URLSearchParams();

    if (location.trim()) {
      params.set('location', location.trim());
    }

    if (maxPrice.trim()) {
      params.set('maxPrice', maxPrice.trim());
    }

    if (date.trim()) {
      params.set('date', date.trim());
    }

    const query = params.toString();

    await goto(`${base}/experiences${query ? `?${query}` : ''}`, {
      replaceState: true,
      keepFocus: true,
      noScroll: true
    });
  }

  async function resetFilters() {
    location = '';
    maxPrice = '';
    date = '';

    await goto(`${base}/experiences`, {
      replaceState: true,
      keepFocus: true,
      noScroll: true
    });
  }
</script>

<svelte:head>
  <title>MyExperiences | Experiences</title>
</svelte:head>

<section class="intro">
  <div>
    <span class="eyebrow">Catalogue public</span>
    <h1>Trouver une experience par ville, budget ou date.</h1>
    <p>
      Cette page consomme directement l'API publique experiences. On peut deja parcourir l'offre,
      ouvrir un detail et se projeter sur les prochains modules de reservation.
    </p>
  </div>
  <a class="back-link" href={`${base}/`}>Retour a l'accueil</a>
</section>

<section class="filters-panel">
  <form class="filters-form" on:submit|preventDefault={applyFilters}>
    <label>
      <span>Lieu</span>
      <input bind:value={location} name="location" placeholder="Paris, Lyon, Bordeaux..." />
    </label>

    <label>
      <span>Prix max</span>
      <input bind:value={maxPrice} min="0" name="maxPrice" placeholder="120" type="number" />
    </label>

    <label>
      <span>Date</span>
      <input bind:value={date} name="date" type="date" />
    </label>

    <div class="actions">
      <button class="primary" type="submit">Appliquer</button>
      <button class="secondary" on:click|preventDefault={resetFilters} type="button">Reinitialiser</button>
    </div>
  </form>
</section>

<section class="results-head">
  <div>
    <span class="eyebrow">Resultats</span>
    <h2>{total} experience{total > 1 ? 's' : ''}</h2>
  </div>
</section>

{#if isLoading}
  <section class="status-panel">Chargement des experiences...</section>
{:else if error}
  <section class="status-panel error">{error}</section>
{:else if experiences.length === 0}
  <section class="status-panel">Aucune experience ne correspond a ces filtres.</section>
{:else}
  <section class="grid">
    {#each experiences as experience (experience.id)}
      <ExperienceCard {experience} />
    {/each}
  </section>
{/if}

<style>
  .intro,
  .filters-panel {
    margin-bottom: 1.3rem;
    padding: 1.35rem;
    border-radius: 1.8rem;
    background: rgba(255, 252, 248, 0.86);
    border: 1px solid rgba(139, 95, 61, 0.12);
    box-shadow: 0 20px 60px rgba(88, 54, 30, 0.08);
  }

  .intro {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: end;
    margin-top: 1rem;
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

  h1,
  h2 {
    margin: 0;
    font-family: Georgia, 'Times New Roman', serif;
    color: #24160e;
  }

  h1 {
    font-size: clamp(2.2rem, 5vw, 4rem);
    line-height: 1.04;
    max-width: 12ch;
  }

  h2 {
    font-size: clamp(1.6rem, 3vw, 2.2rem);
  }

  p {
    max-width: 60ch;
    margin: 1rem 0 0;
    line-height: 1.75;
    color: #5f5146;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    min-height: 3rem;
    padding: 0.8rem 1rem;
    border-radius: 999px;
    text-decoration: none;
    background: rgba(245, 233, 221, 0.9);
    color: #7a5337;
    font-weight: 700;
  }

  .filters-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
    align-items: end;
  }

  label {
    display: grid;
    gap: 0.45rem;
  }

  label span {
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

  .actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
  }

  button {
    min-height: 3rem;
    padding: 0.8rem 1rem;
    border: 0;
    border-radius: 999px;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
  }

  .primary {
    background: #8d5430;
    color: #fff9f1;
  }

  .secondary {
    background: rgba(240, 229, 219, 0.95);
    color: #6d5341;
  }

  .results-head {
    margin-bottom: 1rem;
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

  @media (max-width: 900px) {
    .intro {
      flex-direction: column;
      align-items: start;
    }

    .filters-form {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 640px) {
    .filters-form {
      grid-template-columns: 1fr;
    }
  }
</style>
