# User flow verification v11

## Covered flows
1. Registration flow (API contract and UI route availability).
2. Listing creation/edit lifecycle status transitions (`draft` -> `pending_verification`).
3. Admin moderation flow (`active` / `rejected`) with reason and notifications.
4. Public listing visibility restricted to `active`.

## Automated checks used
- Backend unit tests:
  - `TestNormalizeStatus`
  - `TestValidateListingPayload`
  - `TestNextStatusOnCreate`
  - `TestNextStatusOnUpdate`
  - `TestSeedSQL_HasExpectedDataShape`
- Frontend unit tests:
  - `listingUtils.test.js`

## Outcome
- Status transitions and validation logic are covered by unit tests.
- Seed data consistency and status coverage are validated by test.
- UI helper logic for status labels and distance sorting is covered.
