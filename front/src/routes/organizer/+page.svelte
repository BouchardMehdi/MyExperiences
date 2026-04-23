<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { onMount } from 'svelte';
  import { clearAuthSession, getStoredAuthToken, updateAuthUser } from '$lib/auth/session';
  import {
    createOrganizerExperience,
    createOrganizerSlot,
    deleteOrganizerExperience,
    deleteOrganizerSlot,
    fetchCurrentUser,
    fetchOrganizerDashboard,
    updateOrganizerExperience,
    updateOrganizerSlot
  } from '$lib/api/client';
  import { formatBookingStatus, formatPaymentStatus } from '$lib/utils/booking';
  import { formatDateTime, formatPrice } from '$lib/utils/experience';

  /**
   * @typedef {{ title: string; description: string; location: string; price: string | number; durationMinutes: string | number; status: string }} ExperienceDraft
   */

  /**
   * @typedef {{ startAt: string; endAt: string; capacity: string | number; isActive: boolean }} SlotDraft
   */

  let isLoading = true;
  let error = '';
  let success = '';
  /** @type {Record<string, any> | null} */
  let currentUser = null;
  /** @type {Record<string, any> | null} */
  let dashboard = null;
  /** @type {Record<number, ExperienceDraft>} */
  let experienceDrafts = {};
  /** @type {Record<number, SlotDraft>} */
  let slotDrafts = {};
  /** @type {Record<number, SlotDraft>} */
  let newSlotDrafts = {};
  let processingKey = '';

  /** @type {ExperienceDraft} */
  let newExperience = {
    title: '',
    description: '',
    location: '',
    price: '',
    durationMinutes: 90,
    status: 'DRAFT'
  };

  onMount(async () => {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    try {
      const userResponse = await fetchCurrentUser(token);
      currentUser =
        userResponse.data && typeof userResponse.data === 'object'
          ? /** @type {Record<string, any>} */ (userResponse.data)
          : null;

      if (!isOrganizerUser(currentUser)) {
        await goto(`${base}/account`);
        return;
      }

      updateAuthUser(/** @type {Record<string, unknown>} */ (currentUser));
      await loadDashboard(token);
    } catch (exception) {
      clearAuthSession();
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
      await goto(`${base}/login`);
    } finally {
      isLoading = false;
    }
  });

  /**
   * @param {Record<string, any> | null | undefined} user
   */
  function isOrganizerUser(user) {
    return (
      user &&
      Array.isArray(user.roles) &&
      (user.roles.includes('ROLE_ORGANIZER') || user.roles.includes('ROLE_ADMIN'))
    );
  }

  /**
   * @param {string} token
   */
  async function loadDashboard(token) {
    const response = await fetchOrganizerDashboard(token);
    dashboard = response.data && typeof response.data === 'object' ? response.data : null;
    hydrateDrafts();
  }

  function hydrateDrafts() {
    /** @type {Record<number, ExperienceDraft>} */
    const nextExperienceDrafts = {};
    /** @type {Record<number, SlotDraft>} */
    const nextSlotDrafts = {};
    /** @type {Record<number, SlotDraft>} */
    const nextNewSlotDrafts = {};

    for (const experience of dashboard?.experiences || []) {
      nextExperienceDrafts[experience.id] = {
        title: experience.title || '',
        description: experience.description || '',
        location: experience.location || '',
        price: experience.price?.amount || '',
        durationMinutes: experience.durationMinutes || 60,
        status: experience.status || 'DRAFT'
      };

      nextNewSlotDrafts[experience.id] = nextNewSlotDrafts[experience.id] || {
        startAt: '',
        endAt: '',
        capacity: 10,
        isActive: true
      };

      for (const slot of experience.slots || []) {
        nextSlotDrafts[slot.id] = {
          startAt: toDateTimeLocalValue(slot.startAt),
          endAt: toDateTimeLocalValue(slot.endAt),
          capacity: slot.capacity,
          isActive: !!slot.isActive
        };
      }
    }

    experienceDrafts = nextExperienceDrafts;
    slotDrafts = nextSlotDrafts;
    newSlotDrafts = nextNewSlotDrafts;
  }

  /**
   * @param {string | null | undefined} value
   */
  function toDateTimeLocalValue(value) {
    return typeof value === 'string' && value.length >= 16 ? value.slice(0, 16) : '';
  }

  async function handleCreateExperience() {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = 'create-experience';
    error = '';
    success = '';

    try {
      await createOrganizerExperience(token, newExperience);
      newExperience = {
        title: '',
        description: '',
        location: '',
        price: '',
        durationMinutes: 90,
        status: 'DRAFT'
      };
      await loadDashboard(token);
      success = 'Experience creee.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }

  /**
   * @param {number} experienceId
   */
  async function handleUpdateExperience(experienceId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = `experience-${experienceId}`;
    error = '';
    success = '';

    try {
      await updateOrganizerExperience(token, experienceId, experienceDrafts[experienceId]);
      await loadDashboard(token);
      success = 'Experience mise a jour.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }

  /**
   * @param {number} experienceId
   */
  async function handleDeleteExperience(experienceId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = `delete-experience-${experienceId}`;
    error = '';
    success = '';

    try {
      await deleteOrganizerExperience(token, experienceId);
      await loadDashboard(token);
      success = 'Experience supprimee.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }

  /**
   * @param {number} experienceId
   */
  async function handleCreateSlot(experienceId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = `create-slot-${experienceId}`;
    error = '';
    success = '';

    try {
      await createOrganizerSlot(token, experienceId, newSlotDrafts[experienceId]);
      await loadDashboard(token);
      success = 'Creneau ajoute.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }

  /**
   * @param {number} slotId
   */
  async function handleUpdateSlot(slotId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = `slot-${slotId}`;
    error = '';
    success = '';

    try {
      await updateOrganizerSlot(token, slotId, slotDrafts[slotId]);
      await loadDashboard(token);
      success = 'Creneau mis a jour.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }

  /**
   * @param {number} slotId
   */
  async function handleDeleteSlot(slotId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = `delete-slot-${slotId}`;
    error = '';
    success = '';

    try {
      await deleteOrganizerSlot(token, slotId);
      await loadDashboard(token);
      success = 'Creneau supprime.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }
</script>

<svelte:head>
  <title>MyExperiences | Organisateur</title>
</svelte:head>

{#if isLoading}
  <section class="status-panel">Chargement de l'espace organisateur...</section>
{:else if error && !dashboard}
  <section class="status-panel error">{error}</section>
{:else if dashboard}
  <section class="organizer-shell">
    <div class="hero">
      <div>
        <span class="eyebrow">Organisateur</span>
        <h1>Tableau de bord</h1>
        <p>Gerez vos experiences, vos creneaux et les reservations associees depuis une seule page.</p>
      </div>

      <div class="stats-grid">
        <article class="stat-card">
          <strong>{dashboard.stats?.experienceCount || 0}</strong>
          <span>Experiences</span>
        </article>
        <article class="stat-card">
          <strong>{dashboard.stats?.slotCount || 0}</strong>
          <span>Creneaux</span>
        </article>
        <article class="stat-card">
          <strong>{dashboard.stats?.bookingCount || 0}</strong>
          <span>Reservations</span>
        </article>
        <article class="stat-card">
          <strong>{dashboard.stats?.paidBookingCount || 0}</strong>
          <span>Reservations payees</span>
        </article>
      </div>
    </div>

    {#if error}
      <p class="inline-error">{error}</p>
    {/if}

    {#if success}
      <p class="inline-success">{success}</p>
    {/if}

    <section class="panel">
      <div class="panel-head">
        <span class="eyebrow">Creation</span>
        <h2>Nouvelle experience</h2>
      </div>

      <form class="experience-form" on:submit|preventDefault={handleCreateExperience}>
        <label>
          <span>Titre</span>
          <input bind:value={newExperience.title} required type="text" />
        </label>
        <label class="wide">
          <span>Description</span>
          <textarea bind:value={newExperience.description} required rows="4"></textarea>
        </label>
        <label>
          <span>Lieu</span>
          <input bind:value={newExperience.location} required type="text" />
        </label>
        <label>
          <span>Prix</span>
          <input bind:value={newExperience.price} min="0" required step="0.01" type="number" />
        </label>
        <label>
          <span>Duree</span>
          <input bind:value={newExperience.durationMinutes} min="1" required type="number" />
        </label>
        <label>
          <span>Statut</span>
          <select bind:value={newExperience.status}>
            <option value="DRAFT">Brouillon</option>
            <option value="PUBLISHED">Publiee</option>
            <option value="ARCHIVED">Archivee</option>
          </select>
        </label>

        <button class="primary" disabled={processingKey === 'create-experience'} type="submit">
          {processingKey === 'create-experience' ? 'Creation...' : 'Creer l experience'}
        </button>
      </form>
    </section>

    <section class="panel">
      <div class="panel-head">
        <span class="eyebrow">Catalogue</span>
        <h2>Mes experiences</h2>
      </div>

      {#if dashboard.experiences?.length}
        <div class="experience-list">
          {#each dashboard.experiences as experience (experience.id)}
            <article class="experience-card">
              <header class="card-head">
                <div>
                  <strong>{experience.title}</strong>
                  <small>{experience.location} · {formatPrice(experience.price)}</small>
                </div>
                <span class="status-chip">{experience.status}</span>
              </header>

              <form class="experience-form" on:submit|preventDefault={() => handleUpdateExperience(experience.id)}>
                <label>
                  <span>Titre</span>
                  <input bind:value={experienceDrafts[experience.id].title} type="text" />
                </label>
                <label class="wide">
                  <span>Description</span>
                  <textarea bind:value={experienceDrafts[experience.id].description} rows="4"></textarea>
                </label>
                <label>
                  <span>Lieu</span>
                  <input bind:value={experienceDrafts[experience.id].location} type="text" />
                </label>
                <label>
                  <span>Prix</span>
                  <input bind:value={experienceDrafts[experience.id].price} min="0" step="0.01" type="number" />
                </label>
                <label>
                  <span>Duree</span>
                  <input bind:value={experienceDrafts[experience.id].durationMinutes} min="1" type="number" />
                </label>
                <label>
                  <span>Statut</span>
                  <select bind:value={experienceDrafts[experience.id].status}>
                    <option value="DRAFT">Brouillon</option>
                    <option value="PUBLISHED">Publiee</option>
                    <option value="ARCHIVED">Archivee</option>
                  </select>
                </label>

                <div class="action-row">
                  <button class="primary" disabled={processingKey === `experience-${experience.id}`} type="submit">
                    {processingKey === `experience-${experience.id}` ? 'Enregistrement...' : 'Mettre a jour'}
                  </button>
                  <button
                    class="secondary danger"
                    disabled={processingKey === `delete-experience-${experience.id}`}
                    on:click={() => handleDeleteExperience(experience.id)}
                    type="button"
                  >
                    {processingKey === `delete-experience-${experience.id}` ? 'Suppression...' : 'Supprimer'}
                  </button>
                </div>
              </form>

              <div class="slot-section">
                <h3>Creneaux</h3>

                {#if experience.slots?.length}
                  <div class="slot-list">
                    {#each experience.slots as slot (slot.id)}
                      <article class="slot-card">
                        <form class="slot-form" on:submit|preventDefault={() => handleUpdateSlot(slot.id)}>
                          <label>
                            <span>Debut</span>
                            <input bind:value={slotDrafts[slot.id].startAt} type="datetime-local" />
                          </label>
                          <label>
                            <span>Fin</span>
                            <input bind:value={slotDrafts[slot.id].endAt} type="datetime-local" />
                          </label>
                          <label>
                            <span>Capacite</span>
                            <input bind:value={slotDrafts[slot.id].capacity} min="1" type="number" />
                          </label>
                          <label class="checkbox">
                            <input bind:checked={slotDrafts[slot.id].isActive} type="checkbox" />
                            <span>Actif</span>
                          </label>

                          <p class="slot-meta">
                            {slot.bookedSeats} reservees · {slot.remainingPlaces} restantes
                          </p>

                          <div class="action-row">
                            <button class="primary" disabled={processingKey === `slot-${slot.id}`} type="submit">
                              {processingKey === `slot-${slot.id}` ? 'Mise a jour...' : 'Mettre a jour'}
                            </button>
                            <button
                              class="secondary danger"
                              disabled={processingKey === `delete-slot-${slot.id}`}
                              on:click={() => handleDeleteSlot(slot.id)}
                              type="button"
                            >
                              {processingKey === `delete-slot-${slot.id}` ? 'Suppression...' : 'Supprimer'}
                            </button>
                          </div>
                        </form>
                      </article>
                    {/each}
                  </div>
                {:else}
                  <p class="empty">Aucun creneau pour cette experience.</p>
                {/if}

                <form class="slot-form new-slot" on:submit|preventDefault={() => handleCreateSlot(experience.id)}>
                  <label>
                    <span>Nouveau debut</span>
                    <input bind:value={newSlotDrafts[experience.id].startAt} type="datetime-local" />
                  </label>
                  <label>
                    <span>Nouvelle fin</span>
                    <input bind:value={newSlotDrafts[experience.id].endAt} type="datetime-local" />
                  </label>
                  <label>
                    <span>Capacite</span>
                    <input bind:value={newSlotDrafts[experience.id].capacity} min="1" type="number" />
                  </label>
                  <label class="checkbox">
                    <input bind:checked={newSlotDrafts[experience.id].isActive} type="checkbox" />
                    <span>Actif</span>
                  </label>

                  <button class="secondary" disabled={processingKey === `create-slot-${experience.id}`} type="submit">
                    {processingKey === `create-slot-${experience.id}` ? 'Ajout...' : 'Ajouter un creneau'}
                  </button>
                </form>
              </div>
            </article>
          {/each}
        </div>
      {:else}
        <p class="empty">Aucune experience creee pour le moment.</p>
      {/if}
    </section>

    <section class="panel">
      <div class="panel-head">
        <span class="eyebrow">Reservations</span>
        <h2>Reservations liees</h2>
      </div>

      {#if dashboard.bookings?.length}
        <div class="booking-list">
          {#each dashboard.bookings as booking (booking.id)}
            <article class="booking-card">
              <header class="card-head">
                <div>
                  <strong>{booking.experience?.title || 'Experience'}</strong>
                  <small>{booking.customer?.fullName || 'Participant'} · {booking.customer?.email}</small>
                </div>
                <span class="status-chip">{formatBookingStatus(booking.status)}</span>
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
            </article>
          {/each}
        </div>
      {:else}
        <p class="empty">Aucune reservation recue pour le moment.</p>
      {/if}
    </section>
  </section>
{/if}

<style>
  .organizer-shell {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
  }

  .hero,
  .panel,
  .status-panel {
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

  h1,
  h2,
  h3 {
    margin: 0;
    font-family: Georgia, 'Times New Roman', serif;
    color: #24160e;
  }

  h1 {
    font-size: clamp(2.2rem, 5vw, 3.6rem);
    line-height: 1.04;
  }

  h2 {
    font-size: clamp(1.5rem, 3vw, 2rem);
  }

  h3 {
    font-size: 1.2rem;
  }

  p {
    line-height: 1.75;
    color: #5f5146;
  }

  .panel-head {
    margin-bottom: 1rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.85rem;
    margin-top: 1.2rem;
  }

  .stat-card,
  .experience-card,
  .slot-card,
  .booking-card {
    padding: 1rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .stat-card strong {
    display: block;
    font-size: 1.8rem;
    color: #24160e;
  }

  .experience-list,
  .booking-list,
  .slot-list {
    display: grid;
    gap: 0.9rem;
  }

  .experience-form,
  .slot-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
    margin-top: 1rem;
  }

  .experience-form .wide {
    grid-column: 1 / -1;
  }

  label {
    display: grid;
    gap: 0.4rem;
  }

  label span,
  dt {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #7d746c;
  }

  input,
  textarea,
  select {
    min-height: 3rem;
    padding: 0.8rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(143, 108, 82, 0.22);
    background: #fffdf9;
    color: #291d16;
    font: inherit;
  }

  textarea {
    min-height: 8rem;
    resize: vertical;
  }

  .checkbox {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-height: 3rem;
  }

  .checkbox input {
    min-height: auto;
    padding: 0;
  }

  .checkbox span {
    font-size: 0.95rem;
    letter-spacing: 0;
    text-transform: none;
  }

  .action-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    grid-column: 1 / -1;
  }

  .primary,
  .secondary {
    min-height: 2.9rem;
    padding: 0.8rem 1rem;
    border-radius: 999px;
    border: 0;
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

  .danger {
    background: rgba(255, 244, 241, 0.92);
    color: #8a473f;
  }

  .primary:disabled,
  .secondary:disabled {
    opacity: 0.7;
    cursor: wait;
  }

  .card-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
  }

  .card-head strong {
    display: block;
  }

  .status-chip {
    padding: 0.4rem 0.8rem;
    border-radius: 999px;
    background: rgba(243, 231, 220, 0.9);
    color: #6e513e;
    font-weight: 700;
  }

  .slot-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(143, 108, 82, 0.12);
  }

  .slot-meta,
  small {
    color: #7a6555;
  }

  .new-slot {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px dashed rgba(143, 108, 82, 0.2);
  }

  dl {
    display: grid;
    gap: 0.8rem;
    margin: 1rem 0 0;
  }

  dd {
    margin: 0.2rem 0 0;
    color: #2a2019;
    font-weight: 700;
  }

  .inline-error,
  .status-panel.error,
  .inline-success {
    margin: 0;
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

  @media (max-width: 900px) {
    .stats-grid,
    .experience-form,
    .slot-form {
      grid-template-columns: 1fr;
    }
  }
</style>
