# UI audit v11 (home style alignment)

## Scope
- Home page, dashboard listings/editor, categories page, admin shell.

## Differences found
1. Repeated hardcoded colors/radii across pages.
2. Cards and form controls used mixed border/radius styles.
3. Category page relied on inline layout styling.

## Actions taken
- Introduced reusable style tokens in `:root` (`--color-bg`, `--color-surface`, `--color-border`, `--color-primary`, radii).
- Added base utility classes: `base-card`, `base-input/select/textarea`, `base-button`.
- Replaced inline styles on category map/list layout with CSS classes.
- Unified dashboard listing cards with `dashboard-listing-card`.

## Follow-up
- Migrate remaining pages/components to `base-*` primitives incrementally.
