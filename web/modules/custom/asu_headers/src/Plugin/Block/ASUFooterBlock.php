<?php

namespace Drupal\asu_headers\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'ASU Footer' Block.
 *
 * @Block(
 *   id = "asu_footer_block",
 *   admin_label = @Translation("ASU footer"),
 *   category = @Translation("Views"),
 * )
 */
class ASUFooterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // To be inserted at the top of the page under the <body> tag.
    $url = 'http://www.asu.edu/asuthemes/4.8/includes/gtm.shtml';
    $header_html = file_get_contents($url);
    return ['#markup' => $header_html];
  }

}
