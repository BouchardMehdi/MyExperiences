<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { onMount } from 'svelte';
  import { authSession, clearAuthSession, getStoredAuthToken, updateAuthUser } from '$lib/auth/session';
  import {
    cancelBooking,
    fetchCurrentUser,
    fetchMyBookings,
    fetchOrganizerDashboard
  } from '$lib/api/client';
  import { formatBookingStatus, formatPaymentStatus } from '$lib/utils/booking';
  import { formatDateTime, formatPrice } from '$lib/utils/experience';

  let error = '';
  let isLoading = true;
  /** @type {Record<string, any> | null} */
  let currentUser = null;
  /** @type {Array<Record<string, any>>} */
  let bookings = [];
  /** @type {Record<string, any> | null} */
  let organizerDashboard = null;
  let bookingsError = '';
  let bookingsFeedback = '';
  /** @type {number | null} */
  let cancellingBookingId = null;
  let bookingFilter = 'upcoming';
  let organizerFilter = 'upcoming';

  onMount(async () => {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    try {
      const userResponse = await fetchCurrentUser(token);
      const user =
        userResponse.data && typeof userResponse.data === 'object'
          ? /** @type {Record<string, any>} */ (userResponse.data)
          : null;

      if (!user) {
        throw new Error('La reponse utilisateur est incomplete.');
      }

      updateAuthUser(/** @type {Record<string, unknown>} */ (user));
      currentUser = user;

      const requests = [fetchMyBookings(token)];

      if (isOrganizerUser(user)) {
        requests.push(fetchOrganizerDashboard(token));
      }

      const responses = await Promise.all(requests);
      const bookingsResponse = responses[0];

      bookings = Array.isArray(bookingsResponse.data) ? bookingsResponse.data : [];

      if (responses[1]?.data && typeof responses[1].data === 'object') {
        organizerDashboard = /** @type {Record<string, any>} */ (responses[1].data);
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

  $: currentUser = /** @type {Record<string, any> | null} */ ($authSession.user ?? currentUser);
  $: sortedBookings = [...bookings].sort((left, right) => getBookingTimestamp(left) - getBookingTimestamp(right));
  $: filteredBookings = sortedBookings.filter((booking) => matchesBookingFilter(booking, bookingFilter));
  $: organizerExperiences = Array.isArray(organizerDashboard?.experiences)
    ? organizerDashboard.experiences
    : [];
  $: filteredOrganizerExperiences = organizerExperiences.filter((experience) =>
    matchesOrganizerFilter(experience, organizerFilter)
  );
  $: organizerBookings = Array.isArray(organizerDashboard?.bookings) ? organizerDashboard.bookings : [];
  $: upcomingBookingCount = sortedBookings.filter((booking) => isUpcomingBooking(booking)).length;
  $: paidBookingCount = sortedBookings.filter((booking) => booking.status === 'PAID').length;

  /**
   * @param {Record<string, any> | null | undefined} user
   */
  function isOrganizerUser(user) {
    return !!(
      user &&
      Array.isArray(user.roles) &&
      (user.roles.includes('ROLE_ORGANIZER') || user.roles.includes('ROLE_ADMIN'))
    );
  }

  /**
   * @param {Record<string, any>} booking
   */
  function getBookingTimestamp(booking) {
    const value = booking?.slot?.startAt;
    const timestamp = value ? new Date(value).getTime() : Number.MAX_SAFE_INTEGER;
    return Number.isNaN(timestamp) ? Number.MAX_SAFE_INTEGER : timestamp;
  }

  /**
   * @param {Record<string, any>} booking
   */
  function isUpcomingBooking(booking) {
    return getBookingTimestamp(booking) >= Date.now() && booking.status !== 'CANCELLED';
  }

  /**
   * @param {Record<string, any>} booking
   * @param {string} filter
   */
  function matchesBookingFilter(booking, filter) {
    const isPast = getBookingTimestamp(booking) < Date.now();

    switch (filter) {
      case 'upcoming':
        return !isPast && booking.status !== 'CANCELLED';
      case 'past':
        return isPast;
      case 'paid':
        return booking.status === 'PAID';
      case 'cancelled':
        return booking.status === 'CANCELLED';
      default:
        return true;
    }
  }

  /**
   * @param {Record<string, any>} experience
   */
  function getExperienceSlots(experience) {
    return Array.isArray(experience?.slots) ? experience.slots : [];
  }

  /**
   * @param {Record<string, any>} experience
   */
  function getUpcomingSlots(experience) {
    return getExperienceSlots(experience).filter((slot) => {
      const timestamp = slot?.startAt ? new Date(slot.startAt).getTime() : NaN;
      return !Number.isNaN(timestamp) && timestamp >= Date.now();
    });
  }

  /**
   * @param {Record<string, any>} experience
   */
  function getPastSlots(experience) {
    return getExperienceSlots(experience).filter((slot) => {
      const timestamp = slot?.startAt ? new Date(slot.startAt).getTime() : NaN;
      return !Number.isNaN(timestamp) && timestamp < Date.now();
    });
  }

  /**
   * @param {Record<string, any>} experience
   * @param {string} filter
   */
  function matchesOrganizerFilter(experience, filter) {
    const upcomingCount = getUpcomingSlots(experience).length;
    const pastCount = getPastSlots(experience).length;

    switch (filter) {
      case 'upcoming':
        return upcomingCount > 0;
      case 'past':
        return pastCount > 0 && upcomingCount === 0;
      case 'published':
        return experience.status === 'PUBLISHED';
      case 'draft':
        return experience.status === 'DRAFT';
      default:
        return true;
    }
  }

  /**
   * @param {Record<string, any>} experience
   */
  function getExperienceHeadlineDate(experience) {
    const upcomingSlot = getUpcomingSlots(experience)
      .sort((left, right) => new Date(left.startAt).getTime() - new Date(right.startAt).getTime())[0];

    if (upcomingSlot?.startAt) {
      return `Prochain creneau ${formatDateTime(upcomingSlot.startAt)}`;
    }

    const latestPastSlot = getPastSlots(experience)
      .sort((left, right) => new Date(right.startAt).getTime() - new Date(left.startAt).getTime())[0];

    if (latestPastSlot?.startAt) {
      return `Dernier creneau ${formatDateTime(latestPastSlot.startAt)}`;
    }

    return 'Aucun creneau encore programme';
  }

  /**
   * @param {number | undefined} experienceId
   */
  function getOrganizerExperienceStats(experienceId) {
    const relatedBookings = organizerBookings.filter((booking) => booking.experience?.id === experienceId);

    return {
      bookingCount: relatedBookings.length,
      paidCount: relatedBookings.filter((booking) => booking.status === 'PAID').length
    };
  }

  /**
   * @param {number} bookingId
   */
  async function handleCancelBooking(bookingId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    cancellingBookingId = bookingId;
    bookingsError = '';
    bookingsFeedback = '';

    try {
      const response = await cancelBooking(token, bookingId);
      const updatedBooking = response.data && typeof response.data === 'object' ? response.data : null;

      if (!updatedBooking) {
        throw new Error("La reponse d annulation est incomplete.");
      }

      bookings = bookings.map((booking) => (booking.id === bookingId ? updatedBooking : booking));
      bookingsFeedback = 'La reservation a bien ete annulee.';
    } catch (exception) {
      bookingsError = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      cancellingBookingId = null;
    }
  }

</script>

<svelte:head>
  <title>MyExperiences | Mon espace</title>
</svelte:head>

{#if isLoading}
  <section class="status-panel">Chargement de votre espace...</section>
{:else if error}
  <section class="status-panel error">{error}</section>
{:else if currentUser}
  <section class="space-shell">
    <div class="hero">
      <div>
        <span class="eyebrow">Mon espace</span>
        <h1>Bonjour {currentUser.firstName || 'vous'}.</h1>
        <p>
          Ici, vous retrouvez vos reservations, vos prochaines sorties et, si vous etes organisateur,
          une vue rapide de vos experiences publiees.
        </p>
      </div>

      <div class="hero-stats">
        <article class="stat-card">
          <strong>{sortedBookings.length}</strong>
          <span>reservations au total</span>
        </article>
        <article class="stat-card">
          <strong>{upcomingBookingCount}</strong>
          <span>sorties a venir</span>
        </article>
        <article class="stat-card">
          <strong>{paidBookingCount}</strong>
          <span>reservations payees</span>
        </article>
        {#if isOrganizerUser(currentUser)}
          <article class="stat-card accent">
            <strong>{organizerExperiences.length}</strong>
            <span>experiences organisees</span>
          </article>
        {/if}
      </div>
    </div>

    <section class="panel">
      <div class="panel-head">
        <div>
          <span class="eyebrow soft">Reservations</span>
          <h2>Mes experiences reservees</h2>
        </div>

        <div class="filter-row">
          <button class:active={bookingFilter === 'upcoming'} on:click={() => (bookingFilter = 'upcoming')} type="button">A venir</button>
          <button class:active={bookingFilter === 'paid'} on:click={() => (bookingFilter = 'paid')} type="button">Payees</button>
          <button class:active={bookingFilter === 'past'} on:click={() => (bookingFilter = 'past')} type="button">Passees</button>
          <button class:active={bookingFilter === 'cancelled'} on:click={() => (bookingFilter = 'cancelled')} type="button">Annulees</button>
          <button class:active={bookingFilter === 'all'} on:click={() => (bookingFilter = 'all')} type="button">Toutes</button>
        </div>
      </div>

      {#if bookingsError}
        <p class="inline-error">{bookingsError}</p>
      {/if}

      {#if bookingsFeedback}
        <p class="inline-success">{bookingsFeedback}</p>
      {/if}

      {#if filteredBookings.length}
        <div class="booking-list">
          {#each filteredBookings as booking (booking.id)}
            <article class="booking-card">
              <header>
                <div>
                  <strong>{booking.experience?.title || 'Experience'}</strong>
                  <small>{booking.experience?.location || 'Lieu a confirmer'}</small>
                </div>
                <span class:cancelled={booking.status === 'CANCELLED'} class="status-badge">
                  {formatBookingStatus(booking.status)}
                </span>
              </header>

              <dl>
                <div>
                  <dt>Creneau</dt>
                  <dd>{formatDateTime(booking.slot?.startAt)}</dd>
                </div>
                <div>
                  <dt>Places</dt>
                  <dd>{booking.seats}</dd>
                </div>
                <div>
                  <dt>Total</dt>
                  <dd>{formatPrice(booking.totalPrice)}</dd>
                </div>
                <div>
                  <dt>Paiement</dt>
                  <dd>{formatPaymentStatus(booking.latestPayment?.status)}</dd>
                </div>
              </dl>

              {#if booking.latestPayment}
                <p class="payment-details">
                  Ref {booking.latestPayment.transactionRef || 'mock'} - {booking.latestPayment.provider || 'mock'} -
                  {formatPrice({
                    amount: booking.latestPayment.amount || booking.totalPrice?.amount,
                    currency: booking.totalPrice?.currency || 'EUR'
                  })}
                </p>
              {/if}

              {#if booking.canPay}
                <div class="action-row">
                  <a class="primary-action" href={`${base}/payment/${booking.id}`}>Finaliser le paiement</a>
                </div>
              {/if}

              {#if booking.canCancel}
                <button
                  class="secondary-action"
                  disabled={cancellingBookingId === booking.id}
                  on:click={() => handleCancelBooking(booking.id)}
                  type="button"
                >
                  {cancellingBookingId === booking.id ? 'Annulation...' : 'Annuler'}
                </button>
              {/if}
            </article>
          {/each}
        </div>
      {:else}
        <p class="empty">Aucune reservation dans ce filtre pour le moment.</p>
      {/if}
    </section>

    {#if isOrganizerUser(currentUser)}
      <section class="panel">
        <div class="panel-head">
          <div>
            <span class="eyebrow soft">Organisation</span>
            <h2>Mes evenements organises</h2>
          </div>

          <div class="filter-row">
            <button class:active={organizerFilter === 'upcoming'} on:click={() => (organizerFilter = 'upcoming')} type="button">Prochains</button>
            <button class:active={organizerFilter === 'published'} on:click={() => (organizerFilter = 'published')} type="button">Publies</button>
            <button class:active={organizerFilter === 'draft'} on:click={() => (organizerFilter = 'draft')} type="button">Brouillons</button>
            <button class:active={organizerFilter === 'past'} on:click={() => (organizerFilter = 'past')} type="button">Passes</button>
            <button class:active={organizerFilter === 'all'} on:click={() => (organizerFilter = 'all')} type="button">Tous</button>
          </div>
        </div>

        {#if filteredOrganizerExperiences.length}
          <div class="experience-list">
            {#each filteredOrganizerExperiences as experience (experience.id)}
              {@const stats = getOrganizerExperienceStats(experience.id)}
              <article class="experience-card">
                <div class="experience-main">
                  <div>
                    <strong>{experience.title}</strong>
                    <small>{experience.location} - {experience.status}</small>
                  </div>
                  <a class="manage-link" href={`${base}/organizer`}>Gerer</a>
                </div>

                <p class="experience-date">{getExperienceHeadlineDate(experience)}</p>

                <dl>
                  <div>
                    <dt>Creneaux</dt>
                    <dd>{getExperienceSlots(experience).length}</dd>
                  </div>
                  <div>
                    <dt>A venir</dt>
                    <dd>{getUpcomingSlots(experience).length}</dd>
                  </div>
                  <div>
                    <dt>Reservations</dt>
                    <dd>{stats.bookingCount}</dd>
                  </div>
                  <div>
                    <dt>Payees</dt>
                    <dd>{stats.paidCount}</dd>
                  </div>
                </dl>
              </article>
            {/each}
          </div>
        {:else}
          <p class="empty">Aucun evenement dans ce filtre pour le moment.</p>
        {/if}
      </section>
    {:else}
      <section class="panel">
        <div class="panel-head">
          <div>
            <span class="eyebrow soft">Et apres ?</span>
            <h2>Vous voulez proposer vos propres experiences ?</h2>
          </div>
          <a class="manage-link" href={`${base}/account`}>Faire une demande organisateur</a>
        </div>
        <p>
          Votre compte visiteur vous permet deja de reserver et de suivre vos sorties. Si vous souhaitez
          publier des experiences, la demande se fait depuis Mon compte puis un administrateur valide l acces.
        </p>
      </section>
    {/if}
  </section>
{/if}

<style>
  .space-shell {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
  }

  .hero,
  .panel,
  .status-panel {
    padding: 1.5rem;
    border-radius: 1.9rem;
    background: rgba(255, 251, 246, 0.84);
    border: 1px solid rgba(112, 71, 45, 0.12);
    box-shadow: 0 24px 70px rgba(66, 40, 19, 0.08);
  }

  .hero {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(320px, 1fr);
    gap: 1rem;
    align-items: start;
  }

  .hero-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
  }

  .stat-card {
    padding: 1rem;
    border-radius: 1.25rem;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .stat-card.accent {
    background: linear-gradient(180deg, rgba(233, 247, 240, 0.95), rgba(255, 255, 255, 0.8));
  }

  .stat-card strong {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 1.9rem;
    color: #24160e;
  }

  .stat-card span {
    color: #6b5648;
    font-weight: 700;
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
    font-size: clamp(2.2rem, 5vw, 4rem);
    line-height: 1.04;
  }

  h2 {
    font-size: clamp(1.45rem, 3vw, 2rem);
  }

  p {
    line-height: 1.72;
    color: #5f5146;
  }

  .panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: end;
    margin-bottom: 1rem;
  }

  .filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
  }

  .filter-row button,
  .primary-action,
  .secondary-action,
  .manage-link {
    min-height: 2.85rem;
    padding: 0.75rem 1rem;
    border-radius: 999px;
    border: 0;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
  }

  .filter-row button {
    background: rgba(239, 229, 219, 0.82);
    color: #6d5341;
  }

  .filter-row button.active {
    background: #8d5430;
    color: #fff9f1;
  }

  .primary-action,
  .manage-link {
    background: #8d5430;
    color: #fff9f1;
  }

  .secondary-action {
    background: rgba(240, 229, 219, 0.95);
    color: #6d5341;
  }

  .booking-list,
  .experience-list {
    display: grid;
    gap: 0.9rem;
  }

  .booking-card,
  .experience-card {
    padding: 1rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .booking-card header,
  .experience-main {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
    margin-bottom: 0.8rem;
  }

  .booking-card strong,
  .experience-main strong {
    color: #24160e;
    font-size: 1.05rem;
  }

  .booking-card small,
  .experience-main small {
    display: block;
    margin-top: 0.25rem;
    color: #7a6555;
  }

  .status-badge {
    padding: 0.45rem 0.8rem;
    border-radius: 999px;
    background: rgba(255, 224, 176, 0.7);
    color: #7f5a1d;
    font-weight: 700;
  }

  .status-badge.cancelled {
    background: rgba(238, 211, 211, 0.75);
    color: #95433b;
  }

  dl {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.8rem;
    margin: 0;
  }

  dt {
    margin-bottom: 0.28rem;
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

  .payment-details,
  .experience-date {
    margin: 0.9rem 0 0;
    padding: 0.85rem 1rem;
    border-radius: 1rem;
    background: rgba(247, 239, 229, 0.78);
    color: #6d5341;
  }

  .action-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.9rem;
  }

  .primary-action:disabled,
  .secondary-action:disabled {
    opacity: 0.7;
    cursor: wait;
  }

  .inline-error,
  .inline-success,
  .status-panel.error {
    padding: 0.9rem 1rem;
    border-radius: 1rem;
  }

  .inline-error,
  .status-panel.error {
    color: #9c2f20;
    border: 1px solid rgba(156, 47, 32, 0.16);
    background: rgba(255, 244, 241, 0.92);
  }

  .inline-success {
    color: #1f7e5c;
    border: 1px solid rgba(31, 126, 92, 0.16);
    background: rgba(234, 247, 239, 0.92);
  }

  .empty {
    margin: 0;
    padding: 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  @media (max-width: 960px) {
    .hero,
    .panel-head,
    dl {
      grid-template-columns: 1fr;
    }

    .hero {
      grid-template-columns: 1fr;
    }

    .panel-head {
      align-items: start;
      flex-direction: column;
    }
  }

  @media (max-width: 720px) {
    .hero-stats {
      grid-template-columns: 1fr 1fr;
    }

    dl {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 560px) {
    .hero-stats,
    dl {
      grid-template-columns: 1fr;
    }
  }
</style>
