# Test plan v11

## Smoke
- Health endpoint 200
- Public listings return only `active`
- Create listing -> `pending_verification`
- Admin moderate to `active`/`rejected`
- Dashboard shows rejected reason

## Regression
- Home search + pagination
- Category page map + sorting
- Admin bulk moderation

## Roles
- User: create/edit own listings
- Admin: moderate, verify/block users
