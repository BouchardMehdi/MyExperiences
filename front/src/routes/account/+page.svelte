<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { onMount } from 'svelte';
  import { authSession, clearAuthSession, getStoredAuthToken, updateAuthUser } from '$lib/auth/session';
  import { cancelBooking, fetchCurrentUser, fetchMyBookings, payBooking } from '$lib/api/client';
  import { formatBookingStatus, formatPaymentStatus } from '$lib/utils/booking';
  import { formatDateTime, formatPrice } from '$lib/utils/experience';

  let error = '';
  let isLoading = true;
  /** @type {Record<string, any> | null} */
  let currentUser = null;
  /** @type {Array<Record<string, any>>} */
  let bookings = [];
  let bookingsError = '';
  let bookingsFeedback = '';
  /** @type {number | null} */
  let cancellingBookingId = null;
  /** @type {number | null} */
  let payingBookingId = null;

  onMount(async () => {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    try {
      const [userResponse, bookingsResponse] = await Promise.all([
        fetchCurrentUser(token),
        fetchMyBookings(token)
      ]);

      if (userResponse.data && typeof userResponse.data === 'object') {
        updateAuthUser(/** @type {Record<string, unknown>} */ (userResponse.data));
      } else {
        throw new Error('La reponse utilisateur est incomplete.');
      }

      bookings = Array.isArray(bookingsResponse.data) ? bookingsResponse.data : [];
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
        throw new Error("La reponse d'annulation est incomplete.");
      }

      bookings = bookings.map((booking) => (booking.id === bookingId ? updatedBooking : booking));
      bookingsFeedback = 'La reservation a bien ete annulee.';
    } catch (exception) {
      bookingsError = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      cancellingBookingId = null;
    }
  }

  /**
   * @param {number} bookingId
   * @param {'success' | 'failure'} outcome
   */
  async function handlePayBooking(bookingId, outcome) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    payingBookingId = bookingId;
    bookingsError = '';
    bookingsFeedback = '';

    try {
      const response = await payBooking(token, bookingId, outcome);
      const updatedBooking = response.data && typeof response.data === 'object' ? response.data : null;

      if (!updatedBooking) {
        throw new Error('La reponse de paiement est incomplete.');
      }

      bookings = bookings.map((booking) => (booking.id === bookingId ? updatedBooking : booking));
      bookingsFeedback =
        outcome === 'failure'
          ? 'Le paiement mock a echoue et la reservation a ete annulee.'
          : 'Le paiement mock a ete valide avec succes.';
    } catch (exception) {
      bookingsError = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      payingBookingId = null;
    }
  }
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
        Votre session API Bearer est active. Vous pouvez maintenant suivre vos reservations et
        annuler une reservation encore active depuis cet espace.
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
        <span class="eyebrow">Reservations</span>
        <h2>Mes reservations</h2>

        {#if bookingsError}
          <p class="inline-error">{bookingsError}</p>
        {/if}

        {#if bookingsFeedback}
          <p class="inline-success">{bookingsFeedback}</p>
        {/if}

        {#if bookings.length}
          <div class="booking-list">
            {#each bookings as booking (booking.id)}
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
                    <dt>Statut paiement</dt>
                    <dd>{formatPaymentStatus(booking.latestPayment?.status)}</dd>
                  </div>
                </dl>

                {#if booking.latestPayment}
                  <p class="payment-details">
                    Ref {booking.latestPayment.transactionRef || 'mock'} · {booking.latestPayment.provider || 'mock'} · {formatPrice(booking.latestPayment.amount)}
                  </p>
                {/if}

                {#if booking.canPay}
                  <div class="action-row">
                    <button
                      class="primary-action"
                      disabled={payingBookingId === booking.id || cancellingBookingId === booking.id}
                      on:click={() => handlePayBooking(booking.id, 'success')}
                      type="button"
                    >
                      {payingBookingId === booking.id ? 'Traitement...' : 'Payer (mock)'}
                    </button>

                    <button
                      class="secondary ghost"
                      disabled={payingBookingId === booking.id || cancellingBookingId === booking.id}
                      on:click={() => handlePayBooking(booking.id, 'failure')}
                      type="button"
                    >
                      Simuler un echec
                    </button>
                  </div>
                {/if}

                {#if booking.canCancel}
                  <button
                    class="secondary"
                    disabled={cancellingBookingId === booking.id || payingBookingId === booking.id}
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
          <p class="empty">Aucune reservation pour le moment. Vous pouvez commencer depuis la page detail d'une experience.</p>
        {/if}
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
    grid-template-columns: minmax(300px, 0.9fr) minmax(0, 1.3fr);
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

  h1,
  h2 {
    margin: 0;
    font-family: Georgia, 'Times New Roman', serif;
    color: #24160e;
  }

  h1 {
    font-size: clamp(2.2rem, 5vw, 4rem);
    line-height: 1.04;
  }

  h2 {
    font-size: clamp(1.5rem, 3vw, 2rem);
  }

  p {
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

  .booking-list {
    display: grid;
    gap: 0.9rem;
    margin-top: 1rem;
  }

  .booking-card {
    padding: 1rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .booking-card header {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
    margin-bottom: 0.8rem;
  }

  .booking-card strong {
    color: #24160e;
    font-size: 1.05rem;
  }

  .booking-card small {
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

  .secondary {
    margin-top: 0.9rem;
    min-height: 2.8rem;
    padding: 0.75rem 1rem;
    border: 0;
    border-radius: 999px;
    background: rgba(240, 229, 219, 0.95);
    color: #6d5341;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
  }

  .primary-action {
    min-height: 2.8rem;
    padding: 0.75rem 1rem;
    border: 0;
    border-radius: 999px;
    background: #8d5430;
    color: #fff9f1;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
  }

  .action-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.9rem;
  }

  .ghost {
    background: rgba(255, 244, 241, 0.92);
    color: #8a473f;
  }

  .secondary:disabled,
  .primary-action:disabled {
    opacity: 0.7;
    cursor: wait;
  }

  .payment-details {
    margin-top: 0.9rem;
    padding: 0.85rem 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.86);
    border: 1px solid rgba(143, 108, 82, 0.12);
    color: #6d5341;
  }

  .inline-error,
  .inline-success,
  .status-panel.error {
    color: #9c2f20;
    border-color: rgba(156, 47, 32, 0.16);
    background: rgba(255, 244, 241, 0.92);
  }

  .inline-error {
    margin: 1rem 0 0;
    padding: 0.9rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(156, 47, 32, 0.16);
  }

  .inline-success {
    margin: 1rem 0 0;
    color: #1f7e5c;
    border-color: rgba(31, 126, 92, 0.16);
    background: rgba(234, 247, 239, 0.92);
  }

  .empty {
    margin: 1rem 0 0;
    padding: 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  @media (max-width: 900px) {
    .details-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
