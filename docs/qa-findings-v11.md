# QA findings v11

## Fixed during this pass
1. Listing create flow sent deprecated `pending` status from dashboard form (caused backend validation failures with new status enum).
2. Missing strict validation for listing payload (`category_id`, currency format, coordinates range).
3. No explicit admin-role guard on moderation endpoints.
4. Category page lacked map/list synchronization.

## Re-check
- Backend tests rerun after fixes.
- Frontend production build rerun after fixes.
- Visual check captured for category page map/list sync.


## UI alignment pass
- Added style tokens and base classes for shared look-and-feel.
- Replaced inline category page styles with reusable CSS classes.
- Re-checked desktop home and mobile categories views via screenshots.
