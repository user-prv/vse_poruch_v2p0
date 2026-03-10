import { describe, expect, it } from 'vitest';
import { distanceKm, getStatusLabel } from './listingUtils';

describe('listingUtils', () => {
  it('returns translated status labels', () => {
    expect(getStatusLabel('pending_verification')).toBe('На перевірці');
    expect(getStatusLabel('unknown')).toBe('unknown');
  });

  it('calculates distance in km', () => {
    const kyiv = { lat: 50.4501, lng: 30.5234 };
    const nearby = { lat: 50.4547, lng: 30.5238 };
    const value = distanceKm(kyiv, nearby);
    expect(value).not.toBeNull();
    expect(value).toBeGreaterThan(0);
  });

  it('returns null for invalid points', () => {
    expect(distanceKm({ lat: NaN, lng: 1 }, { lat: 1, lng: 1 })).toBeNull();
  });
});
