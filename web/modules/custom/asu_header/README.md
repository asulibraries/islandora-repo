# ASU Unity Design System header/footer

A Drupal 8/9 compatible module to apply the Unity Design System header/footer.

## Assumptions

* The site's "Main navigation" menu will be inserted into the header.
* This module was built for a site using a sub-theme of Bootstrap Barrio. ymmv

## Configuration

* The parent unit URL is hardcoded as a variable in xxx.html.twig.
  * Set the site slogan to be the parent unit in Drupal site settings.
* Barrio subtheme config:
  * Layout > Region > Top Header, Header, and Footer fifth regions should all have "no wrapper" checked and no class set (the default is row).
  * A custom page.html.twig file is included for comparison. Any template in the theme will override this module's templates.
  
## Block placement

ASU Global Menu block into the Top Header region.

Main navigation block into the Header region.

Universal Footer block into the Footer fifth region.

## Troubleshooting

If the styles are not being applied to the menu, check to make sure the Main navigation block has the correct machine name/ID.

If the block was added to another theme and then added to your subtheme, the block ID may be `[subtheme]-mainnavigation` instead of `mainnavigation`.

Delete the block from all of the themes where it appears, then add it to your subtheme's block page again. It should have the correct ID.