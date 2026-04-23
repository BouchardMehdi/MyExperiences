/**
 * @param {string | null | undefined} status
 * @returns {string}
 */
export function formatBookingStatus(status) {
  switch (status) {
    case 'PENDING':
      return 'En attente';
    case 'PAID':
      return 'Payee';
    case 'CANCELLED':
      return 'Annulee';
    default:
      return status || 'Inconnu';
  }
}
