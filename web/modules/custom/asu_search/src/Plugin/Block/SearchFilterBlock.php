<?php

/**
 * @file
 * SearchFilterBlock
 */
namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search Filter' Block.
 *
 * @Block(
 *   id = "search_filter_block",
 *   admin_label = @Translation("Search Filters"),
 *   category = @Translation("Views"),
 * )
 */
class SearchFilterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->t('Search Filters'),
    ];
  }

}