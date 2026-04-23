<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { onMount } from 'svelte';
  import { clearAuthSession, getStoredAuthToken, updateAuthUser } from '$lib/auth/session';
  import {
    approveOrganizerRequest,
    deleteAdminExperience,
    deleteAdminReview,
    fetchAdminDashboard,
    fetchCurrentUser,
    rejectOrganizerRequest,
    updateAdminExperience,
    updateAdminUser
  } from '$lib/api/client';
  import { formatDateTime, formatPrice } from '$lib/utils/experience';

  /**
   * @typedef {{ email: string; firstName: string; lastName: string; roleProfile: string }} UserDraft
   */

  /**
   * @typedef {{ title: string; description: string; location: string; price: string | number; durationMinutes: string | number; status: string }} AdminExperienceDraft
   */

  let isLoading = true;
  let error = '';
  let success = '';
  /** @type {Record<string, any> | null} */
  let currentUser = null;
  /** @type {Record<string, any> | null} */
  let dashboard = null;
  /** @type {Record<number, UserDraft>} */
  let userDrafts = {};
  /** @type {Record<number, AdminExperienceDraft>} */
  let experienceDrafts = {};
  let processingKey = '';

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

      if (!isAdminUser(currentUser)) {
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
  function isAdminUser(user) {
    return !!(user && Array.isArray(user.roles) && user.roles.includes('ROLE_ADMIN'));
  }

  /**
   * @param {string} token
   */
  async function loadDashboard(token) {
    const response = await fetchAdminDashboard(token);
    dashboard = response.data && typeof response.data === 'object' ? response.data : null;
    hydrateDrafts();
  }

  function hydrateDrafts() {
    /** @type {Record<number, UserDraft>} */
    const nextUserDrafts = {};
    /** @type {Record<number, AdminExperienceDraft>} */
    const nextExperienceDrafts = {};

    for (const user of dashboard?.users || []) {
      nextUserDrafts[user.id] = {
        email: user.email || '',
        firstName: user.firstName || '',
        lastName: user.lastName || '',
        roleProfile: getRoleProfile(user.roles || [])
      };
    }

    for (const experience of dashboard?.experiences || []) {
      nextExperienceDrafts[experience.id] = {
        title: experience.title || '',
        description: experience.description || '',
        location: experience.location || '',
        price: experience.price?.amount || '',
        durationMinutes: experience.durationMinutes || 60,
        status: experience.status || 'DRAFT'
      };
    }

    userDrafts = nextUserDrafts;
    experienceDrafts = nextExperienceDrafts;
  }

  /**
   * @param {string[]} roles
   */
  function getRoleProfile(roles) {
    if (roles.includes('ROLE_ADMIN')) {
      return 'ROLE_ADMIN';
    }

    if (roles.includes('ROLE_ORGANIZER')) {
      return 'ROLE_ORGANIZER';
    }

    return 'ROLE_USER';
  }

  /**
   * @param {number} userId
   */
  async function handleUpdateUser(userId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = `user-${userId}`;
    error = '';
    success = '';

    try {
      const draft = userDrafts[userId];
      await updateAdminUser(token, userId, {
        email: draft.email,
        firstName: draft.firstName,
        lastName: draft.lastName,
        roles: draft.roleProfile === 'ROLE_USER' ? ['ROLE_USER'] : [draft.roleProfile]
      });
      await loadDashboard(token);
      success = 'Utilisateur mis a jour.';
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
      await updateAdminExperience(token, experienceId, experienceDrafts[experienceId]);
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
      await deleteAdminExperience(token, experienceId);
      await loadDashboard(token);
      success = 'Experience supprimee.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }

  /**
   * @param {number} reviewId
   */
  async function handleDeleteReview(reviewId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = `review-${reviewId}`;
    error = '';
    success = '';

    try {
      await deleteAdminReview(token, reviewId);
      await loadDashboard(token);
      success = 'Avis supprime.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }

  /**
   * @param {number} requestId
   */
  async function handleApproveOrganizerRequest(requestId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = `approve-request-${requestId}`;
    error = '';
    success = '';

    try {
      await approveOrganizerRequest(token, requestId);
      await loadDashboard(token);
      success = 'Demande organisateur approuvee.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }

  /**
   * @param {number} requestId
   */
  async function handleRejectOrganizerRequest(requestId) {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    processingKey = `reject-request-${requestId}`;
    error = '';
    success = '';

    try {
      await rejectOrganizerRequest(token, requestId);
      await loadDashboard(token);
      success = 'Demande organisateur refusee.';
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      processingKey = '';
    }
  }
</script>

<svelte:head>
  <title>MyExperiences | Admin</title>
</svelte:head>

{#if isLoading}
  <section class="status-panel">Chargement de l'espace admin...</section>
{:else if error && !dashboard}
  <section class="status-panel error">{error}</section>
{:else if dashboard}
  <section class="admin-shell">
    <div class="hero">
      <div>
        <span class="eyebrow">Administration</span>
        <h1>Vue globale</h1>
        <p>Supervisez les utilisateurs, les experiences publiees et la moderation des avis.</p>
      </div>

      <div class="stats-grid">
        <article class="stat-card">
          <strong>{dashboard.stats?.userCount || 0}</strong>
          <span>Utilisateurs</span>
        </article>
        <article class="stat-card">
          <strong>{dashboard.stats?.organizerCount || 0}</strong>
          <span>Organisateurs</span>
        </article>
        <article class="stat-card">
          <strong>{dashboard.stats?.experienceCount || 0}</strong>
          <span>Experiences</span>
        </article>
        <article class="stat-card">
          <strong>{dashboard.stats?.reviewCount || 0}</strong>
          <span>Avis</span>
        </article>
        <article class="stat-card">
          <strong>{dashboard.stats?.pendingOrganizerRequestCount || 0}</strong>
          <span>Demandes organisateur</span>
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
        <span class="eyebrow">Demandes</span>
        <h2>Demandes organisateur</h2>
      </div>

      {#if dashboard.organizerRequests?.length}
        <div class="card-list">
          {#each dashboard.organizerRequests as organizerRequest (organizerRequest.id)}
            <article class="card">
              <header class="card-head">
                <div>
                  <strong>{organizerRequest.user?.fullName || 'Utilisateur'}</strong>
                  <small>{organizerRequest.user?.email} - {formatDateTime(organizerRequest.createdAt)}</small>
                </div>
                <span class="status-chip">{organizerRequest.status}</span>
              </header>

              <p>{organizerRequest.motivation}</p>

              <div class="action-row">
                <button
                  class="primary"
                  disabled={processingKey === `approve-request-${organizerRequest.id}`}
                  on:click={() => handleApproveOrganizerRequest(organizerRequest.id)}
                  type="button"
                >
                  {processingKey === `approve-request-${organizerRequest.id}` ? 'Validation...' : 'Approuver'}
                </button>
                <button
                  class="secondary danger"
                  disabled={processingKey === `reject-request-${organizerRequest.id}`}
                  on:click={() => handleRejectOrganizerRequest(organizerRequest.id)}
                  type="button"
                >
                  {processingKey === `reject-request-${organizerRequest.id}` ? 'Refus...' : 'Refuser'}
                </button>
              </div>
            </article>
          {/each}
        </div>
      {:else}
        <p class="empty">Aucune demande organisateur en attente.</p>
      {/if}
    </section>

    <section class="panel">
      <div class="panel-head">
        <span class="eyebrow">Utilisateurs</span>
        <h2>Gestion des comptes</h2>
      </div>

      {#if dashboard.users?.length}
        <div class="card-list">
          {#each dashboard.users as user (user.id)}
            <article class="card">
              <header class="card-head">
                <div>
                  <strong>{user.fullName}</strong>
                  <small>{user.email}</small>
                </div>
                <span class="status-chip">{getRoleProfile(user.roles || [])}</span>
              </header>

              <form class="grid-form" on:submit|preventDefault={() => handleUpdateUser(user.id)}>
                <label>
                  <span>Email</span>
                  <input bind:value={userDrafts[user.id].email} type="email" />
                </label>
                <label>
                  <span>Prenom</span>
                  <input bind:value={userDrafts[user.id].firstName} type="text" />
                </label>
                <label>
                  <span>Nom</span>
                  <input bind:value={userDrafts[user.id].lastName} type="text" />
                </label>
                <label>
                  <span>Role</span>
                  <select bind:value={userDrafts[user.id].roleProfile}>
                    <option value="ROLE_USER">Utilisateur</option>
                    <option value="ROLE_ORGANIZER">Organisateur</option>
                    <option value="ROLE_ADMIN">Admin</option>
                  </select>
                </label>

                <div class="meta-line">
                  {user.experienceCount} experiences · {user.bookingCount} reservations · {user.reviewCount} avis
                </div>

                <button class="primary" disabled={processingKey === `user-${user.id}`} type="submit">
                  {processingKey === `user-${user.id}` ? 'Enregistrement...' : 'Mettre a jour'}
                </button>
              </form>
            </article>
          {/each}
        </div>
      {:else}
        <p class="empty">Aucun utilisateur a afficher.</p>
      {/if}
    </section>

    <section class="panel">
      <div class="panel-head">
        <span class="eyebrow">Experiences</span>
        <h2>Gestion globale des experiences</h2>
      </div>

      {#if dashboard.experiences?.length}
        <div class="card-list">
          {#each dashboard.experiences as experience (experience.id)}
            <article class="card">
              <header class="card-head">
                <div>
                  <strong>{experience.title}</strong>
                  <small>{experience.organizer?.fullName || 'Sans organisateur'} - {formatPrice(experience.price)}</small>
                </div>
                <span class="status-chip">{experience.status}</span>
              </header>

              <form class="grid-form" on:submit|preventDefault={() => handleUpdateExperience(experience.id)}>
                <label>
                  <span>Titre</span>
                  <input bind:value={experienceDrafts[experience.id].title} type="text" />
                </label>
                <label>
                  <span>Lieu</span>
                  <input bind:value={experienceDrafts[experience.id].location} type="text" />
                </label>
                <label class="wide">
                  <span>Description</span>
                  <textarea bind:value={experienceDrafts[experience.id].description} rows="4"></textarea>
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

                <div class="meta-line">
                  {experience.slotCount} creneaux · {experience.reviewSummary?.count || 0} avis
                </div>

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
            </article>
          {/each}
        </div>
      {:else}
        <p class="empty">Aucune experience a moderer.</p>
      {/if}
    </section>

    <section class="panel">
      <div class="panel-head">
        <span class="eyebrow">Moderation</span>
        <h2>Avis publies</h2>
      </div>

      {#if dashboard.reviews?.length}
        <div class="card-list">
          {#each dashboard.reviews as review (review.id)}
            <article class="card">
              <header class="card-head">
                <div>
                  <strong>{review.experience?.title || 'Experience'}</strong>
                  <small>{review.author?.fullName || 'Participant'} - {formatDateTime(review.createdAt)}</small>
                </div>
                <span class="status-chip">{review.rating}/5</span>
              </header>

              <p>{review.comment}</p>

              <button
                class="secondary danger"
                disabled={processingKey === `review-${review.id}`}
                on:click={() => handleDeleteReview(review.id)}
                type="button"
              >
                {processingKey === `review-${review.id}` ? 'Suppression...' : 'Supprimer cet avis'}
              </button>
            </article>
          {/each}
        </div>
      {:else}
        <p class="empty">Aucun avis a moderer.</p>
      {/if}
    </section>
  </section>
{/if}

<style>
  .admin-shell {
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
  h2 {
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

  p {
    line-height: 1.75;
    color: #5f5146;
  }

  .panel-head {
    margin-bottom: 1rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.85rem;
    margin-top: 1.2rem;
  }

  .stat-card,
  .card {
    padding: 1rem;
    border-radius: 1.2rem;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .stat-card strong,
  .card strong {
    display: block;
    color: #24160e;
  }

  .stat-card strong {
    font-size: 1.8rem;
  }

  .card-list {
    display: grid;
    gap: 0.9rem;
  }

  .card-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
  }

  .status-chip {
    padding: 0.4rem 0.8rem;
    border-radius: 999px;
    background: rgba(243, 231, 220, 0.9);
    color: #6e513e;
    font-weight: 700;
  }

  .grid-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
    margin-top: 1rem;
  }

  .wide {
    grid-column: 1 / -1;
  }

  label {
    display: grid;
    gap: 0.4rem;
  }

  label span {
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

  .meta-line {
    grid-column: 1 / -1;
    color: #7a6555;
    font-size: 0.92rem;
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

  small {
    color: #7a6555;
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
    .grid-form {
      grid-template-columns: 1fr;
    }
  }
</style>
