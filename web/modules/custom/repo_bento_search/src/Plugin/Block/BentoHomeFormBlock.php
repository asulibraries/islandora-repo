<?php

namespace Drupal\repo_bento_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Bento Home Form' Block.
 *
 * @Block(
 *   id = "bento_home_form_block",
 *   admin_label = @Translation("Bento form for Homepage"),
 *   category = @Translation("Views"),
 * )
 */
class BentoHomeFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $search_form = \Drupal::formBuilder()->getForm('Drupal\repo_bento_search\Form\BentoHomeSearchForm');
    return $search_form;
  }

}
