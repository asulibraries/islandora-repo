<?php

namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search Filter' Block.
 *
 * @Block(
 *   id = "asu_search_filter_block",
 *   admin_label = @Translation("Search filter block"),
 *   category = @Translation("Hello World"),
 * )
 */
class FilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}

