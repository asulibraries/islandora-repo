## ASU Headers for Drupal 8 sites

1. Install and enable the ASU Brand module just like any other module.
2. Go to the Structure -> Blocks and move the ASU Brand header and ASU Brand
   footer blocks into the header and footer regions respectively. The available
   regions will be determined by the theme that you are using.
3. The header and footer are cached locally and will refresh every 48 hours. If
   you need to manually refresh them, go to Configuration -> Performace and
   clear the site's cache.

### HOOKS
In the event that some code needs to be overridden in any installation, the asu_headers module provides several hooks for achieving this.
```{
/**
 * Implements hook_asu_brand_sitemenu_alter().
 * Modify site menu before injection into ASU Header
 */
function MODULENAME_asu_brand_sitemenu_alter(&$menu_array) {
  // you can modify the $menu_array here
}

/**
 * Implements hook_asu_brand_sitename_alter().
 * Modify site name before injection into ASU Header
 */
function MODULENAME_asu_brand_sitename_alter(&$site_name) {
  // you can modify the $site_name here
}

/**
 * Implements hook_asu_brand_site_url_alter().
 * Modify site name before injection into ASU Header
 */
function MODULENAME_asu_brand_site_url_alter(&$site_url) {
  // you can modify the $site_name here
}
```

### Incorporating the Global ASU Header

*The ASU web standards established a single standard header, which replaces all previous headers.*

There are four parts to the ASU Header that need to be rendered on the site in order for it all to display and function correctly. 
Generally, the PHP code is added to the elements of the page according to this pattern.

Header: Place above `</head>` tag:
 * `<?php echo file_get_contents('http://www.asu.edu/asuthemes/4.8/heads/default.shtml'); ?>`

Google Tag Manager: Place below `<body>` tag:
 * `<?php echo file_get_contents('http://www.asu.edu/asuthemes/4.8/includes/gtm.shtml'); ?>`

Header: Place below Google Tag Manager:
 * `<?php echo file_get_contents('http://www.asu.edu/asuthemes/4.8/headers/default.shtml'); ?>`

Footer: Above closing `</body>` tag:
 * `<?php echo file_get_contents('http://www.asu.edu/asuthemes/4.8/includes/footer.shtml'); ?>`


#### This implementation is for Drupal 8 sites

The Drupal 7 implementation of this module is found here: https://github.com/ASU/asu-drupal-modules/tree/master/asu_brand
