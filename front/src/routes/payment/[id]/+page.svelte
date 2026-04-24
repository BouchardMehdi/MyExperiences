<script>
  import { browser } from '$app/environment';
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { page } from '$app/stores';
  import { onDestroy } from 'svelte';
  import { fetchBookingById, payBooking } from '$lib/api/client';
  import { getStoredAuthToken } from '$lib/auth/session';
  import { formatBookingStatus, formatPaymentStatus } from '$lib/utils/booking';
  import { formatDateTime, formatPrice } from '$lib/utils/experience';

  /** @type {Record<string, any> | null} */
  let booking = null;
  let bookingId = '';
  let currentId = '';
  let isLoading = true;
  let error = '';
  let paymentState = 'idle';
  let redirectCountdown = 4;
  /** @type {ReturnType<typeof setTimeout> | null} */
  let redirectTimeout = null;
  /** @type {ReturnType<typeof setInterval> | null} */
  let countdownInterval = null;

  $: bookingId = $page.params.id ?? '';

  $: if (browser && bookingId && bookingId !== currentId) {
    currentId = bookingId;
    void loadBooking(bookingId);
  }

  onDestroy(() => {
    clearRedirectTimers();
  });

  /**
   * @param {string} id
   */
  async function loadBooking(id) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    isLoading = true;
    error = '';
    booking = null;
    paymentState = 'idle';
    clearRedirectTimers();

    try {
      const response = await fetchBookingById(token, id);
      booking = response.data && typeof response.data === 'object' ? response.data : null;

      if (!booking) {
        throw new Error('La reservation est introuvable.');
      }

      if (booking.status === 'PAID') {
        paymentState = 'success';
        scheduleSpaceRedirect();
      } else if (booking.status === 'CANCELLED') {
        paymentState = 'failure';
      }
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      isLoading = false;
    }
  }

  /**
   * @param {'success' | 'failure'} outcome
   */
  async function processPayment(outcome) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    paymentState = 'processing';
    error = '';
    clearRedirectTimers();

    try {
      await wait(900);
      const response = await payBooking(token, bookingId, outcome);
      booking = response.data && typeof response.data === 'object' ? response.data : booking;
      paymentState = outcome === 'success' ? 'success' : 'failure';

      if (outcome === 'success') {
        scheduleSpaceRedirect();
      }
    } catch (exception) {
      paymentState = 'idle';
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    }
  }

  function scheduleSpaceRedirect() {
    clearRedirectTimers();
    redirectCountdown = 4;

    countdownInterval = setInterval(() => {
      redirectCountdown = Math.max(0, redirectCountdown - 1);
    }, 1000);

    redirectTimeout = setTimeout(() => {
      void goto(`${base}/space`);
    }, 4000);
  }

  function clearRedirectTimers() {
    if (redirectTimeout) {
      clearTimeout(redirectTimeout);
      redirectTimeout = null;
    }

    if (countdownInterval) {
      clearInterval(countdownInterval);
      countdownInterval = null;
    }
  }

  /**
   * @param {number} duration
   */
  function wait(duration) {
    return new Promise((resolve) => {
      setTimeout(resolve, duration);
    });
  }
</script>

<svelte:head>
  <title>MyExperiences | Paiement mock</title>
</svelte:head>

