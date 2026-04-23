<script>
  import { base } from '$app/paths';
  import { formatDateTime, formatDuration, formatPrice } from '$lib/utils/experience';

  /** @type {any} */
  export let experience;
  export let showSummary = true;
</script>

<a class="card" href={`${base}/experiences/${experience.id}`}>
  <div class="card-top">
    <span class="location">{experience.location}</span>
    <span class:bookable={experience.booking?.isBookable} class="availability">
      {experience.booking?.isBookable ? 'Reservable' : 'Complet ou a venir'}
    </span>
  </div>

  <div class="card-body">
    <h3>{experience.title}</h3>

    {#if showSummary && experience.summary}
      <p>{experience.summary}</p>
    {/if}

    <dl class="facts">
      <div>
        <dt>Prix</dt>
        <dd>{formatPrice(experience.price)}</dd>
      </div>
      <div>
        <dt>Duree</dt>
        <dd>{formatDuration(experience.durationMinutes)}</dd>
      </div>
      <div>
        <dt>Prochain depart</dt>
        <dd>{formatDateTime(experience.booking?.nextStartAt)}</dd>
      </div>
      <div>
        <dt>Avis</dt>
        <dd>
          {#if experience.reviewSummary?.count}
            {experience.reviewSummary.averageRating}/5 · {experience.reviewSummary.count}
          {:else}
            Aucun avis
          {/if}
        </dd>
      </div>
    </dl>
  </div>
</a>

<style>
  .card {
    display: grid;
    gap: 1.2rem;
    padding: 1.4rem;
    border-radius: 1.5rem;
    text-decoration: none;
    background:
      linear-gradient(180deg, rgba(255, 250, 244, 0.96), rgba(255, 255, 255, 0.9)),
      #fff;
    border: 1px solid rgba(117, 74, 39, 0.12);
    box-shadow: 0 20px 50px rgba(88, 54, 30, 0.08);
    color: inherit;
    transition:
      transform 180ms ease,
      box-shadow 180ms ease,
      border-color 180ms ease;
  }

  .card:hover {
    transform: translateY(-3px);
    box-shadow: 0 28px 60px rgba(88, 54, 30, 0.14);
    border-color: rgba(195, 120, 66, 0.3);
  }

  .card-top {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: center;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  .location {
    color: #8a5a35;
    font-weight: 700;
  }

  .availability {
    padding: 0.4rem 0.75rem;
    border-radius: 999px;
    background: rgba(111, 91, 74, 0.1);
    color: #5f5248;
    font-weight: 700;
  }

  .availability.bookable {
    background: rgba(31, 126, 92, 0.12);
    color: #1f7e5c;
  }

  .card-body {
    display: grid;
    gap: 1rem;
  }

  h3 {
    margin: 0;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: clamp(1.35rem, 2vw, 1.7rem);
    line-height: 1.1;
    color: #24160e;
  }

  p {
    margin: 0;
    color: #5e5147;
    line-height: 1.65;
  }

  .facts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
    margin: 0;
  }

  .facts div {
    padding: 0.9rem;
    border-radius: 1rem;
    background: rgba(245, 238, 230, 0.75);
  }

  dt {
    margin-bottom: 0.35rem;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #8c715e;
  }

  dd {
    margin: 0;
    color: #2b211b;
    font-weight: 700;
    line-height: 1.4;
  }

  @media (max-width: 640px) {
    .facts {
      grid-template-columns: 1fr;
    }
  }
</style>
