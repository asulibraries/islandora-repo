<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Latest additions to collection' Block.
 *
 * @Block(
 *   id = "latest_additions_to_collection_block",
 *   admin_label = @Translation("Latest Additions To Collection"),
 *   category = @Translation("Views"),
 * )
 */
class LatestAdditionsToCollectionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $collection_node = \Drupal::routeMatch()->getParameter('node');
    $children_nids = getAllCollectionChildren($collection_node, TRUE, 4);

    $rendered_nodes = $this->renderNodes($children_nids);

    $return = [
      '#cache' => ['max-age' => 0],
      '#markup' =>
        ((count($children_nids) > 0) ?
        $rendered_nodes:
        ""),
      'lib' => [
        '#attached' => [
          'library' => [
            'asu_collection_extras/style',
          ],
        ],
      ]
    ];
    return $return;
  }

  private function renderNodes($nids) {
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $output = [];
    foreach ($nids as $nid) {
      $node = $storage->load($nid);
      $build = $view_builder->view($node, 'collection_browse_teaser');
      $output[] = render($build);
    }
    return '<div class="card-deck">' . implode('', $output) . "</div>";
  }

}
