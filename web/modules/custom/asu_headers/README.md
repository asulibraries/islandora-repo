## ASU Headers for Drupal 8 sites



### Incorporating the Global ASU Header

*The ASU web standards established a single standard header, which replaces all previous headers.*

There are four parts to the ASU Header that need to be rendered on the site in order for it all to display and function correctly. 
Generally, the code is added to the elements of the page according to this pattern.

PHP
Header: Place above </head> tag: <?php echo file_get_contents('http://www.asu.edu/asuthemes/4.8/heads/default.shtml'); ?>

Google Tag Manager: Place below <body> tag: <?php echo file_get_contents('http://www.asu.edu/asuthemes/4.8/includes/gtm.shtml'); ?>

Header: Place below Google Tag Manager: <?php echo file_get_contents('http://www.asu.edu/asuthemes/4.8/headers/default.shtml'); ?>

Footer: Above </body> tag: <?php echo file_get_contents('http://www.asu.edu/asuthemes/4.8/includes/footer.shtml'); ?>


#### This implementation is for Drupal 8 sites

The Drupal 7 implementation of this module is found here: https://github.com/ASU/asu-drupal-modules/tree/master/asu_brand

