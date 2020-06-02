<?php

/**
 * @file
 * PrintAndShareBlock
 */
namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'Print and share' Block.
 *
 * @Block(
 *   id = "print_and_share_item_block",
 *   admin_label = @Translation("Print and share"),
 *   category = @Translation("Views"),
 * )
 */
class PrintAndShareBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * This should probably call a separate function that will output the
     * HTML --- and this same contents will be needed in the main part of the
     * page so that same function should be used there.
     */
    $node_url = Url::fromRoute('<current>', array());

    return [
      'unordered-list' => [
        '#type' => 'item',
        '#markup' => asu_search_get_print_and_share_block($node_url),
      ]
    ];
  }

}