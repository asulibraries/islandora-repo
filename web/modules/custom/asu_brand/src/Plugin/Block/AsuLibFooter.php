<?php

namespace Drupal\asu_brand\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
                    <a id="footlink-header-two" data-toggle="collapse" href="#footlink-two" role="button" aria-expanded="false" aria-controls="footlink-two">Repository Services
                      <i class="fas fa-chevron-up"></i>
                    </a>
                  </h5>
                </div>
                <div id="footlink-two" class="collapse card-body show" aria-labelledby="footlink-header-two">
                  <a class="nav-link" href="https://repository.lib.asu.edu" title="Repository Services Home">Home</a>
                  <a class="nav-link" href="https://keep.lib.asu.edu" title="KEEP">KEEP</a>
                  <a class="nav-link" href="https://prism.lib.asu.edu" title="PRISM">PRISM</a>
                  <a class="nav-link" href="https://dataverse.asu.edu" title="ASU Research Data Repository">ASU Research Data Repository</a>
                </div>
              </div>
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
                  <a class="nav-link" href="https://keep.lib.asu.edu/about/termsofdeposit" title="Terms of Deposit">Terms of Deposit</a>
                  <a class="nav-link" href="https://libguides.asu.edu/digitalrepository/home" title="ASU Digital Repository Guide">Sharing Materials: ASU Digital Repository Guide</a>
                  <a class="nav-link" href="http://libguides.asu.edu/openaccess" title="Open Access at ASU">Open Access at ASU</a>
                </div>
              </div>
            </div>

          </div>
        </div>
      </nav>
    </div>';

    $a3 = '<div class="wrapper" id="wrapper-footer-land-ack">
      <div class="container">
        <div class="row">
          <div class="col-md">
            <p>The ASU Library acknowledges the twenty-three Native Nations that have inhabited this land for centuries. Arizona State University\'s four campuses are located in the Salt River Valley on ancestral territories of Indigenous peoples, including the Akimel O’odham (Pima) and Pee Posh (Maricopa) Indian Communities, whose care and keeping of these lands allows us to be here today. ASU Library acknowledges the sovereignty of these nations and seeks to foster an environment of success and possibility for Native American students and patrons. We are advocates for the incorporation of Indigenous knowledge systems and research methodologies within contemporary library practice. ASU Library welcomes members of the Akimel O’odham and Pee Posh, and all Native nations to the Library.</p>
          </div>
        </div>
      </div>
    </div>';

    $build['asu_lib_footer_block']['#markup'] = $a1 . $a2 . $a3;

    return $build;
  }

}
