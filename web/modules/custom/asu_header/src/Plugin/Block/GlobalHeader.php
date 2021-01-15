<?php

namespace Drupal\asu_header\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block for the ASU universal header
 *
 * @Block(
 *   id = "asu_global_header",
 *   admin_label = @Translation("ASU Universal Header"),
 *   category = @Translation("ASU"),
 * )
 */
class GlobalHeader extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      '#theme' => 'asu_header',
    ];
  }


}