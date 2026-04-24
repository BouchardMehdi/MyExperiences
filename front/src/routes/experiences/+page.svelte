<script>
  import { base } from '$app/paths';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { onDestroy } from 'svelte';
  import { authSession } from '$lib/auth/session';
  import ExperienceCard from '$lib/components/ExperienceCard.svelte';
  import ExperienceMap from '$lib/components/ExperienceMap.svelte';
  import { fetchExperiences } from '$lib/api/client';
  import { calculateDistanceKm } from '$lib/utils/experience';

  /** @type {any[]} */
  let experiences = [];
  let isLoading = true;
  let error = '';
  let total = 0;

  let location = '';
  let maxPrice = '';
  let date = '';
  let isLocating = false;
  let geolocationError = '';
  /** @type {{ latitude: number; longitude: number } | null} */
  let userLocation = null;
  /** @type {number | null} */
  let selectedExperienceId = null;

  /** @type {string | null} */
  let currentSearch = null;

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

  async function useMyLocation() {
    if (!navigator.geolocation) {
      geolocationError = 'La geolocalisation n est pas disponible sur cet appareil.';
      return;
    }

    isLocating = true;
    geolocationError = '';

    navigator.geolocation.getCurrentPosition(
      (position) => {
        userLocation = {
          latitude: position.coords.latitude,
          longitude: position.coords.longitude
        };
        isLocating = false;
      },
      () => {
        geolocationError = 'Impossible de recuperer votre position. Verifiez les permissions du navigateur.';
        isLocating = false;
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000
      }
    );
  }

  function clearMyLocation() {
    userLocation = null;
    geolocationError = '';
  }

  /**
   * @param {CustomEvent<{ id: number }>} event
   */
  function handleMapSelect(event) {
    selectedExperienceId = Number(event.detail.id);

    if (typeof document === 'undefined') {
      return;
    }

    const target = document.getElementById(`experience-card-${selectedExperienceId}`);
    target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  /**
   * @param {any} experience
   * @returns {number | null}
   */
  function getExperienceDistance(experience) {
    return calculateDistanceKm(userLocation, experience?.coordinates || null);
  }

  $: activeFilterCount = [location, maxPrice, date].filter((value) => String(value || '').trim()).length;
  $: isFeaturedMode = activeFilterCount === 0;
  $: baseDisplayedExperiences = isFeaturedMode ? experiences.slice(0, 9) : experiences;
  $: displayedExperiences = [...baseDisplayedExperiences]
    .map((experience) => {
      const distanceKm = getExperienceDistance(experience);
      return distanceKm == null ? experience : { ...experience, distanceKm };
    })
    .sort((left, right) => {
      const leftDistance = typeof left.distanceKm === 'number' ? left.distanceKm : Number.POSITIVE_INFINITY;
      const rightDistance = typeof right.distanceKm === 'number' ? right.distanceKm : Number.POSITIVE_INFINITY;
      return leftDistance - rightDistance;
    });
  $: displayedTotal = displayedExperiences.length;
  $: mappedExperiences = displayedExperiences.filter(
    (experience) =>
      experience?.coordinates &&
      Number.isFinite(Number(experience.coordinates.latitude)) &&
      Number.isFinite(Number(experience.coordinates.longitude))
  );
  $: if (
    mappedExperiences.length > 0 &&
    !mappedExperiences.some((experience) => Number(experience.id) === Number(selectedExperienceId))
  ) {
    selectedExperienceId = Number(mappedExperiences[0].id);
  }
  $: if (mappedExperiences.length === 0) {
    selectedExperienceId = null;
  }
  $: ctaHref = $authSession.user ? `${base}/space` : `${base}/register`;
  $: ctaLabel = $authSession.user ? 'Ouvrir mon espace' : 'Creer un compte';
</script>

<svelte:head>
  <title>MyExperiences | Catalogue experiences</title>
</svelte:head>

<section class="intro-panel">
  <div class="intro-copy">
    <span class="eyebrow">Catalogue</span>
    <h1>Choisir une experience devient simple.</h1>
    <p>
      Parcours les formats mis en avant, puis affine par lieu, budget ou date si tu sais deja ce
      que tu cherches.
    </p>
  </div>

  <div class="intro-actions">
    <a class="ghost-link" href={`${base}/`}>Retour a l accueil</a>
    <a class="solid-link" href={ctaHref}>{ctaLabel}</a>
  </div>
</section>

<section class="filters-shell">
  <div class="filters-head">
    <div>
      <span class="eyebrow soft">Recherche</span>
      <h2>Filtrer les experiences</h2>
    </div>

    {#if activeFilterCount > 0}
      <p>{activeFilterCount} filtre{activeFilterCount > 1 ? 's' : ''} actif{activeFilterCount > 1 ? 's' : ''}</p>
    {:else}
      <p>Sans filtre, on affiche une selection a la une.</p>
    {/if}
  </div>

  <form class="filters-form" on:submit|preventDefault={applyFilters}>
    <label>
      <span>Ville ou lieu</span>
      <input bind:value={location} name="location" placeholder="Paris, Lyon, Bordeaux..." />
    </label>

    <label>
      <span>Budget max</span>
      <input bind:value={maxPrice} min="0" name="maxPrice" placeholder="120" type="number" />
    </label>

    <label>
      <span>Date</span>
      <input bind:value={date} name="date" type="date" />
    </label>

    <div class="actions">
      <button class="primary" type="submit">Appliquer</button>
      <button class="secondary" on:click|preventDefault={resetFilters} type="button">Effacer</button>
    </div>
  </form>
</section>

<section class="results-head">
  <div>
    <span class="eyebrow soft">{isFeaturedMode ? 'A la une' : 'Resultats'}</span>
    <h2>{displayedTotal} experience{displayedTotal > 1 ? 's' : ''}</h2>
  </div>

  <p>
    {#if isFeaturedMode}
      Jusqu a 9 experiences pour decouvrir rapidement le meilleur du catalogue.
    {:else}
      Des experiences qui correspondent a tes criteres actuels.
    {/if}
  </p>
</section>

<section class="map-section">
  <div class="map-head">
    <div>
      <span class="eyebrow soft">Carte</span>
      <h2>Voir les experiences sur la carte</h2>
      <p>
        {#if userLocation}
          La liste se base maintenant aussi sur votre position approximative pour remonter les plus proches.
        {:else}
          Activez votre position pour mieux reperer les experiences autour de vous.
        {/if}
      </p>
    </div>

    <div class="map-actions">
      <button class="primary" disabled={isLocating} on:click={useMyLocation} type="button">
        {isLocating ? 'Localisation...' : 'Utiliser ma position'}
      </button>
      {#if userLocation}
        <button class="secondary" on:click={clearMyLocation} type="button">Retirer ma position</button>
      {/if}
    </div>
  </div>

  {#if geolocationError}
    <p class="map-note error-note">{geolocationError}</p>
  {/if}

  <div class="map-layout">
    <div class="map-panel">
      {#if mappedExperiences.length > 0}
        <ExperienceMap
          experiences={mappedExperiences}
          {userLocation}
          {selectedExperienceId}
          on:select={handleMapSelect}
        />
      {:else}
        <div class="map-empty">
          Aucune experience de cette selection ne possede encore de coordonnees exploitables.
        </div>
      {/if}
    </div>

    <aside class="map-aside">
      <article>
        <strong>{mappedExperiences.length}</strong>
        <span>experience{mappedExperiences.length > 1 ? 's' : ''} sur la carte</span>
      </article>
      <article>
        <strong>{userLocation ? 'Actif' : 'Inactif'}</strong>
        <span>mode proximite</span>
      </article>
      <article>
        <strong>{selectedExperienceId || 'Aucune'}</strong>
        <span>experience ciblee</span>
      </article>
    </aside>
  </div>
</section>

{#if isLoading}
  <section class="status-panel">Chargement des experiences...</section>
{:else if error}
  <section class="status-panel error">{error}</section>
{:else if displayedExperiences.length === 0}
  <section class="status-panel">Aucune experience ne correspond a ces filtres.</section>
{:else}
  <section class="grid">
    {#each displayedExperiences as experience (experience.id)}
      <div
        id={`experience-card-${experience.id}`}
        role="presentation"
        class:selected-card={selectedExperienceId === Number(experience.id)}
        on:mouseenter={() => (selectedExperienceId = Number(experience.id))}
        on:focusin={() => (selectedExperienceId = Number(experience.id))}
      >
        <ExperienceCard {experience} />
      </div>
    {/each}
  </section>
{/if}

<style>
  .intro-panel,
  .filters-shell,
  .map-section,
  .results-head,
  .status-panel {
    border-radius: 1.9rem;
    border: 1px solid rgba(112, 71, 45, 0.12);
    background: rgba(255, 251, 246, 0.84);
    box-shadow: 0 22px 60px rgba(66, 40, 19, 0.08);
    backdrop-filter: blur(12px);
  }

  .intro-panel {
    display: flex;
    justify-content: space-between;
    gap: 1.5rem;
    align-items: center;
    margin: 1rem 0 1.1rem;
    padding: 1.5rem;
  }

  .intro-copy {
    max-width: 45rem;
  }

  .intro-actions {
    display: flex;
    gap: 0.8rem;
    flex-wrap: wrap;
    align-items: center;
    align-self: center;
  }

  .filters-shell,
  .map-section,
  .results-head,
  .status-panel {
    padding: 1.25rem;
  }

  .filters-head,
  .results-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: end;
  }

  .eyebrow {
    display: inline-flex;
    align-items: center;
    min-height: 2rem;
    margin-bottom: 0.75rem;
    padding: 0.38rem 0.8rem;
    border-radius: 999px;
    background: rgba(235, 203, 178, 0.28);
    color: #8a5b3b;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 700;
  }

  .eyebrow.soft {
    background: rgba(225, 210, 194, 0.38);
    color: #7b604d;
  }

  h1,
  h2 {
    margin: 0;
    font-family: 'Constantia', Georgia, serif;
    color: #24160e;
  }

  h1 {
    font-size: clamp(2.1rem, 4vw, 3.6rem);
    line-height: 1.03;
    max-width: 12ch;
  }

  h2 {
    font-size: clamp(1.65rem, 3vw, 2.25rem);
  }

  p {
    margin: 0;
    line-height: 1.72;
    color: #5f5146;
  }

  .intro-copy p {
    margin-top: 0.9rem;
    max-width: 56ch;
  }

  .ghost-link,
  .solid-link,
  .primary,
  .secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 3rem;
    padding: 0.8rem 1rem;
    border-radius: 999px;
    text-decoration: none;
    border: 0;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
    align-self: start;
  }

  .ghost-link,
  .secondary {
    background: rgba(243, 230, 217, 0.92);
    color: #734d36;
  }

  .solid-link,
  .primary {
    background: #8d5430;
    color: #fff9f1;
  }

  .filters-form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.9rem;
    align-items: end;
    margin-top: 1rem;
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

  .results-head {
    margin: 1.1rem 0 1rem;
  }

  .map-section {
    display: grid;
    gap: 1rem;
    margin-bottom: 1rem;
  }

  .map-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: end;
  }

  .map-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
  }

  .map-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(16rem, 0.75fr);
    gap: 1rem;
    align-items: stretch;
  }

  .map-panel {
    min-height: 28rem;
    border-radius: 1.6rem;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .map-aside {
    display: grid;
    gap: 0.85rem;
  }

  .map-aside article,
  .map-empty {
    display: grid;
    gap: 0.35rem;
    padding: 1rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .map-aside strong {
    font-size: 1.5rem;
    color: #24160e;
  }

  .map-aside span {
    color: #64564b;
    line-height: 1.5;
  }

  .map-note {
    margin: 0;
    color: #5f5146;
  }

  .error-note {
    color: #9c2f20;
  }

  .selected-card {
    border-radius: 1.7rem;
    box-shadow: 0 0 0 2px rgba(157, 92, 49, 0.18);
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
    gap: 1rem;
    align-items: stretch;
  }

  .status-panel {
    color: #5a473a;
  }

  .status-panel.error {
    color: #9c2f20;
    background: rgba(255, 244, 241, 0.92);
    border-color: rgba(156, 47, 32, 0.16);
  }

  @media (max-width: 920px) {
    .intro-panel,
    .filters-head,
    .results-head,
    .map-head {
      flex-direction: column;
      align-items: start;
    }

    .filters-form {
      grid-template-columns: 1fr 1fr;
    }

    .map-layout {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 640px) {
    .filters-form {
      grid-template-columns: 1fr;
    }
  }
</style>
