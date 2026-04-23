<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { browser } from '$app/environment';
  import { page } from '$app/stores';
  import { authSession, getStoredAuthToken } from '$lib/auth/session';
  import { createBooking, fetchExperienceById } from '$lib/api/client';
  import { formatDateTime, formatDuration, formatPrice } from '$lib/utils/experience';

  /** @type {any} */
  let experience = null;
  let isLoading = true;
  let error = '';
  let experienceId = '';
  let currentId = '';
  let selectedSlotId = '';
  let seats = 1;
  let bookingError = '';
  let bookingSuccess = '';
  let isSubmittingBooking = false;

  $: experienceId = $page.params.id ?? '';
  $: authenticatedUser = /** @type {Record<string, any> | null} */ ($authSession.user);

  $: if (browser && experienceId && experienceId !== currentId) {
    currentId = experienceId;
    void loadExperience(experienceId);
  }

  /**
   * @param {string} id
   */
  async function loadExperience(id) {
    isLoading = true;
    error = '';
    experience = null;
    bookingError = '';
    bookingSuccess = '';

    try {
      const response = await fetchExperienceById(id);
      experience = response.data || null;
      selectedSlotId = experience?.slots?.[0]?.id ? String(experience.slots[0].id) : '';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      isLoading = false;
    }
  }

  async function submitBooking() {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    if (!selectedSlotId) {
      bookingError = 'Veuillez choisir un creneau.';
      return;
    }

    isSubmittingBooking = true;
    bookingError = '';
    bookingSuccess = '';

    try {
      await createBooking(token, {
        slotId: Number(selectedSlotId),
        seats
      });

      bookingSuccess = 'Reservation creee. Vous pouvez la retrouver dans votre compte.';
      await loadExperience(experienceId);
      await goto(`${base}/account`, { keepFocus: true, noScroll: true });
    } catch (exception) {
      bookingError = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      isSubmittingBooking = false;
    }
  }
</script>

<svelte:head>
  <title>{experience?.title ? `${experience.title} | MyExperiences` : 'MyExperiences | Detail experience'}</title>
</svelte:head>

