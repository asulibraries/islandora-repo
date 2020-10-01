<?php

namespace Drupal\asu_brand\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'AsuLibFooter' block.
 *
 * @Block(
 *  id = "asu_lib_footer_block",
 *  admin_label = @Translation("ASU Library Footer Block"),
 * )
 */
class AsuLibFooter extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $logo_url = 'https://repository.lib.asu.edu/themes/custom/asulib_barrio/images/library_footer_logo_white.png';

    $c1 = '<img src="' . $logo_url . '" /><br/><ul class="list-inline"><li class="list-inline-item"><a href="http://twitter.com/ASUlibraries"><i class="fab fa-twitter-square" title="ASU Library Twitter"></i></a></li><li class="list-inline-item"><a href="http://www.facebook.com/ASULibraries" title="ASU Library Facebook"><i class="fab fa-facebook-square"></i></a></li><li class="list-inline-item"><a href="http://instagram.com/ASULibraries/" title="ASU Library Instagram"><i class="fab fa-instagram-square"></i></a></li></ul>';
    $c2 = '<ul class="list-unstyled links"><li><a href="/about/policies/terms-of-deposit" title="Terms of Deposit">Terms of Deposit</a></li><li><a href="https://libguides.asu.edu/digitalrepository/home" title="ASU Digital Repository Guide">Sharing Materials: ASU Digital Repository Guide</a></li></ul>';
    $c3 = '<ul class="list-unstyled links"><li><a href="http://libguides.asu.edu/openaccess" title="Open Access at ASU">Open Access at ASU</a></li><li><a href="/contact" title="Contact Us">Contact Us</a></li></ul>';
    $build['asu_lib_footer_block']['#markup'] = '<div class="asulib-footer"><div class="container"><div class="row"><div class="col-md-3">' . $c1 . '</div><div class="col-md-3 offset-md-2">' . $c2 . '</div><div class="col-md-3">' . $c3 . '</div></div></div></div>';

    return $build;
  }

}
