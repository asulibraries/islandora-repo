<?php
namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'IncludedInThisItem' block.
 *
 * @Block(
 *  id = "included_in_this_item_block",
 *  admin_label = @Translation("Included in this item block"),
 * )
 */
class IncludedInThisItem extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_config = BlockBase::getConfiguration();
    $build = [];
    if (is_array($block_config) && array_key_exists('node', $block_config)) {
        $node = $block_config['node'];
        $node_id = $node->id();
        \Drupal::logger('included in this item')->info($node_id);
        $build['included_in_this_item_block'] = [
        '#markup' => '<div id="react-app">React block app will load here.</div>',
        '#attached' => [
            'library' => 'asulib_barrio/react_app',
            'drupalSettings' => [
                'reactApp' => [
                    'node_id' => $node_id,
                ],
            ],
        ],
        ];
    }
    return $build;
  }
}