<nav class="breadcrumb">
  <a href={`${base}/`}>Accueil</a>
  <span>/</span>
  <a href={`${base}/experiences`}>Experiences</a>
  {#if experience?.title}
    <span>/</span>
    <span>{experience.title}</span>
  {/if}
</nav>

{#if isLoading}
  <section class="status-panel">Chargement de l'experience...</section>
{:else if error}
  <section class="status-panel error">{error}</section>
{:else if !experience}
  <section class="status-panel">Experience introuvable.</section>
{:else}
  <section class="hero">
    <div class="hero-copy">
      <span class="eyebrow">{experience.location}</span>
      <h1>{experience.title}</h1>
      <p>{experience.description}</p>

      <div class="chips">
        <span>{formatPrice(experience.price)}</span>
        <span>{formatDuration(experience.durationMinutes)}</span>
        <span>{experience.booking?.availableSlotsCount} creneaux disponibles</span>
        {#if experience.reviewSummary?.count}
          <span>{experience.reviewSummary.averageRating}/5 - {experience.reviewSummary.count} avis</span>
        {/if}
      </div>
    </div>

    <aside class="booking-panel">
      <strong>Reservation</strong>

      {#if !authenticatedUser}
        <p>Connectez-vous pour reserver un creneau disponible.</p>
        <a class="primary-link" href={`${base}/login`}>Se connecter</a>
      {:else if experience.booking?.isBookable && experience.slots?.length}
        <p>Choisissez un creneau et le nombre de places a reserver.</p>

        <form class="booking-form" on:submit|preventDefault={submitBooking}>
          <label>
            <span>Creneau</span>
            <select bind:value={selectedSlotId}>
              {#each experience.slots as slot (slot.id)}
                <option value={slot.id}>
                  {formatDateTime(slot.startAt)} - {slot.remainingPlaces} places restantes
                </option>
              {/each}
            </select>
          </label>

          <label>
            <span>Places</span>
            <input bind:value={seats} min="1" type="number" />
          </label>

          {#if bookingError}
            <p class="inline-error">{bookingError}</p>
          {/if}

          {#if bookingSuccess}
            <p class="inline-success">{bookingSuccess}</p>
          {/if}

          <button class="primary-link" disabled={isSubmittingBooking} type="submit">
            {isSubmittingBooking ? 'Reservation...' : 'Reserver'}
          </button>
        </form>
      {:else}
        <p>Aucun creneau reservable pour le moment.</p>
      {/if}

      <dl>
        <div>
          <dt>Prochain depart</dt>
          <dd>{formatDateTime(experience.booking?.nextStartAt)}</dd>
        </div>
        <div>
          <dt>Statut</dt>
          <dd>{experience.status}</dd>
        </div>
      </dl>
    </aside>
  </section>

  <section class="content-grid">
    <article class="panel">
      <div class="panel-head">
        <span class="eyebrow">Creneaux</span>
        <h2>Disponibilites</h2>
      </div>

      {#if experience.slots?.length}
        <div class="slot-list">
          {#each experience.slots as slot (slot.id)}
            <article class="slot-card">
              <strong>{formatDateTime(slot.startAt)}</strong>
              <p>Fin {formatDateTime(slot.endAt)}</p>
              <div class="slot-meta">
                <span>{slot.remainingPlaces} places restantes</span>
                <span>{slot.capacity} places au total</span>
              </div>
            </article>
          {/each}
        </div>
      {:else}
        <p class="empty">Aucun creneau reservable pour l'instant.</p>
      {/if}
    </article>

    <article class="panel">
      <div class="panel-head">
        <span class="eyebrow">Avis</span>
        <h2>Derniers retours</h2>
      </div>

      {#if experience.reviews?.length}
        <div class="review-list">
          {#each experience.reviews as review (review.id)}
            <article class="review-card">
              <header>
                <strong>{review.author?.fullName || 'Participant'}</strong>
                <span>{review.rating}/5</span>
              </header>
              <p>{review.comment}</p>
              <small>{formatDateTime(review.createdAt)}</small>
            </article>
          {/each}
        </div>
      {:else}
        <p class="empty">Aucun avis publie pour le moment.</p>
      {/if}
    </article>
  </section>
{/if}

<style>
  .breadcrumb {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 1rem 0 1.2rem;
    color: #7a6555;
  }

  .breadcrumb a {
    color: inherit;
    text-decoration: none;
    font-weight: 700;
  }

  .hero,
  .panel {
    border-radius: 1.8rem;
    border: 1px solid rgba(139, 95, 61, 0.12);
    box-shadow: 0 20px 60px rgba(88, 54, 30, 0.08);
    background: rgba(255, 252, 248, 0.88);
  }

  .hero {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(280px, 0.9fr);
    gap: 1rem;
    padding: 1.35rem;
    margin-bottom: 1.3rem;
  }

  .hero-copy,
  .booking-panel {
    padding: 1.1rem;
    border-radius: 1.35rem;
  }

  .hero-copy {
    background: linear-gradient(180deg, rgba(255, 248, 239, 0.92), rgba(255, 255, 255, 0.86));
  }

  .booking-panel {
    background: linear-gradient(180deg, rgba(233, 245, 239, 0.95), rgba(255, 251, 247, 0.82));
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
    font-size: clamp(2.3rem, 5vw, 4rem);
    line-height: 1.04;
  }

  h2 {
    font-size: clamp(1.5rem, 3vw, 2.2rem);
  }

  p {
    margin: 1rem 0 0;
    line-height: 1.75;
    color: #5f5146;
  }

  .chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    margin-top: 1.4rem;
  }

  .chips span,
  .slot-meta span {
    padding: 0.6rem 0.85rem;
    border-radius: 999px;
    background: rgba(243, 231, 220, 0.9);
    color: #6e513e;
    font-weight: 700;
  }

  .booking-form {
    display: grid;
    gap: 0.8rem;
    margin-top: 1rem;
  }

  .booking-form label {
    display: grid;
    gap: 0.4rem;
  }

  .booking-form span {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #866854;
    font-weight: 700;
  }

  .booking-form select,
  .booking-form input {
    min-height: 3rem;
    padding: 0.8rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(143, 108, 82, 0.22);
    background: #fffdf9;
    color: #291d16;
    font: inherit;
  }

  .primary-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 3rem;
    padding: 0.8rem 1rem;
    border-radius: 999px;
    background: #8d5430;
    color: #fff9f1;
    text-decoration: none;
    border: 0;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
  }

  .primary-link:disabled {
    opacity: 0.7;
    cursor: wait;
  }

  .inline-error,
  .inline-success {
    margin: 0;
    padding: 0.85rem 1rem;
    border-radius: 1rem;
  }

  .inline-error {
    background: rgba(255, 244, 241, 0.92);
    color: #9c2f20;
    border: 1px solid rgba(156, 47, 32, 0.16);
  }

  .inline-success {
    background: rgba(234, 247, 239, 0.92);
    color: #1f7e5c;
    border: 1px solid rgba(31, 126, 92, 0.16);
  }

  dl {
    display: grid;
    gap: 0.9rem;
    margin: 1rem 0 0;
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

  .content-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }

  .panel {
    padding: 1.35rem;
  }

  .panel-head {
    margin-bottom: 1rem;
  }

  .slot-list,
  .review-list {
    display: grid;
    gap: 0.9rem;
  }

  .slot-card,
  .review-card {
    padding: 1rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .slot-card strong,
  .review-card strong {
    color: #24160e;
  }

  .slot-card p,
  .review-card p {
    margin-top: 0.45rem;
  }

  .slot-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
    margin-top: 0.85rem;
  }

  .review-card header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
  }

  small {
    display: inline-block;
    margin-top: 0.65rem;
    color: #816a59;
  }

  .empty,
  .status-panel {
    margin: 0;
    padding: 1.2rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.82);
    color: #5f5146;
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .status-panel.error {
    color: #9c2f20;
    border-color: rgba(156, 47, 32, 0.16);
    background: rgba(255, 244, 241, 0.92);
  }

  @media (max-width: 900px) {
    .hero,
    .content-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
