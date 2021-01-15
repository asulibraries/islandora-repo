<?php

namespace Drupal\asu_header\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block for the ASU universal footer
 *
 * @Block(
 *   id = "asu_global_footer",
 *   admin_label = @Translation("ASU Universal Footer"),
 *   category = @Translation("ASU"),
 * )
 */
class GlobalFooter extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      '#theme' => 'asu_footer',
      '#attached' => [
        'library' => [
          'asu_header/header-footer',
        ],
      ],
    ];
  }


}