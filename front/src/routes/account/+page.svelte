<script>
  import { goto } from '$app/navigation';
  import { base } from '$app/paths';
  import { onMount } from 'svelte';
  import { authSession, clearAuthSession, getStoredAuthToken, updateAuthUser } from '$lib/auth/session';
  import { fetchCurrentUser, requestOrganizerAccess } from '$lib/api/client';
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

  function isAdminUser() {
    return !!(
      currentUser &&
      Array.isArray(currentUser.roles) &&
      currentUser.roles.includes('ROLE_ADMIN')
    );
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
      await requestOrganizerAccess(token, organizerForm);

      const refreshedUserResponse = await fetchCurrentUser(token);
      if (refreshedUserResponse.data && typeof refreshedUserResponse.data === 'object') {
        updateAuthUser(/** @type {Record<string, unknown>} */ (refreshedUserResponse.data));
      }

      organizerForm = createOrganizerForm();
      organizerRequestMessage = 'Votre demande organisateur a bien ete envoyee.';
    } catch (exception) {
      organizerRequestError = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    } finally {
      isSubmittingOrganizerRequest = false;
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
      <div>
        <span class="eyebrow">Mon compte</span>
        <h1>{currentUser.fullName || 'Compte MyExperiences'}</h1>
        <p>
          Retrouvez ici vos informations principales et votre acces organisateur. Les reservations
          et l activite sont maintenant centralisees dans <a href={`${base}/space`}>Mon espace</a>.
        </p>
      </div>

      <a class="space-link" href={`${base}/space`}>Ouvrir mon espace</a>
    </div>

    <div class="details-grid">
      <article class="panel">
        <span class="eyebrow soft">Profil</span>
        <h2>Informations du compte</h2>

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

      {#if isOrganizerUser()}
        <article class="panel">
          <span class="eyebrow soft">{isAdminUser() ? 'Administration' : 'Organisateur'}</span>
          <h2>{isAdminUser() ? 'Acces admin actif' : 'Acces organisateur actif'}</h2>

          <p>
            {#if isAdminUser()}
              Votre compte dispose des acces administrateur et organisateur. Vous pouvez gerer la
              plateforme, moderer les contenus et publier des experiences.
            {:else}
              Votre compte peut deja publier et gerer des experiences, tout en conservant vos usages
              classiques de participant.
            {/if}
          </p>

          <div class="action-stack">
            <a class="space-link" href={`${base}/space`}>Voir mon activite</a>
            <a class="secondary-link" href={`${base}/organizer`}>Ouvrir l espace organisateur</a>
            {#if isAdminUser()}
              <a class="secondary-link" href={`${base}/admin`}>Ouvrir l espace admin</a>
            {/if}
          </div>
        </article>
      {:else}
        <article class="panel wide-panel">
          <span class="eyebrow soft">Organisateur</span>
          <h2>Demande d acces organisateur</h2>

          <p class="intro-text">
            Completez votre dossier avec vos coordonnees, votre activite et les types d experiences
            que vous souhaitez proposer. L objectif est de faciliter la revue admin des demandes.
          </p>

          {#if organizerRequestError}
            <p class="inline-error">{organizerRequestError}</p>
          {/if}

          {#if organizerRequestMessage}
            <p class="inline-success">{organizerRequestMessage}</p>
          {/if}

          {#if getOrganizerRequestStatus() === 'PENDING'}
            <div class="request-summary">
              <p class="empty">
                Votre demande est en attente depuis {formatDateTime(currentUser.organizerRequest?.createdAt)}.
                Un administrateur doit encore la valider.
              </p>

              <dl>
                <div>
                  <dt>Structure</dt>
                  <dd>{currentUser.organizerRequest?.organizationName || 'En attente'}</dd>
                </div>
                <div>
                  <dt>Ville</dt>
                  <dd>{currentUser.organizerRequest?.city || 'En attente'}</dd>
                </div>
                <div>
                  <dt>Type</dt>
                  <dd>{currentUser.organizerRequest?.businessTypeLabel || 'En attente'}</dd>
                </div>
                <div>
                  <dt>Evenements</dt>
                  <dd>{Array.isArray(currentUser.organizerRequest?.eventTypeLabels) ? currentUser.organizerRequest.eventTypeLabels.join(', ') : 'En attente'}</dd>
                </div>
              </dl>
            </div>
          {:else if getOrganizerRequestStatus() === 'APPROVED'}
            <p class="empty">Votre demande a ete approuvee. Reconnectez-vous si l acces n apparait pas encore partout.</p>
          {:else}
            {#if getOrganizerRequestStatus() === 'REJECTED'}
              <p class="empty">
                Votre precedente demande a ete refusee. Vous pouvez en envoyer une nouvelle avec un
                dossier plus complet.
              </p>
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
          {/if}
        </article>
      {/if}
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
    background: rgba(255, 251, 246, 0.84);
    border: 1px solid rgba(112, 71, 45, 0.12);
    box-shadow: 0 24px 70px rgba(66, 40, 19, 0.08);
  }

  .account-hero {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 1rem;
  }

  .details-grid {
    display: grid;
    grid-template-columns: minmax(280px, 0.9fr) minmax(0, 1.4fr);
    gap: 1rem;
  }

  .wide-panel {
    min-width: 0;
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

  p a {
    color: #8a4326;
    font-weight: 700;
  }

  .intro-text {
    margin-top: 0.9rem;
  }

  .space-link,
  .primary-action,
  .secondary-link {
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

  .space-link,
  .primary-action {
    background: #8d5430;
    color: #fff9f1;
  }

  .secondary-link {
    background: rgba(243, 230, 217, 0.92);
    color: #734d36;
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
    line-height: 1.55;
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
  .checkbox-group legend {
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
    align-items: center;
    gap: 0.6rem;
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
    min-height: auto;
    margin: 0;
    padding: 0;
  }

  .checkbox-card span {
    font-size: 0.95rem;
    text-transform: none;
    letter-spacing: 0;
    color: #3a2920;
  }

  .submit-button {
    margin-top: 0.2rem;
  }

  .action-stack {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 1.1rem;
  }

  .request-summary {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
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

  @media (max-width: 980px) {
    .account-hero,
    .details-grid {
      grid-template-columns: 1fr;
    }

    .account-hero {
      flex-direction: column;
      align-items: start;
    }

    .request-form,
    .checkbox-grid {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (max-width: 640px) {
    .request-form,
    .checkbox-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
