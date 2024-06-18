@@ -0,0 +1,21 @@
ASU Repositories Bootstrap 5 - Barrio SASS Sub-Theme
====================================================

## Development

1. Install Node.js 22+
1. Save the `.npmrc` file with the ASU authentication info.
1. `npm install`
1. Install Gulp
1. `gulp` runs build which populates the `css` and `js` directories.
1. After you build, commit the changes to `css` and `js` so we don't have to do all this on production.

## Bootstrap 4→5 & ASU Design Changes

- The `data-toggle` and `data-target` became `data-bs-toggle` and `data-bs-target`, respectively.
- Removed JQuery.
- Font Awesome moved from the ASU kit to a library kit included via library.
- Stretch links are discouraged in favor of call-to-action buttons.
- Uppercase format extensions in download buttons.

## Styling Mysteries

- The file format, e.g. 'pdf', on the downloads buttons on item pages is being capitalized despite the dev tools indicating they *aren't* applying a `text-transform`. The only way I've found to remove the capitalization is by unselecting the `display: inline-block` instances in dev tools. I have *no* idea why that would cause it to no longer capitalize "Pdf". It also isn't viable because it messes with the layout of other items. 😕
- The header's "Search" button isn't as rounded as other buttons with the same classes.
