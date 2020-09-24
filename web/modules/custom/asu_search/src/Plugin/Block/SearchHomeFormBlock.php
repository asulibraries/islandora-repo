<?php

namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search Home Form' Block.
 *
 * @Block(
 *   id = "search_home_form_block",
 *   admin_label = @Translation("Search form for Homepage"),
 *   category = @Translation("Views"),
 * )
 */
class SearchHomeFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $search_form = \Drupal::formBuilder()->getForm('Drupal\asu_search\Form\SearchHomeForm');
    return $search_form;
  }

}
