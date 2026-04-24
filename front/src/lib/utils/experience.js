/**
 * @param {{ amount?: string; currency?: string } | null | undefined} price
 * @returns {string}
 */
export function formatPrice(price) {
  if (!price?.amount) {
    return 'Prix indisponible';
  }

  const amount = Number(price.amount);

  if (Number.isNaN(amount)) {
    return `${price.amount} ${price.currency || 'EUR'}`;
  }

  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: price.currency || 'EUR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(amount);
}

/**
 * @param {string | null | undefined} value
 * @returns {string}
 */
export function formatDateTime(value) {
  if (!value) {
    return 'Date a confirmer';
  }

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(new Date(value));
}

/**
 * @param {number | null | undefined} minutes
 * @returns {string}
 */
export function formatDuration(minutes) {
  if (!minutes || minutes < 1) {
    return 'Duree flexible';
  }

  if (minutes < 60) {
    return `${minutes} min`;
  }

  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  if (remainingMinutes === 0) {
    return `${hours} h`;
  }

  return `${hours} h ${remainingMinutes} min`;
}

/**
 * @param {{ latitude: number; longitude: number } | null | undefined} from
 * @param {{ latitude: number; longitude: number } | null | undefined} to
 * @returns {number | null}
 */
export function calculateDistanceKm(from, to) {
  if (
    !from ||
    !to ||
    !Number.isFinite(from.latitude) ||
    !Number.isFinite(from.longitude) ||
    !Number.isFinite(to.latitude) ||
    !Number.isFinite(to.longitude)
  ) {
    return null;
  }

  const earthRadiusKm = 6371;
  const latitudeDelta = toRadians(to.latitude - from.latitude);
  const longitudeDelta = toRadians(to.longitude - from.longitude);
  const fromLatitude = toRadians(from.latitude);
  const toLatitude = toRadians(to.latitude);

  const a =
    Math.sin(latitudeDelta / 2) * Math.sin(latitudeDelta / 2) +
    Math.cos(fromLatitude) *
      Math.cos(toLatitude) *
      Math.sin(longitudeDelta / 2) *
      Math.sin(longitudeDelta / 2);

  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return earthRadiusKm * c;
}

/**
 * @param {number} value
 * @returns {number}
 */
function toRadians(value) {
  return (value * Math.PI) / 180;
}
