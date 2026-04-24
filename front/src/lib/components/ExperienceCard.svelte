<script>
  import { base } from '$app/paths';
  import { formatDateTime, formatDuration, formatPrice } from '$lib/utils/experience';

  /** @type {any} */
  export let experience;
  export let showSummary = true;

  $: reviewCount = Number(experience?.reviewSummary?.count || 0);
  $: reviewAverage = experience?.reviewSummary?.averageRating || '0.0';
  $: distanceLabel = experience?.distanceKm != null ? `${experience.distanceKm.toFixed(1)} km` : null;
</script>

<a class="card" href={`${base}/experiences/${experience.id}`}>
  <div class="card-image">
    <div class="card-overlay">
      <span class="location">{experience.location}</span>
      <div class="overlay-side">
        {#if distanceLabel}
          <span class="distance">{distanceLabel}</span>
        {/if}
        <span class:bookable={experience.booking?.isBookable} class="availability">
          {experience.booking?.isBookable ? 'Reservable' : 'A surveiller'}
        </span>
      </div>
    </div>
  </div>

  <div class="card-body">
    <div class="headline">
      <h3>{experience.title}</h3>
      <span class="price">{formatPrice(experience.price)}</span>
    </div>

    {#if showSummary && experience.summary}
      <p>{experience.summary}</p>
    {/if}

    <dl class="facts">
      <div>
        <dt>Duree</dt>
        <dd>{formatDuration(experience.durationMinutes)}</dd>
      </div>
      <div>
        <dt>Prochain creneau</dt>
        <dd>{formatDateTime(experience.booking?.nextStartAt)}</dd>
      </div>
    </dl>

    <div class="footer">
      <span class="review-pill">
        {#if reviewCount > 0}
          {reviewAverage}/5 - {reviewCount} avis
        {:else}
          Pas encore d avis
        {/if}
      </span>
      <span class="cta">Voir le detail</span>
    </div>
  </div>
</a>

<style>
  .card {
    display: grid;
    grid-template-rows: auto 1fr;
    gap: 0;
    height: 100%;
    border-radius: 1.65rem;
    overflow: hidden;
    text-decoration: none;
    background: linear-gradient(180deg, rgba(255, 251, 246, 0.96), rgba(255, 255, 255, 0.92));
    border: 1px solid rgba(117, 74, 39, 0.12);
    box-shadow: 0 20px 50px rgba(88, 54, 30, 0.08);
    color: inherit;
    transition:
      transform 180ms ease,
      box-shadow 180ms ease,
      border-color 180ms ease;
  }

  .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 28px 60px rgba(88, 54, 30, 0.14);
    border-color: rgba(195, 120, 66, 0.28);
  }

  .card-image {
    min-height: 9.8rem;
    padding: 1rem;
    background:
      radial-gradient(circle at 22% 18%, rgba(255, 209, 175, 0.45), transparent 22%),
      radial-gradient(circle at 82% 26%, rgba(169, 226, 205, 0.4), transparent 20%),
      linear-gradient(135deg, #4e3328 0%, #8e5535 48%, #d28b53 100%);
  }

  .card-overlay {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: start;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  .overlay-side {
    display: grid;
    justify-items: end;
    gap: 0.45rem;
  }

  .location,
  .availability,
  .distance {
    padding: 0.42rem 0.78rem;
    border-radius: 999px;
    font-weight: 700;
  }

  .location {
    background: rgba(255, 248, 241, 0.18);
    color: #fff7ef;
  }

  .availability {
    background: rgba(255, 245, 236, 0.9);
    color: #694f3d;
  }

  .distance {
    background: rgba(255, 251, 246, 0.86);
    color: #6b4c39;
  }

  .availability.bookable {
    background: rgba(223, 247, 239, 0.94);
    color: #1f7e5c;
  }

  .card-body {
    display: grid;
    grid-template-rows: auto auto auto 1fr;
    gap: 1rem;
    padding: 1.25rem;
  }

  .headline {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: start;
  }

  h3 {
    margin: 0;
    font-family: 'Constantia', Georgia, serif;
    font-size: clamp(1.35rem, 2vw, 1.72rem);
    line-height: 1.1;
    color: #24160e;
    min-height: 3.7rem;
  }

  .price {
    white-space: nowrap;
    color: #9a4e2b;
    font-weight: 800;
  }

  p {
    margin: 0;
    color: #5e5147;
    line-height: 1.68;
    min-height: 5rem;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    line-clamp: 3;
  }

  .facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.8rem;
    margin: 0;
  }

  .facts div {
    padding: 0.9rem;
    border-radius: 1rem;
    background: rgba(247, 239, 229, 0.8);
  }

  dt {
    margin-bottom: 0.32rem;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #8c715e;
  }

  dd {
    margin: 0;
    color: #2b211b;
    font-weight: 700;
    line-height: 1.45;
  }

  .footer {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
    align-self: end;
  }

  .review-pill {
    padding: 0.48rem 0.8rem;
    border-radius: 999px;
    background: rgba(230, 205, 180, 0.28);
    color: #724f38;
    font-weight: 700;
  }

  .cta {
    color: #9a4e2b;
    font-weight: 800;
  }

  @media (max-width: 640px) {
    .facts {
      grid-template-columns: 1fr;
    }
  }
</style>
