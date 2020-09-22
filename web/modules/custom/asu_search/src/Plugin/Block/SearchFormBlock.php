<?php

namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search Form' Block.
 *
 * @Block(
 *   id = "search_form_block",
 *   admin_label = @Translation("Search form"),
 *   category = @Translation("Views"),
 * )
 */
class SearchFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $search_form = \Drupal::formBuilder()->getForm('Drupal\asu_search\Form\SearchForm');
    return $search_form;
  }

}