{#if isLoading}
  <section class="status-panel">Preparation du paiement...</section>
{:else if error}
  <section class="status-panel error">{error}</section>
{:else if booking}
  <section class:success={paymentState === 'success'} class:failure={paymentState === 'failure'} class="payment-shell">
    <div class="payment-card">
      <span class="eyebrow">Paiement mock</span>
      <h1>Finaliser votre reservation.</h1>
      <p>
        Aucun vrai paiement n est effectue. Vous pouvez simuler une reussite ou un echec pour tester
        le parcours complet de reservation.
      </p>

      <div class="summary-grid">
        <article>
          <span>Experience</span>
          <strong>{booking.experience?.title || 'Experience'}</strong>
          <small>{booking.experience?.location || 'Lieu a confirmer'}</small>
        </article>
        <article>
          <span>Creneau</span>
          <strong>{formatDateTime(booking.slot?.startAt)}</strong>
          <small>{booking.seats} place{booking.seats > 1 ? 's' : ''}</small>
        </article>
        <article>
          <span>Total</span>
          <strong>{formatPrice(booking.totalPrice)}</strong>
          <small>{formatBookingStatus(booking.status)}</small>
        </article>
      </div>

      {#if booking.latestPayment}
        <p class="payment-note">
          Dernier paiement : {formatPaymentStatus(booking.latestPayment.status)}
          {#if booking.latestPayment.transactionRef}
            - ref {booking.latestPayment.transactionRef}
          {/if}
        </p>
      {/if}

      <div class="mock-terminal" aria-live="polite">
        <div class="orb">
          <span></span>
          <span></span>
          <span></span>
        </div>

        {#if paymentState === 'processing'}
          <strong>Traitement du paiement...</strong>
          <p>Verification mock, autorisation, confirmation. La petite machine fait semblant tres serieusement.</p>
        {:else if paymentState === 'success'}
          <strong>Paiement reussi.</strong>
          <p>Reservation confirmee. Redirection vers Mon espace dans {redirectCountdown} seconde{redirectCountdown > 1 ? 's' : ''}.</p>
        {:else if paymentState === 'failure'}
          <strong>Paiement echoue.</strong>
          <p>La reservation a ete annulee et les places sont liberees. Vous pouvez repartir sur l experience.</p>
        {:else}
          <strong>Pret a tester.</strong>
          <p>Choisissez le resultat mock que vous voulez simuler.</p>
        {/if}
      </div>

      <div class="action-row">
        {#if paymentState === 'idle'}
          <button class="primary-action" on:click={() => processPayment('success')} type="button">
            Payer et confirmer
          </button>
          <button class="secondary-action" on:click={() => processPayment('failure')} type="button">
            Simuler un echec
          </button>
        {:else if paymentState === 'processing'}
          <button class="primary-action" disabled type="button">Paiement en cours...</button>
        {:else if paymentState === 'success'}
          <a class="primary-action" href={`${base}/space`}>Aller a Mon espace</a>
        {:else if paymentState === 'failure'}
          <a class="primary-action" href={`${base}/experiences/${booking.experience?.id || ''}`}>
            Refaire une reservation
          </a>
          <a class="secondary-action" href={`${base}/space`}>Voir Mon espace</a>
        {/if}
      </div>
    </div>
  </section>
{:else}
  <section class="status-panel">Reservation introuvable.</section>
{/if}

<style>
  .payment-shell,
  .status-panel {
    margin-top: 1rem;
  }

  .payment-shell {
    min-height: 70vh;
    display: grid;
    place-items: center;
    padding: 1rem;
  }

  .payment-card,
  .status-panel {
    width: min(100%, 980px);
    padding: clamp(1.2rem, 4vw, 2.2rem);
    border-radius: 2rem;
    background:
      radial-gradient(circle at 12% 10%, rgba(205, 113, 65, 0.16), transparent 34%),
      radial-gradient(circle at 88% 0%, rgba(143, 180, 153, 0.2), transparent 32%),
      rgba(255, 251, 246, 0.9);
    border: 1px solid rgba(112, 71, 45, 0.12);
    box-shadow: 0 30px 90px rgba(66, 40, 19, 0.11);
  }

  .eyebrow {
    display: inline-flex;
    margin-bottom: 0.8rem;
    padding: 0.38rem 0.8rem;
    border-radius: 999px;
    background: rgba(235, 203, 178, 0.35);
    color: #8a5b3b;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-weight: 800;
  }

  h1 {
    max-width: 720px;
    margin: 0;
    font-family: Constantia, Georgia, serif;
    font-size: clamp(2.4rem, 7vw, 5.2rem);
    line-height: 0.95;
    color: #24160e;
  }

  p {
    max-width: 680px;
    line-height: 1.72;
    color: #5f5146;
  }

  .summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.8rem;
    margin-top: 1.4rem;
  }

  .summary-grid article {
    padding: 1rem;
    border-radius: 1.25rem;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .summary-grid span,
  .summary-grid small {
    display: block;
    color: #7a6555;
  }

  .summary-grid span {
    margin-bottom: 0.42rem;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    font-weight: 800;
  }

  .summary-grid strong {
    display: block;
    color: #24160e;
    font-size: 1.05rem;
  }

  .summary-grid small {
    margin-top: 0.32rem;
  }

  .payment-note {
    padding: 0.85rem 1rem;
    border-radius: 1rem;
    background: rgba(247, 239, 229, 0.78);
  }

  .mock-terminal {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 1rem;
    align-items: center;
    margin-top: 1.2rem;
    padding: 1rem;
    border-radius: 1.35rem;
    overflow: hidden;
    background: #24160e;
    color: #fff7ec;
  }

  .mock-terminal::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(110deg, transparent 0%, rgba(255, 255, 255, 0.12) 42%, transparent 58%);
    transform: translateX(-100%);
    animation: scan 2.2s infinite;
  }

  .mock-terminal strong,
  .mock-terminal p,
  .orb {
    position: relative;
    z-index: 1;
  }

  .mock-terminal strong {
    display: block;
    font-size: 1.15rem;
  }

  .mock-terminal p {
    margin: 0.28rem 0 0;
    color: rgba(255, 247, 236, 0.78);
  }

  .orb {
    width: 4.2rem;
    height: 4.2rem;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(255, 249, 241, 0.1);
  }

  .orb span {
    position: absolute;
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 50%;
    border: 3px solid rgba(255, 247, 236, 0.85);
    animation: pulse 1.4s infinite ease-out;
  }

  .orb span:nth-child(2) {
    animation-delay: 0.22s;
  }

  .orb span:nth-child(3) {
    animation-delay: 0.44s;
  }

  .success .mock-terminal {
    background: #1f6f51;
  }

  .success .orb {
    background: rgba(255, 255, 255, 0.16);
  }

  .success .orb::after {
    content: '';
    width: 1.25rem;
    height: 0.72rem;
    border-left: 4px solid #fff;
    border-bottom: 4px solid #fff;
    transform: rotate(-45deg);
  }

  .success .orb span {
    border-color: rgba(255, 255, 255, 0.34);
  }

  .failure .mock-terminal {
    background: #8b3329;
  }

  .failure .orb::before,
  .failure .orb::after {
    content: '';
    position: absolute;
    width: 1.9rem;
    height: 4px;
    border-radius: 999px;
    background: #fff;
  }

  .failure .orb::before {
    transform: rotate(45deg);
  }

  .failure .orb::after {
    transform: rotate(-45deg);
  }

  .failure .orb span {
    border-color: rgba(255, 255, 255, 0.28);
  }

  .action-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1.2rem;
  }

  .primary-action,
  .secondary-action {
    min-height: 3rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.8rem 1.1rem;
    border-radius: 999px;
    border: 0;
    font: inherit;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
  }

  .primary-action {
    background: #8d5430;
    color: #fff9f1;
  }

  .secondary-action {
    background: rgba(240, 229, 219, 0.95);
    color: #6d5341;
  }

  .primary-action:disabled {
    opacity: 0.72;
    cursor: wait;
  }

  .status-panel.error {
    color: #9c2f20;
    border-color: rgba(156, 47, 32, 0.16);
    background: rgba(255, 244, 241, 0.92);
  }

  @keyframes pulse {
    from {
      opacity: 0.86;
      transform: scale(0.45);
    }

    to {
      opacity: 0;
      transform: scale(1.8);
    }
  }

  @keyframes scan {
    to {
      transform: translateX(100%);
    }
  }

  @media (max-width: 760px) {
    .summary-grid,
    .mock-terminal {
      grid-template-columns: 1fr;
    }

    .orb {
      width: 3.6rem;
      height: 3.6rem;
    }
  }
</style>
