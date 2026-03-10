export const STATUS_LABELS = {
  draft: 'Чернетка',
  pending_verification: 'На перевірці',
  active: 'Активне',
  rejected: 'Відхилене',
  archived: 'Архівне',
};

export function getStatusLabel(status) {
  return STATUS_LABELS[status] || status || '—';
}

export function distanceKm(from, to) {
  if (!Number.isFinite(from?.lat) || !Number.isFinite(from?.lng) || !Number.isFinite(to?.lat) || !Number.isFinite(to?.lng)) {
    return null;
  }
  const toRad = (deg) => (deg * Math.PI) / 180;
  const R = 6371;
  const dLat = toRad(to.lat - from.lat);
  const dLng = toRad(to.lng - from.lng);
  const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(from.lat)) * Math.cos(toRad(to.lat)) * Math.sin(dLng / 2) ** 2;
  return +(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))).toFixed(1);
}
