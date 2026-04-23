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
