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
  let adminSection = 'requests';
  let requestStatusFilter = 'PENDING';
  let requestScreeningFilter = 'ALL';
  let adminSearch = '';
  let userSearch = '';
  let userRoleFilter = 'ALL';
  let experienceSearch = '';
  let experienceStatusFilter = 'ALL';
  let reviewSearch = '';
  let reviewRatingFilter = 'ALL';
  let requestPage = 1;
  let userPage = 1;
  let experiencePage = 1;
  let reviewPage = 1;
  /** @type {Record<number, boolean>} */
  let expandedOrganizerRequests = {};

  const REQUESTS_PER_PAGE = 5;
  const USERS_PER_PAGE = 5;
  const EXPERIENCES_PER_PAGE = 3;
  const REVIEWS_PER_PAGE = 5;

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
   * @param {any[]} checks
   * @param {string} status
   */
  function countChecksByStatus(checks, status) {
    return Array.isArray(checks)
      ? checks.filter((check) => check && check.status === status).length
      : 0;
  }

  /**
   * @param {Record<string, any>} organizerRequest
   */
  function getRequestDecisionLabel(organizerRequest) {
    if (organizerRequest.status === 'APPROVED') {
      return 'Approuvee';
    }

    if (organizerRequest.status === 'REJECTED') {
      return organizerRequest.screening?.isAutoRejected ? 'Refus automatique' : 'Refusee';
    }

    if (organizerRequest.screening?.status === 'PRE_VALIDATED') {
      return 'Priorite validation';
    }

    if (organizerRequest.screening?.status === 'AUTO_REJECTED') {
      return 'Refus automatique';
    }

    return 'A verifier';
  }

  /**
   * @param {Record<string, any>} organizerRequest
   */
  function matchesRequestFilters(organizerRequest) {
    const statusMatches =
      requestStatusFilter === 'ALL' || organizerRequest.status === requestStatusFilter;
    const screeningMatches =
      requestScreeningFilter === 'ALL' || organizerRequest.screening?.status === requestScreeningFilter;
    const query = adminSearch.trim().toLowerCase();

    if (!statusMatches || !screeningMatches) {
      return false;
    }

    if (!query) {
      return true;
    }

    const haystack = [
      organizerRequest.organizationName,
      organizerRequest.user?.fullName,
      organizerRequest.user?.email,
      organizerRequest.city,
      organizerRequest.postalCode,
      organizerRequest.siret,
      organizerRequest.businessTypeLabel,
      ...(Array.isArray(organizerRequest.eventTypeLabels) ? organizerRequest.eventTypeLabels : [])
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();

    return haystack.includes(query);
  }

  /**
   * @param {Record<string, any>} user
   */
  function matchesUserFilters(user) {
    const roleMatches = userRoleFilter === 'ALL' || getRoleProfile(user.roles || []) === userRoleFilter;
    const query = userSearch.trim().toLowerCase();

    if (!roleMatches) {
      return false;
    }

    if (!query) {
      return true;
    }

    return [user.fullName, user.email, user.firstName, user.lastName]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query);
  }

  /**
   * @param {Record<string, any>} experience
   */
  function matchesExperienceFilters(experience) {
    const statusMatches =
      experienceStatusFilter === 'ALL' || experience.status === experienceStatusFilter;
    const query = experienceSearch.trim().toLowerCase();

    if (!statusMatches) {
      return false;
    }

    if (!query) {
      return true;
    }

    return [
      experience.title,
      experience.location,
      experience.organizer?.fullName,
      experience.organizer?.email,
      experience.description
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query);
  }

  /**
   * @param {Record<string, any>} review
   */
  function matchesReviewFilters(review) {
    const ratingMatches =
      reviewRatingFilter === 'ALL' || Number(review.rating) === Number(reviewRatingFilter);
    const query = reviewSearch.trim().toLowerCase();

    if (!ratingMatches) {
      return false;
    }

    if (!query) {
      return true;
    }

    return [review.comment, review.author?.fullName, review.author?.email, review.experience?.title]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query);
  }

  /**
   * @param {any[]} items
   * @param {number} page
   * @param {number} pageSize
   */
  function paginate(items, page, pageSize) {
    return items.slice((page - 1) * pageSize, page * pageSize);
  }

  /**
   * @param {number} total
   * @param {number} pageSize
   */
  function pageCount(total, pageSize) {
    return Math.max(1, Math.ceil(total / pageSize));
  }

  /**
   * @param {number} requestId
   */
  function toggleOrganizerRequest(requestId) {
    expandedOrganizerRequests = {
      ...expandedOrganizerRequests,
      [requestId]: !expandedOrganizerRequests[requestId]
    };
  }

  $: organizerRequests = Array.isArray(dashboard?.organizerRequests)
    ? dashboard.organizerRequests
    : [];
  $: filteredOrganizerRequests = organizerRequests
    .filter((organizerRequest) => {
      requestStatusFilter;
      requestScreeningFilter;
      adminSearch;
      return matchesRequestFilters(organizerRequest);
    });
  $: requestTotalPages = pageCount(filteredOrganizerRequests.length, REQUESTS_PER_PAGE);
  $: if (requestPage > requestTotalPages) {
    requestPage = requestTotalPages;
  }
  $: paginatedOrganizerRequests = paginate(filteredOrganizerRequests, requestPage, REQUESTS_PER_PAGE);
  $: pendingOrganizerRequests = organizerRequests.filter((request) => request.status === 'PENDING');
  $: criticalOrganizerRequests = pendingOrganizerRequests.filter(
    (request) => request.screening?.status === 'PRE_VALIDATED' || request.screening?.status === 'AUTO_REJECTED'
  );
  $: users = Array.isArray(dashboard?.users) ? dashboard.users : [];
  $: filteredUsers = users.filter((user) => {
    userRoleFilter;
    userSearch;
    return matchesUserFilters(user);
  });
  $: userTotalPages = pageCount(filteredUsers.length, USERS_PER_PAGE);
  $: if (userPage > userTotalPages) {
    userPage = userTotalPages;
  }
  $: paginatedUsers = paginate(filteredUsers, userPage, USERS_PER_PAGE);
  $: experiences = Array.isArray(dashboard?.experiences) ? dashboard.experiences : [];
  $: filteredExperiences = experiences.filter((experience) => {
    experienceStatusFilter;
    experienceSearch;
    return matchesExperienceFilters(experience);
  });
  $: experienceTotalPages = pageCount(filteredExperiences.length, EXPERIENCES_PER_PAGE);
  $: if (experiencePage > experienceTotalPages) {
    experiencePage = experienceTotalPages;
  }
  $: paginatedExperiences = paginate(filteredExperiences, experiencePage, EXPERIENCES_PER_PAGE);
  $: reviews = Array.isArray(dashboard?.reviews) ? dashboard.reviews : [];
  $: filteredReviews = reviews.filter((review) => {
    reviewRatingFilter;
    reviewSearch;
    return matchesReviewFilters(review);
  });
  $: reviewTotalPages = pageCount(filteredReviews.length, REVIEWS_PER_PAGE);
  $: if (reviewPage > reviewTotalPages) {
    reviewPage = reviewTotalPages;
  }
  $: paginatedReviews = paginate(filteredReviews, reviewPage, REVIEWS_PER_PAGE);
  $: requestStatusFilter, requestScreeningFilter, adminSearch, (requestPage = 1);
  $: userRoleFilter, userSearch, (userPage = 1);
  $: experienceStatusFilter, experienceSearch, (experiencePage = 1);
  $: reviewRatingFilter, reviewSearch, (reviewPage = 1);

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
          <span>Demandes en attente</span>
        </article>
        <article class="stat-card accent">
          <strong>{dashboard.stats?.preValidatedOrganizerRequestCount || 0}</strong>
          <span>Pre-validees</span>
        </article>
      </div>
    </div>

    {#if error}
      <p class="inline-error">{error}</p>
    {/if}

    {#if success}
      <p class="inline-success">{success}</p>
    {/if}

    <nav class="section-tabs" aria-label="Sections admin">
      <button class:active={adminSection === 'requests'} on:click={() => (adminSection = 'requests')} type="button">
        Demandes
        <span>{dashboard.stats?.pendingOrganizerRequestCount || 0}</span>
      </button>
      <button class:active={adminSection === 'users'} on:click={() => (adminSection = 'users')} type="button">
        Utilisateurs
        <span>{dashboard.stats?.userCount || 0}</span>
      </button>
      <button class:active={adminSection === 'experiences'} on:click={() => (adminSection = 'experiences')} type="button">
        Experiences
        <span>{dashboard.stats?.experienceCount || 0}</span>
      </button>
      <button class:active={adminSection === 'reviews'} on:click={() => (adminSection = 'reviews')} type="button">
        Avis
        <span>{dashboard.stats?.reviewCount || 0}</span>
      </button>
    </nav>

    {#if adminSection === 'requests'}
    <section class="panel">
      <div class="panel-head">
        <div>
          <span class="eyebrow">Demandes</span>
          <h2>File organisateur</h2>
        </div>
        <div class="panel-metrics">
          <div>
            <strong>{criticalOrganizerRequests.length}</strong>
            <span>A traiter vite</span>
          </div>
          <div>
            <strong>{dashboard.stats?.needsReviewOrganizerRequestCount || 0}</strong>
            <span>A revoir</span>
          </div>
          <div>
            <strong>{dashboard.stats?.autoRejectedOrganizerRequestCount || 0}</strong>
            <span>Refus auto</span>
          </div>
        </div>
      </div>

      <div class="admin-toolbar">
        <label>
          <span>Recherche</span>
          <input bind:value={adminSearch} placeholder="Nom, email, ville, SIRET..." type="search" />
        </label>
        <label>
          <span>Statut dossier</span>
          <select bind:value={requestStatusFilter}>
            <option value="ALL">Tous</option>
            <option value="PENDING">En attente</option>
            <option value="APPROVED">Approuves</option>
            <option value="REJECTED">Refuses</option>
          </select>
        </label>
        <label>
          <span>Pre-tri</span>
          <select bind:value={requestScreeningFilter}>
            <option value="ALL">Tous</option>
            <option value="PRE_VALIDATED">Pre-valides</option>
            <option value="NEEDS_REVIEW">A revoir</option>
            <option value="AUTO_REJECTED">Refus auto</option>
          </select>
        </label>
      </div>

      {#if organizerRequests.length}
        {#if filteredOrganizerRequests.length}
        <div class="card-list">
          {#each paginatedOrganizerRequests as organizerRequest (organizerRequest.id)}
            <article class="card">
              <header class="card-head">
                <div>
                  <strong>{organizerRequest.user?.fullName || 'Utilisateur'}</strong>
                  <small>{organizerRequest.user?.email} - {formatDateTime(organizerRequest.createdAt)}</small>
                </div>
                <div class="chip-stack">
                  <span class="status-chip">{organizerRequest.status}</span>
                  {#if organizerRequest.screening?.label}
                    <span class="status-chip screening">{organizerRequest.screening.label}</span>
                  {/if}
                  <span class="status-chip decision">{getRequestDecisionLabel(organizerRequest)}</span>
                </div>
              </header>

              <div class="decision-strip">
                <div>
                  <strong>{countChecksByStatus(organizerRequest.screening?.checks, 'passed')}</strong>
                  <span>valides</span>
                </div>
                <div>
                  <strong>{countChecksByStatus(organizerRequest.screening?.checks, 'warning')}</strong>
                  <span>alertes</span>
                </div>
                <div>
                  <strong>{countChecksByStatus(organizerRequest.screening?.checks, 'failed')}</strong>
                  <span>bloquants</span>
                </div>
              </div>

              <div class="request-overview">
                <div>
                  <span>Structure</span>
                  <strong>{organizerRequest.organizationName}</strong>
                </div>
                <div>
                  <span>Telephone</span>
                  <strong>{organizerRequest.phoneNumber}</strong>
                </div>
                <div>
                  <span>Ville</span>
                  <strong>{organizerRequest.city}, {organizerRequest.postalCode}</strong>
                </div>
                <div>
                  <span>Type</span>
                  <strong>{organizerRequest.businessTypeLabel || organizerRequest.businessType}</strong>
                </div>
                <div>
                  <span>SIRET</span>
                  <strong>{organizerRequest.siret}</strong>
                </div>
                <div>
                  <span>Evenements</span>
                  <strong>{Array.isArray(organizerRequest.eventTypeLabels) ? organizerRequest.eventTypeLabels.slice(0, 2).join(', ') : 'Non renseigne'}</strong>
                </div>
              </div>

              <button
                class="link-button"
                on:click={() => toggleOrganizerRequest(organizerRequest.id)}
                type="button"
              >
                {expandedOrganizerRequests[organizerRequest.id] ? 'Voir moins' : 'Voir plus'}
              </button>

              {#if expandedOrganizerRequests[organizerRequest.id]}
              <p class="address-line">
                {organizerRequest.streetAddress}, {organizerRequest.postalCode} {organizerRequest.city}, {organizerRequest.country}
              </p>

              {#if organizerRequest.eventTypeLabels?.length}
                <div class="tag-row">
                  {#each organizerRequest.eventTypeLabels as eventTypeLabel}
                    <span class="tag">{eventTypeLabel}</span>
                  {/each}
                </div>
              {/if}

              <div class="request-copy">
                {#if organizerRequest.screening?.summary?.length}
                  <div>
                    <span>Pre-tri automatique</span>
                    <ul class="summary-list">
                      {#each organizerRequest.screening.summary as item}
                        <li>{item}</li>
                      {/each}
                    </ul>
                  </div>
                {/if}

                {#if organizerRequest.screening?.checks?.length}
                  <div>
                    <span>Controles</span>
                    <div class="check-list">
                      {#each organizerRequest.screening.checks as check}
                        <div class={`check-item ${check.status || 'warning'}`}>
                          <strong>{check.label}</strong>
                          <p>{check.message}</p>
                        </div>
                      {/each}
                    </div>
                  </div>
                {/if}

                <div>
                  <span>Description d activite</span>
                  <p>{organizerRequest.activityDescription}</p>
                </div>

                <div>
                  <span>Motivation</span>
                  <p>{organizerRequest.motivation}</p>
                </div>

                {#if organizerRequest.socialLinks}
                  <div>
                    <span>Liens utiles</span>
                    <p>{organizerRequest.socialLinks}</p>
                  </div>
                {/if}

                <div>
                  <span>Site web</span>
                  <p>{organizerRequest.websiteUrl || 'Non renseigne'}</p>
                </div>
              </div>
              {/if}

              <div class="action-row">
                {#if organizerRequest.status === 'PENDING'}
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
                {:else}
                  <span class="processed-note">
                    Dossier traite {organizerRequest.processedAt ? `le ${formatDateTime(organizerRequest.processedAt)}` : ''}
                    {organizerRequest.reviewedBy?.fullName ? ` par ${organizerRequest.reviewedBy.fullName}` : ''}
                  </span>
                {/if}
              </div>
            </article>
          {/each}
        </div>
        <div class="pagination">
          <span>{filteredOrganizerRequests.length} demande{filteredOrganizerRequests.length > 1 ? 's' : ''}</span>
          <div>
            <button class="secondary" disabled={requestPage === 1} on:click={() => (requestPage -= 1)} type="button">Precedent</button>
            <span>Page {requestPage} / {requestTotalPages}</span>
            <button class="secondary" disabled={requestPage === requestTotalPages} on:click={() => (requestPage += 1)} type="button">Suivant</button>
          </div>
        </div>
        {:else}
          <p class="empty">Aucune demande ne correspond aux filtres actuels.</p>
        {/if}
      {:else}
        <p class="empty">Aucune demande organisateur a afficher.</p>
      {/if}
    </section>
    {/if}

    {#if adminSection === 'users'}
    <section class="panel">
      <div class="panel-head">
        <span class="eyebrow">Utilisateurs</span>
        <h2>Gestion des comptes</h2>
      </div>

      <div class="admin-toolbar two-columns">
        <label>
          <span>Recherche</span>
          <input bind:value={userSearch} placeholder="Nom, email..." type="search" />
        </label>
        <label>
          <span>Role</span>
          <select bind:value={userRoleFilter}>
            <option value="ALL">Tous</option>
            <option value="ROLE_USER">Utilisateurs</option>
            <option value="ROLE_ORGANIZER">Organisateurs</option>
            <option value="ROLE_ADMIN">Admins</option>
          </select>
        </label>
      </div>

      {#if users.length}
        {#if filteredUsers.length}
        <div class="card-list">
          {#each paginatedUsers as user (user.id)}
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
                  {user.experienceCount} experiences - {user.bookingCount} reservations - {user.reviewCount} avis
                </div>

                <button class="primary" disabled={processingKey === `user-${user.id}`} type="submit">
                  {processingKey === `user-${user.id}` ? 'Enregistrement...' : 'Mettre a jour'}
                </button>
              </form>
            </article>
          {/each}
        </div>
        <div class="pagination">
          <span>{filteredUsers.length} compte{filteredUsers.length > 1 ? 's' : ''}</span>
          <div>
            <button class="secondary" disabled={userPage === 1} on:click={() => (userPage -= 1)} type="button">Precedent</button>
            <span>Page {userPage} / {userTotalPages}</span>
            <button class="secondary" disabled={userPage === userTotalPages} on:click={() => (userPage += 1)} type="button">Suivant</button>
          </div>
        </div>
        {:else}
          <p class="empty">Aucun utilisateur ne correspond aux filtres actuels.</p>
        {/if}
      {:else}
        <p class="empty">Aucun utilisateur a afficher.</p>
      {/if}
    </section>
    {/if}

    {#if adminSection === 'experiences'}
    <section class="panel">
      <div class="panel-head">
        <span class="eyebrow">Experiences</span>
        <h2>Gestion globale des experiences</h2>
      </div>

      <div class="admin-toolbar two-columns">
        <label>
          <span>Recherche</span>
          <input bind:value={experienceSearch} placeholder="Titre, lieu, organisateur..." type="search" />
        </label>
        <label>
          <span>Statut</span>
          <select bind:value={experienceStatusFilter}>
            <option value="ALL">Tous</option>
            <option value="DRAFT">Brouillons</option>
            <option value="PUBLISHED">Publiees</option>
            <option value="ARCHIVED">Archivees</option>
          </select>
        </label>
      </div>

      {#if experiences.length}
        {#if filteredExperiences.length}
        <div class="card-list">
          {#each paginatedExperiences as experience (experience.id)}
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
                  {experience.slotCount} creneaux - {experience.reviewSummary?.count || 0} avis
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
        <div class="pagination">
          <span>{filteredExperiences.length} experience{filteredExperiences.length > 1 ? 's' : ''}</span>
          <div>
            <button class="secondary" disabled={experiencePage === 1} on:click={() => (experiencePage -= 1)} type="button">Precedent</button>
            <span>Page {experiencePage} / {experienceTotalPages}</span>
            <button class="secondary" disabled={experiencePage === experienceTotalPages} on:click={() => (experiencePage += 1)} type="button">Suivant</button>
          </div>
        </div>
        {:else}
          <p class="empty">Aucune experience ne correspond aux filtres actuels.</p>
        {/if}
      {:else}
        <p class="empty">Aucune experience a moderer.</p>
      {/if}
    </section>
    {/if}

    {#if adminSection === 'reviews'}
    <section class="panel">
      <div class="panel-head">
        <span class="eyebrow">Moderation</span>
        <h2>Avis publies</h2>
      </div>

      <div class="admin-toolbar two-columns">
        <label>
          <span>Recherche</span>
          <input bind:value={reviewSearch} placeholder="Commentaire, auteur, experience..." type="search" />
        </label>
        <label>
          <span>Note</span>
          <select bind:value={reviewRatingFilter}>
            <option value="ALL">Toutes</option>
            <option value="5">5/5</option>
            <option value="4">4/5</option>
            <option value="3">3/5</option>
            <option value="2">2/5</option>
            <option value="1">1/5</option>
          </select>
        </label>
      </div>

      {#if reviews.length}
        {#if filteredReviews.length}
        <div class="card-list">
          {#each paginatedReviews as review (review.id)}
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
        <div class="pagination">
          <span>{filteredReviews.length} avis</span>
          <div>
            <button class="secondary" disabled={reviewPage === 1} on:click={() => (reviewPage -= 1)} type="button">Precedent</button>
            <span>Page {reviewPage} / {reviewTotalPages}</span>
            <button class="secondary" disabled={reviewPage === reviewTotalPages} on:click={() => (reviewPage += 1)} type="button">Suivant</button>
          </div>
        </div>
        {:else}
          <p class="empty">Aucun avis ne correspond aux filtres actuels.</p>
        {/if}
      {:else}
        <p class="empty">Aucun avis a moderer.</p>
      {/if}
    </section>
    {/if}
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
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
    margin-bottom: 1rem;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
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

  .stat-card.accent {
    background: rgba(235, 246, 241, 0.86);
    border-color: rgba(31, 126, 92, 0.16);
  }

  .section-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    padding: 0.65rem;
    border-radius: 1.4rem;
    background: rgba(255, 252, 248, 0.88);
    border: 1px solid rgba(139, 95, 61, 0.12);
    box-shadow: 0 14px 45px rgba(88, 54, 30, 0.06);
  }

  .section-tabs button {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    min-height: 2.7rem;
    padding: 0.65rem 0.9rem;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #604c3f;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
  }

  .section-tabs button.active {
    background: #8d5430;
    color: #fff9f1;
  }

  .section-tabs span {
    display: inline-grid;
    place-items: center;
    min-width: 1.7rem;
    min-height: 1.7rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.35);
  }

  .panel-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(5rem, 1fr));
    gap: 0.55rem;
    min-width: min(100%, 24rem);
  }

  .panel-metrics div {
    padding: 0.8rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .panel-metrics strong {
    display: block;
    color: #24160e;
    font-size: 1.35rem;
  }

  .panel-metrics span {
    color: #7a6555;
    font-size: 0.86rem;
  }

  .admin-toolbar {
    display: grid;
    grid-template-columns: minmax(16rem, 1.5fr) minmax(11rem, 0.8fr) minmax(11rem, 0.8fr);
    gap: 0.8rem;
    margin-bottom: 1rem;
    padding: 0.9rem;
    border-radius: 1.2rem;
    background: rgba(247, 239, 229, 0.58);
  }

  .admin-toolbar.two-columns {
    grid-template-columns: minmax(16rem, 1.5fr) minmax(11rem, 0.8fr);
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

  .chip-stack {
    display: flex;
    flex-wrap: wrap;
    justify-content: end;
    gap: 0.45rem;
  }

  .request-overview {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
  }

  .request-overview div,
  .request-copy div {
    padding: 0.9rem;
    border-radius: 1rem;
    background: rgba(247, 239, 229, 0.78);
  }

  .request-overview span,
  .request-copy span {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #7d746c;
  }

  .address-line {
    margin-top: 0.9rem;
  }

  .tag-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
    margin-top: 0.9rem;
  }

  .tag {
    display: inline-flex;
    align-items: center;
    min-height: 2rem;
    padding: 0.38rem 0.75rem;
    border-radius: 999px;
    background: rgba(230, 205, 180, 0.32);
    color: #7b604d;
    font-weight: 700;
  }

  .link-button {
    display: inline-flex;
    margin: 1rem 0 0.65rem;
    padding: 0;
    border: 0;
    background: transparent;
    color: #8d5430;
    font: inherit;
    font-weight: 800;
    cursor: pointer;
  }

  .request-copy {
    display: grid;
    gap: 0.75rem;
    margin-top: 0.9rem;
  }

  .status-chip {
    padding: 0.4rem 0.8rem;
    border-radius: 999px;
    background: rgba(243, 231, 220, 0.9);
    color: #6e513e;
    font-weight: 700;
  }

  .status-chip.screening {
    background: rgba(233, 238, 246, 0.9);
    color: #44576f;
  }

  .status-chip.decision {
    background: rgba(236, 247, 240, 0.9);
    color: #2f6e58;
  }

  .decision-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.6rem;
    margin-top: 1rem;
  }

  .decision-strip div {
    padding: 0.8rem 0.9rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .decision-strip strong {
    display: block;
    color: #24160e;
    font-size: 1.35rem;
  }

  .decision-strip span {
    color: #7a6555;
    font-size: 0.9rem;
  }

  .check-list,
  .summary-list {
    display: grid;
    gap: 0.65rem;
    margin-top: 0.65rem;
  }

  .summary-list {
    margin-bottom: 0;
    padding-left: 1.1rem;
  }

  .check-item {
    padding: 0.8rem 0.9rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.72);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .check-item strong {
    display: block;
    margin-bottom: 0.3rem;
  }

  .check-item p {
    margin: 0;
  }

  .check-item.passed {
    background: rgba(236, 247, 240, 0.88);
    border-color: rgba(31, 126, 92, 0.16);
  }

  .check-item.warning {
    background: rgba(255, 249, 237, 0.92);
    border-color: rgba(171, 121, 38, 0.16);
  }

  .check-item.failed {
    background: rgba(255, 244, 241, 0.92);
    border-color: rgba(156, 47, 32, 0.16);
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
    align-items: center;
    margin-top: 0.9rem;
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

  .processed-note {
    color: #6f5b4d;
    font-weight: 700;
  }

  .pagination {
    display: flex;
    justify-content: space-between;
    gap: 0.9rem;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 1rem;
    padding: 0.8rem 0.95rem;
    border-radius: 1rem;
    background: rgba(247, 239, 229, 0.58);
    color: #6f5b4d;
    font-weight: 700;
  }

  .pagination div {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    flex-wrap: wrap;
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
    .grid-form,
    .admin-toolbar,
    .admin-toolbar.two-columns,
    .panel-metrics {
      grid-template-columns: 1fr;
    }

    .panel-head {
      flex-direction: column;
    }

    .request-overview {
      grid-template-columns: 1fr;
    }
  }
</style>
