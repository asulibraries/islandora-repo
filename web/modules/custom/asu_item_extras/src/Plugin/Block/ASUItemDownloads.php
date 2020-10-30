<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;

/**
 * Provides a 'Downloads count' Block.
 *
 * @Block(
 *   id = "asu_item_downloads",
 *   admin_label = @Translation("Item downloads count"),
 *   category = @Translation("Views"),
 * )
 */
class ASUItemDownloads extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The output of this block should be:
     *  - Download count for all media on the object
     */
    $block_config = BlockBase::getConfiguration();
    if (is_array($block_config) && array_key_exists('child_node_id', $block_config)) {
      $node_id = $block_config['child_node_id'];
    }
    else {
    // Since this block should be set to display on node/[nid] pages that are
    // "ASU Repository Item", the underlying node can be accessed via the path.

    // When this block appears on the items/{nid}/members view, each node.id value
    // is passed as a parameter.
      if (\Drupal::routeMatch()->getParameter('node')) {
        $node = \Drupal::routeMatch()->getParameter('node');
        $node_id = (is_string($node) ? $node : $node->id());
      }
    }
    if ($node_id) {
      $mids = \Drupal::entityQuery('media')
        ->condition('field_media_of', $node_id)
        ->execute();
      $download_count = 0;
      foreach ($mids as $mid) {
        $media = Media::load($mid);
        $fid = \Drupal::service('islandora_matomo.default')->getFileFromMedia($mid);
        $download_count += \Drupal::service('islandora_matomo.default')->getDownloadsForFile($fid);

      }
      return [
        '#cache' => ['max-age' => 0],
        '#markup' => Markup::create($download_count),
      ];
    }
    else {
      return [
        '#markup' => Markup::create("This page is not a node. Please restrict this block's configuration to display on nodes only."),
      ];
    }
  }
}
