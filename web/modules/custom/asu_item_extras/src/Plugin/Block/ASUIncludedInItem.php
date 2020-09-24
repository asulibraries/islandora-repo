<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;

/**
 * Provides an 'Included in this item' Block.
 *
 * @Block(
 *   id = "asu_included_in_item",
 *   admin_label = @Translation("Included in this item"),
 *   category = @Translation("Views"),
 * )
 */
class ASUIncludedInItem extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The output of this block should be:
     *  - the first three child nodes to any complex object
     */
    if (\Drupal::routeMatch()->getParameter('node')) {
      $node = \Drupal::routeMatch()->getParameter('node');
      $build_output = [];
      $node_id = (is_object($node)) ? $node->id() : $node;
      $children = asu_item_extras_get_complex_object_child_nodes($node_id, 3);
      foreach ($children as $child_obj) {
        if ($child_obj->entity_id) {
          $node = \Drupal::entityTypeManager()->getStorage('node')->load($child_obj->entity_id);
          $build_output[] = node_view($node, 'complex_object_child_box');
        }
      }
      return [
        '#cache' => ['max-age' => 0],
        $build_output
      ];
    }
    else {
      return [
        '#markup' => Markup::create("This page is not a node. Please restrict this block's configuration to display on nodes only."),
      ];
    }
  }
}
