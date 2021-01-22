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

    $a1 = '<div class="wrapper" id="wrapper-endorsed-footer">
      <div class="container" id="endorsed-footer">
        <div class="row">

          <div class="col-md" id="endorsed-logo">
            <img src="' . $logo_url . '" alt="ASU University Technology Office Arizona State University.">
          </div>

          <div class="col-md" id="social-media">
            <nav class="nav" aria-label="Social Media">
              <a class="nav-link" href="http://www.facebook.com/ASULibraries" title="ASU Library Facebook"><i class="fab fa-facebook-square"></i></a>
              <a class="nav-link" href="http://twitter.com/ASUlibraries" title="ASU Library Twitter"><i class="fab fa-twitter-square" title="ASU Library Twitter"></i></a>
              <a class="nav-link" href="http://instagram.com/ASULibraries/" title="ASU Library Instagram"><i class="fab fa-instagram-square"></i></a>
            </nav>
          </div>

        </div>
      </div>
    </div>';

    $a2 = '<div class="wrapper" id="wrapper-footer-columns">
      <nav aria-label="Footer">
        <div class="container" id="footer-columns">
          <div class="row">

            <div class="col-xl-3" id="info-column">
              <h5>' . \Drupal::config('system.site')->get('name') . '</h5>
              <p class="contact-link"><a href="/contact">Contact Us</a></p>
              <!--<p class="contribute-button"><a href="#" class="btn btn-small btn-gold">Contribute</a></p>-->
            </div>

            <div class="col-xl flex-footer">
              <div class="card card-foldable desktop-disable-xl">
                <div class="card-header">
                  <h5>
                    <a id="footlink-header-two" data-toggle="collapse" href="#footlink-two" role="button" aria-expanded="false" aria-controls="footlink-two">Resources
                      <i class="fas fa-chevron-up"></i>
                    </a>
                  </h5>
                </div>
                <div id="footlink-two" class="collapse card-body show" aria-labelledby="footlink-header-two">
                  <a class="nav-link" href="/about/termsofdeposit" title="Terms of Deposit">Terms of Deposit</a>
                  <a class="nav-link" href="https://libguides.asu.edu/digitalrepository/home" title="ASU Digital Repository Guide">Sharing Materials: ASU Digital Repository Guide</a>
                  <a class="nav-link" href="http://libguides.asu.edu/openaccess" title="Open Access at ASU">Open Access at ASU</a>
                </div>
              </div>
            </div>

          </div>
        </div>
      </nav>
    </div>';

    $build['asu_lib_footer_block']['#markup'] = $a1 . $a2;

    return $build;
  }

}
