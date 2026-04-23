<script>
  import { onMount } from 'svelte';
  import { fetchHealth, fetchHello } from '$lib/api/client';

  /** @type {Record<string, unknown> | null} */
  let health = null;
  /** @type {Record<string, unknown> | null} */
  let hello = null;
  let error = '';

  onMount(async () => {
    try {
      const [healthResponse, helloResponse] = await Promise.all([fetchHealth(), fetchHello()]);
      health = healthResponse;
      hello = helloResponse;
    } catch (exception) {
      error = exception instanceof Error ? exception.message : 'Erreur inconnue.';
    }
  });
</script>

<svelte:head>
  <title>MyExperiences</title>
  <meta
    name="description"
    content="Base reusable SvelteKit + Symfony API + PostgreSQL with Docker and sub-path deployment."
  />
</svelte:head>

<div class="page-shell">
  <section class="hero">
    <span class="eyebrow">MyExperiences</span>
    <h1>Frontend SvelteKit statique, backend Symfony API, deploiement sous <code>/MyExperiences</code>.</h1>
    <p>
      Cette base est prete pour une application d'experiences avec une separation nette entre
      frontend, API Symfony et PostgreSQL.
    </p>
  </section>

  <section class="panel-grid">
    <article class="panel">
      <h2>Health</h2>
      {#if health}
        <pre>{JSON.stringify(health, null, 2)}</pre>
      {:else if error}
        <p class="error">{error}</p>
      {:else}
        <p>Chargement...</p>
      {/if}
    </article>

    <article class="panel">
      <h2>Hello</h2>
      {#if hello}
        <pre>{JSON.stringify(hello, null, 2)}</pre>
      {:else if error}
        <p class="error">{error}</p>
      {:else}
        <p>Chargement...</p>
      {/if}
    </article>
  </section>
</div>

<style>
  :global(body) {
    margin: 0;
    font-family:
      'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    background:
      radial-gradient(circle at top left, rgba(56, 189, 248, 0.2), transparent 25%),
      linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
    color: #0f172a;
  }

  .page-shell {
    max-width: 1040px;
    margin: 0 auto;
    padding: 4rem 1.5rem;
  }

  .hero {
    background: rgba(255, 255, 255, 0.88);
    border-radius: 1.5rem;
    padding: 2rem;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.1);
    margin-bottom: 1.5rem;
  }

  .eyebrow {
    display: inline-block;
    margin-bottom: 1rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    background: #0f172a;
    color: #fff;
    font-size: 0.85rem;
  }

  h1 {
    margin: 0 0 1rem;
    font-size: clamp(2rem, 5vw, 3.5rem);
    line-height: 1.05;
  }

  p {
    margin: 0;
    font-size: 1.05rem;
    line-height: 1.7;
    color: #334155;
  }

  code {
    font-family: 'Consolas', 'SFMono-Regular', monospace;
    font-size: 0.95em;
  }

  .panel-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
  }

  .panel {
    background: rgba(255, 255, 255, 0.92);
    border-radius: 1.25rem;
    padding: 1.5rem;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
  }

  .panel h2 {
    margin-top: 0;
  }

  .panel pre {
    white-space: pre-wrap;
    word-break: break-word;
    margin: 0;
    padding: 1rem;
    border-radius: 1rem;
    background: #0f172a;
    color: #e2e8f0;
    font-size: 0.92rem;
  }

  .error {
    color: #b91c1c;
    font-weight: 600;
  }
</style>
