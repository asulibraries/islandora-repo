<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Explore this Collection' Block.
 *
 * @Block(
 *   id = "explore_this_collection_block",
 *   admin_label = @Translation("Explore this collection"),
 *   category = @Translation("Views"),
 * )
 */
class ExploreThisCollectionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $search_form = \Drupal::formBuilder()->getForm('Drupal\asu_collection_extras\Form\ExploreForm');
    return $search_form;
  }

}