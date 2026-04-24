<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { onDestroy, onMount } from 'svelte';
  import { authSession, clearAuthSession, getStoredAuthToken, updateAuthUser } from '$lib/auth/session';
  import {
    fetchCurrentUser,
    fetchOrganizerAddressSuggestions,
    requestOrganizerAccess
  } from '$lib/api/client';
  import { formatDateTime } from '$lib/utils/experience';

  const businessTypeOptions = [
    { value: 'INDIVIDUAL', label: 'Independant' },
    { value: 'COMPANY', label: 'Entreprise' },
    { value: 'ASSOCIATION', label: 'Association' },
    { value: 'COLLECTIVE', label: 'Collectif' },
    { value: 'OTHER', label: 'Autre' }
  ];

  const eventTypeOptions = [
    { value: 'WORKSHOP', label: 'Atelier' },
    { value: 'CULTURE', label: 'Culture' },
    { value: 'FOOD', label: 'Gastronomie' },
    { value: 'SPORT', label: 'Sport' },
    { value: 'WELLNESS', label: 'Bien-etre' },
    { value: 'FAMILY', label: 'Famille' },
    { value: 'NIGHTLIFE', label: 'Soiree' },
    { value: 'NATURE', label: 'Nature' },
    { value: 'OTHER', label: 'Autre' }
  ];

  let error = '';
  let isLoading = true;
  /** @type {Record<string, any> | null} */
  let currentUser = null;
  let organizerRequestMessage = '';
  let organizerRequestError = '';
  let isSubmittingOrganizerRequest = false;
  let isLoadingAddressSuggestions = false;
  let addressSuggestionError = '';
  let lastAddressSuggestionQuery = '';
  /** @type {any[]} */
  let addressSuggestions = [];
  /** @type {ReturnType<typeof setTimeout> | null} */
  let addressSuggestionTimeout = null;

  /** @type {{
   *   organizationName: string;
   *   phoneNumber: string;
   *   streetAddress: string;
   *   postalCode: string;
   *   city: string;
   *   country: string;
   *   businessType: string;
   *   eventTypes: string[];
   *   activityDescription: string;
   *   websiteUrl: string;
   *   socialLinks: string;
   *   siret: string;
   *   motivation: string;
   * }} */
  let organizerForm = createOrganizerForm();

  onMount(async () => {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    try {
      const userResponse = await fetchCurrentUser(token);

      if (userResponse.data && typeof userResponse.data === 'object') {
        updateAuthUser(/** @type {Record<string, unknown>} */ (userResponse.data));
      } else {
        throw new Error('La reponse utilisateur est incomplete.');
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

  onDestroy(() => {
    if (addressSuggestionTimeout) {
      clearTimeout(addressSuggestionTimeout);
    }
  });

  /**
   * @returns {{
   *   organizationName: string;
   *   phoneNumber: string;
   *   streetAddress: string;
   *   postalCode: string;
   *   city: string;
   *   country: string;
   *   businessType: string;
   *   eventTypes: string[];
   *   activityDescription: string;
   *   websiteUrl: string;
   *   socialLinks: string;
   *   siret: string;
   *   motivation: string;
   * }}
   */
  function createOrganizerForm() {
    return {
      organizationName: '',
      phoneNumber: '',
      streetAddress: '',
      postalCode: '',
      city: '',
      country: 'France',
      businessType: 'INDIVIDUAL',
      eventTypes: [],
      activityDescription: '',
      websiteUrl: '',
      socialLinks: '',
      siret: '',
      motivation: ''
    };
  }

  $: currentUser = /** @type {Record<string, any> | null} */ ($authSession.user);

  function isOrganizerUser() {
    return !!(
      currentUser &&
      Array.isArray(currentUser.roles) &&
      (currentUser.roles.includes('ROLE_ORGANIZER') || currentUser.roles.includes('ROLE_ADMIN'))
    );
  }

  function getOrganizerRequestStatus() {
    return currentUser?.organizerRequest?.status || null;
  }

  function getScreeningLabel() {
    return currentUser?.organizerRequest?.screening?.label || null;
  }

  function getScreeningSummary() {
    return Array.isArray(currentUser?.organizerRequest?.screening?.summary)
      ? currentUser.organizerRequest.screening.summary
      : [];
  }

  $: streetAddressQuery = organizerForm.streetAddress.trim();
  $: if (streetAddressQuery.length < 3) {
    addressSuggestions = [];
    addressSuggestionError = '';
    isLoadingAddressSuggestions = false;
    lastAddressSuggestionQuery = '';
    if (addressSuggestionTimeout) {
      clearTimeout(addressSuggestionTimeout);
      addressSuggestionTimeout = null;
    }
  } else if (streetAddressQuery !== lastAddressSuggestionQuery) {
    scheduleAddressSuggestions(streetAddressQuery);
  }

  /**
   * @param {string} eventType
   */
  function toggleEventType(eventType) {
    if (organizerForm.eventTypes.includes(eventType)) {
      organizerForm = {
        ...organizerForm,
        eventTypes: organizerForm.eventTypes.filter((value) => value !== eventType)
      };
      return;
    }

    organizerForm = {
      ...organizerForm,
      eventTypes: [...organizerForm.eventTypes, eventType]
    };
  }

  /**
   * @param {string} query
   */
  function scheduleAddressSuggestions(query) {
    if (addressSuggestionTimeout) {
      clearTimeout(addressSuggestionTimeout);
    }

    addressSuggestionTimeout = setTimeout(() => {
      void loadAddressSuggestions(query);
    }, 220);
  }

  /**
   * @param {string} query
   */
  async function loadAddressSuggestions(query) {
    const token = getStoredAuthToken();

    if (!token || query !== organizerForm.streetAddress.trim()) {
      return;
    }

    isLoadingAddressSuggestions = true;
    addressSuggestionError = '';
    lastAddressSuggestionQuery = query;

    try {
      const response = await fetchOrganizerAddressSuggestions(token, query);
      addressSuggestions = Array.isArray(response.data) ? response.data : [];

      const meta =
        response.meta && typeof response.meta === 'object'
          ? /** @type {Record<string, any>} */ (response.meta)
          : null;
      if (meta && meta.available === false && typeof meta.message === 'string') {
        addressSuggestionError = meta.message;
      }
    } catch (exception) {
      addressSuggestions = [];
      addressSuggestionError = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      isLoadingAddressSuggestions = false;
    }
  }

  /**
   * @param {Record<string, any>} suggestion
   */
  function applyAddressSuggestion(suggestion) {
    organizerForm = {
      ...organizerForm,
      streetAddress: suggestion.streetAddress || organizerForm.streetAddress,
      postalCode: suggestion.postalCode || organizerForm.postalCode,
      city: suggestion.city || organizerForm.city,
      country: suggestion.country || organizerForm.country
    };
    lastAddressSuggestionQuery = organizerForm.streetAddress.trim();
    addressSuggestions = [];
    addressSuggestionError = '';
  }

  async function handleOrganizerRequest() {
    const token = getStoredAuthToken();

    if (!token) {
      await goto(`${base}/login`);
      return;
    }

    isSubmittingOrganizerRequest = true;
    organizerRequestError = '';
    organizerRequestMessage = '';

    try {
      const response = await requestOrganizerAccess(token, organizerForm);
      const organizerRequest =
        response.data && typeof response.data === 'object'
          ? /** @type {Record<string, any>} */ (response.data)
          : null;

      const refreshedUserResponse = await fetchCurrentUser(token);
      if (refreshedUserResponse.data && typeof refreshedUserResponse.data === 'object') {
        updateAuthUser(/** @type {Record<string, unknown>} */ (refreshedUserResponse.data));
      }

      if (
        organizerRequest &&
        organizerRequest.status === 'REJECTED' &&
        organizerRequest.screening &&
        typeof organizerRequest.screening === 'object' &&
        Array.isArray(organizerRequest.screening.summary)
      ) {
        organizerRequestError = organizerRequest.screening.summary.join(' ');
      } else {
        organizerForm = createOrganizerForm();
        organizerRequestMessage = 'Votre demande organisateur a bien ete envoyee.';
      }
    } catch (exception) {
      organizerRequestError = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      isSubmittingOrganizerRequest = false;
    }
  }
</script>

<svelte:head>
  <title>MyExperiences | Demande organisateur</title>
</svelte:head>

{#if isLoading}
  <section class="status-panel">Chargement de votre dossier organisateur...</section>
{:else if error}
  <section class="status-panel error">{error}</section>
{:else if currentUser}
  <section class="request-shell">
    <div class="request-hero">
      <div>
        <span class="eyebrow">Organisateur</span>
        <h1>Dossier de demande organisateur</h1>
        <p>
          Renseignez ici votre structure, votre activite et les experiences que vous souhaitez proposer.
          L objectif est de faciliter la validation de votre acces organisateur.
        </p>
      </div>

      <a class="back-link" href={`${base}/account`}>Retour a mon compte</a>
    </div>

    {#if isOrganizerUser()}
      <section class="panel">
        <p class="empty">Votre compte dispose deja de l acces organisateur.</p>
      </section>
    {:else if getOrganizerRequestStatus() === 'PENDING'}
      <section class="panel">
        <span class="eyebrow soft">Demande en cours</span>
        <h2>Votre dossier est deja en attente</h2>

        <p class="empty">
          Demande envoyee le {formatDateTime(currentUser.organizerRequest?.createdAt)}. Un administrateur
          doit encore la valider.
        </p>

        {#if getScreeningLabel()}
          <p class="screening-line">
            Pre-tri automatique : <strong>{getScreeningLabel()}</strong>
          </p>
        {/if}

        {#if getScreeningSummary().length}
          <ul class="summary-list">
            {#each getScreeningSummary() as item}
              <li>{item}</li>
            {/each}
          </ul>
        {/if}

        <div class="request-summary">
          <div>
            <span>Structure</span>
            <strong>{currentUser.organizerRequest?.organizationName || 'En attente'}</strong>
          </div>
          <div>
            <span>Ville</span>
            <strong>{currentUser.organizerRequest?.city || 'En attente'}</strong>
          </div>
          <div>
            <span>Type</span>
            <strong>{currentUser.organizerRequest?.businessTypeLabel || 'En attente'}</strong>
          </div>
          <div>
            <span>Evenements</span>
            <strong>{Array.isArray(currentUser.organizerRequest?.eventTypeLabels) ? currentUser.organizerRequest.eventTypeLabels.join(', ') : 'En attente'}</strong>
          </div>
        </div>
      </section>
    {:else}
      <section class="panel">
        <span class="eyebrow soft">Formulaire</span>
        <h2>Completer votre dossier</h2>

        {#if organizerRequestError}
          <p class="inline-error">{organizerRequestError}</p>
        {/if}

        {#if organizerRequestMessage}
          <p class="inline-success">{organizerRequestMessage}</p>
        {/if}

        {#if getOrganizerRequestStatus() === 'REJECTED'}
          <p class="empty">
            Votre precedente demande a ete refusee. Vous pouvez en envoyer une nouvelle avec un
            dossier plus complet.
          </p>

          {#if getScreeningSummary().length}
            <ul class="summary-list">
              {#each getScreeningSummary() as item}
                <li>{item}</li>
              {/each}
            </ul>
          {/if}
        {/if}

        <form class="request-form" on:submit|preventDefault={handleOrganizerRequest}>
          <label>
            <span>Nom de structure ou nom public</span>
            <input bind:value={organizerForm.organizationName} type="text" />
          </label>

          <label>
            <span>Telephone</span>
            <input bind:value={organizerForm.phoneNumber} type="tel" />
          </label>

          <label>
            <span>SIRET</span>
            <input bind:value={organizerForm.siret} inputmode="numeric" maxlength="14" type="text" />
            <small>Verification automatique dans la base officielle des entreprises diffusibles.</small>
          </label>

          <label>
            <span>Type de structure</span>
            <select bind:value={organizerForm.businessType}>
              {#each businessTypeOptions as option}
                <option value={option.value}>{option.label}</option>
              {/each}
            </select>
          </label>

          <label class="full-width">
            <span>Adresse</span>
            <input bind:value={organizerForm.streetAddress} type="text" />
            <small>Suggestion automatique via le referentiel public d adresses francais.</small>

            {#if isLoadingAddressSuggestions}
              <div class="suggestion-box muted">Recherche d adresses...</div>
            {:else if addressSuggestions.length > 0}
              <div class="suggestion-box">
                {#each addressSuggestions as suggestion}
                  <button
                    class="suggestion-item"
                    on:click={() => applyAddressSuggestion(suggestion)}
                    type="button"
                  >
                    <strong>{suggestion.label}</strong>
                    <span>{suggestion.postalCode} {suggestion.city}</span>
                  </button>
                {/each}
              </div>
            {:else if addressSuggestionError}
              <div class="suggestion-box muted error-text">{addressSuggestionError}</div>
            {/if}
          </label>

          <label>
            <span>Code postal</span>
            <input bind:value={organizerForm.postalCode} type="text" />
          </label>

          <label>
            <span>Ville</span>
            <input bind:value={organizerForm.city} type="text" />
          </label>

          <label>
            <span>Pays</span>
            <input bind:value={organizerForm.country} type="text" />
          </label>

          <label>
            <span>Site web</span>
            <input bind:value={organizerForm.websiteUrl} placeholder="https://..." type="url" />
          </label>

          <label class="full-width">
            <span>Reseaux sociaux ou liens utiles</span>
            <input bind:value={organizerForm.socialLinks} placeholder="@instagram, LinkedIn, page Facebook..." type="text" />
          </label>

          <fieldset class="full-width checkbox-group">
            <legend>Types d evenements proposes</legend>
            <div class="checkbox-grid">
              {#each eventTypeOptions as option}
                <label class:selected={organizerForm.eventTypes.includes(option.value)} class="checkbox-card">
                  <input
                    checked={organizerForm.eventTypes.includes(option.value)}
                    on:change={() => toggleEventType(option.value)}
                    type="checkbox"
                  />
                  <span>{option.label}</span>
                </label>
              {/each}
            </div>
          </fieldset>

          <label class="full-width">
            <span>Description de l activite</span>
            <textarea bind:value={organizerForm.activityDescription} rows="5"></textarea>
          </label>

          <label class="full-width">
            <span>Motivation</span>
            <textarea bind:value={organizerForm.motivation} rows="5"></textarea>
          </label>

          <button class="primary-action full-width submit-button" disabled={isSubmittingOrganizerRequest} type="submit">
            {isSubmittingOrganizerRequest ? 'Envoi...' : 'Envoyer ma demande'}
          </button>
        </form>
      </section>
    {/if}
  </section>
{/if}

<style>
  .request-shell {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
  }

  .request-hero,
  .panel,
  .status-panel {
    padding: 1.5rem;
    border-radius: 1.8rem;
    background: rgba(255, 251, 246, 0.84);
    border: 1px solid rgba(112, 71, 45, 0.12);
    box-shadow: 0 24px 70px rgba(66, 40, 19, 0.08);
  }

  .request-hero {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 1rem;
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

  .back-link,
  .primary-action {
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
  }

  .back-link {
    background: rgba(243, 230, 217, 0.92);
    color: #734d36;
  }

  .primary-action {
    background: #8d5430;
    color: #fff9f1;
  }

  .request-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
    margin-top: 1rem;
  }

  .request-form label {
    display: grid;
    gap: 0.4rem;
  }

  .request-form span,
  .checkbox-group legend,
  .request-summary span {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #866854;
    font-weight: 700;
  }

  .request-form input,
  .request-form textarea,
  .request-form select {
    min-height: 3rem;
    padding: 0.8rem 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(143, 108, 82, 0.22);
    background: #fffdf9;
    color: #291d16;
    font: inherit;
  }

  .request-form textarea {
    min-height: 8rem;
    resize: vertical;
  }

  small {
    color: #7a6555;
    line-height: 1.45;
  }

  .full-width {
    grid-column: 1 / -1;
  }

  .checkbox-group {
    margin: 0;
    padding: 0;
    border: 0;
  }

  .checkbox-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.65rem;
    margin-top: 0.7rem;
  }

  .checkbox-card {
    display: flex;
    flex-direction: row-reverse;
    justify-content: space-between;
    align-items: center;
    gap: 0.6rem;
    min-height: 4.25rem;
    padding: 0.85rem 0.95rem;
    border-radius: 1rem;
    border: 1px solid rgba(143, 108, 82, 0.16);
    background: rgba(255, 255, 255, 0.82);
    cursor: pointer;
  }

  .checkbox-card.selected {
    border-color: rgba(141, 84, 48, 0.32);
    background: rgba(248, 238, 229, 0.94);
  }

  .checkbox-card input {
    width: 1rem;
    height: 1rem;
    min-height: auto;
    margin: 0;
    padding: 0;
    flex: 0 0 auto;
  }

  .checkbox-card span {
    font-size: 0.95rem;
    text-transform: none;
    letter-spacing: 0;
    color: #3a2920;
    line-height: 1.35;
  }

  .request-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
    margin-top: 1rem;
  }

  .request-summary div {
    padding: 0.95rem;
    border-radius: 1rem;
    background: rgba(247, 239, 229, 0.78);
  }

  .request-summary strong {
    display: block;
    margin-top: 0.35rem;
    color: #24160e;
  }

  .submit-button {
    margin-top: 0.2rem;
  }

  .suggestion-box {
    display: grid;
    gap: 0.45rem;
    padding: 0.7rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .suggestion-box.muted {
    color: #74675d;
  }

  .suggestion-item {
    display: grid;
    gap: 0.15rem;
    padding: 0.8rem 0.9rem;
    border: 0;
    border-radius: 0.9rem;
    text-align: left;
    background: rgba(247, 239, 229, 0.78);
    color: #2f231c;
    cursor: pointer;
    font: inherit;
  }

  .suggestion-item strong {
    color: #24160e;
  }

  .suggestion-item span {
    color: #6d5a4e;
    font-size: 0.92rem;
    text-transform: none;
    letter-spacing: 0;
    font-weight: 500;
  }

  .error-text {
    color: #9c2f20;
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
    margin: 1rem 0 0;
    padding: 1rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(143, 108, 82, 0.12);
  }

  .screening-line {
    margin: 0.9rem 0 0;
    color: #645549;
  }

  .summary-list {
    display: grid;
    gap: 0.55rem;
    margin: 0.85rem 0 0;
    padding-left: 1.2rem;
    color: #5f5146;
  }

  @media (max-width: 980px) {
    .request-hero {
      flex-direction: column;
      align-items: start;
    }

    .request-form,
    .checkbox-grid,
    .request-summary {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 640px) {
    .request-form,
    .checkbox-grid,
    .request-summary {
      grid-template-columns: 1fr;
    }
  }
</style>
