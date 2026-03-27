import { describe, expect, it } from 'vitest';
import { unwrapApiData, unwrapApiItems } from './apiPayload';

describe('apiPayload helpers', () => {
  it('unwrapApiData returns envelope data when present', () => {
    expect(unwrapApiData({ data: { total: 2 } })).toEqual({ total: 2 });
  });

  it('unwrapApiItems supports enveloped collection', () => {
    expect(unwrapApiItems({ data: { items: [{ id: 1 }] } })).toEqual([{ id: 1 }]);
  });

  it('unwrapApiItems supports array response and defaults to empty array', () => {
    expect(unwrapApiItems([{ id: 2 }])).toEqual([{ id: 2 }]);
    expect(unwrapApiItems({ data: {} })).toEqual([]);
  });
});
