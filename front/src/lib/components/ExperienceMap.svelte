<script>
  import { createEventDispatcher, onDestroy, onMount } from 'svelte';
  import 'leaflet/dist/leaflet.css';

  /** @type {any[]} */
  export let experiences = [];
  /** @type {{ latitude: number; longitude: number } | null} */
  export let userLocation = null;
  /** @type {number | null} */
  export let selectedExperienceId = null;

  const dispatch = createEventDispatcher();

  /** @type {HTMLDivElement | undefined} */
  let mapElement;
  /** @type {any} */
  let map;
  /** @type {any} */
  let layerGroup;
  /** @type {any} */
  let leaflet;
  let hasRenderedInitialView = false;

  onMount(async () => {
    const module = await import('leaflet');
    leaflet = module.default || module;

    map = leaflet.map(mapElement, {
      zoomControl: true,
      scrollWheelZoom: true,
      wheelDebounceTime: 35
    }).setView([46.603354, 1.888334], 5.4);

    leaflet
      .tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution:
          '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      })
      .addTo(map);

    layerGroup = leaflet.layerGroup().addTo(map);
    renderMap();

    requestAnimationFrame(() => {
      map?.invalidateSize();
    });
  });

  onDestroy(() => {
    if (map) {
      map.remove();
    }
  });

  $: if (map && leaflet && layerGroup) {
    renderMap();
  }

  function renderMap() {
    if (!map || !leaflet || !layerGroup) {
      return;
    }

    layerGroup.clearLayers();

    /** @type {any[]} */
    const boundsPoints = [];

    for (const experience of experiences) {
      const latitude = Number(experience?.coordinates?.latitude);
      const longitude = Number(experience?.coordinates?.longitude);

      if (Number.isNaN(latitude) || Number.isNaN(longitude)) {
        continue;
      }

      const isSelected = Number(experience?.id) === Number(selectedExperienceId);
      const marker = leaflet.circleMarker([latitude, longitude], {
        radius: isSelected ? 12 : 9,
        weight: isSelected ? 3 : 2,
        color: isSelected ? '#f8efe4' : '#fff7f0',
        fillColor: isSelected ? '#6f4328' : '#a05f37',
        fillOpacity: 0.94
      });

      marker.bindPopup(`
        <div style="min-width: 180px">
          <strong>${escapeHtml(experience.title || 'Experience')}</strong><br/>
          <span>${escapeHtml(experience.location || 'Lieu a confirmer')}</span>
        </div>
      `);

      marker.on('click', () => {
        dispatch('select', { id: experience.id });
      });

      marker.addTo(layerGroup);
      boundsPoints.push([latitude, longitude]);
    }

    if (userLocation && isFinite(userLocation.latitude) && isFinite(userLocation.longitude)) {
      leaflet.circle([userLocation.latitude, userLocation.longitude], {
        radius: 1200,
        color: '#22695a',
        weight: 1,
        fillColor: '#4cb89a',
        fillOpacity: 0.16
      }).addTo(layerGroup);

      leaflet.circleMarker([userLocation.latitude, userLocation.longitude], {
        radius: 8,
        weight: 2,
        color: '#f3fffb',
        fillColor: '#22695a',
        fillOpacity: 1
      })
        .bindPopup('Votre position approximative')
        .addTo(layerGroup);

      boundsPoints.push([userLocation.latitude, userLocation.longitude]);
    }

    if (boundsPoints.length === 0) {
      if (!hasRenderedInitialView) {
        map.setView([46.603354, 1.888334], 5.4);
        hasRenderedInitialView = true;
      }

      return;
    }

    const selectedExperience = experiences.find(
      (experience) => Number(experience?.id) === Number(selectedExperienceId)
    );

    if (
      selectedExperience &&
      selectedExperience.coordinates &&
      isFinite(Number(selectedExperience.coordinates.latitude)) &&
      isFinite(Number(selectedExperience.coordinates.longitude))
    ) {
      map.setView(
        [Number(selectedExperience.coordinates.latitude), Number(selectedExperience.coordinates.longitude)],
        11,
        { animate: true }
      );
      return;
    }

    const bounds = leaflet.latLngBounds(boundsPoints);
    map.fitBounds(bounds, {
      padding: [30, 30],
      maxZoom: userLocation ? 10 : 7
    });
    hasRenderedInitialView = true;
  }

  /**
   * @param {string} value
   */
  function escapeHtml(value) {
    return value
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  }
</script>

<div bind:this={mapElement} class="map-shell" role="img" aria-label="Carte des experiences"></div>

<style>
  .map-shell {
    min-height: 28rem;
    border-radius: 1.6rem;
    overflow: hidden;
    background: linear-gradient(180deg, rgba(255, 251, 246, 0.98), rgba(246, 238, 230, 0.92));
  }

  :global(.leaflet-container) {
    height: 100%;
    width: 100%;
    font: inherit;
    color: #2b211b;
  }

  :global(.leaflet-popup-content-wrapper),
  :global(.leaflet-popup-tip) {
    background: rgba(255, 251, 246, 0.98);
    color: #2b211b;
    box-shadow: 0 18px 45px rgba(66, 40, 19, 0.18);
  }

  :global(.leaflet-control-attribution) {
    background: rgba(255, 251, 246, 0.92);
  }
</style>
