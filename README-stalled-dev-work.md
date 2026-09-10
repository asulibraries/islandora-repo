# Stalled Dev Work Preservation

This branch preserves uncommitted development work found on the dev server on 2026-09-10.

The work appears to be part of a stalled project to remove or de-emphasize the separate Full Metadata page and display more repository item metadata directly on item pages. These changes were present on dev but were not committed and were not present on production. Production was up to date with `origin/develop` when this branch was created.

The changes include:

- Removing the `/items/{node}/metadata` route.
- Removing related full metadata breadcrumb and theme preprocessing behavior.
- Changing the ASU Item "Part of" block layout behavior.
- Replacing "View full metadata" links with inline metadata fields across repository item templates.
- Deleting one PRISM group form display config file.

This branch was created to preserve the work for possible future review while returning `develop` to a clean state for security/core updates and other maintenance.

To regenerate a patch from this branch:

```bash
git diff develop..preserve/stalled-full-metadata-inline-fields > stalled-dev-work.patch
