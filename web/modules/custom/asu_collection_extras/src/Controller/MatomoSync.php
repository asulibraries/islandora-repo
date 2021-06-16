<?php

namespace Drupal\asu_collection_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * A drush command file for collection summary tabulation.
 *
 * @package Drupal\asu_collection_extras\Commands
 */
class MatomoSync extends ControllerBase {

  /**
   * An endpoint to respond to an AJAX call that will perform the sync of
   * matomo views & downloads for a given node.
   *
   * @param Drupal\node\NodeInterface $node
   *   The node to sync.
   */
  public function matomoSync($node) {
    if (!is_object($node) && $node) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    }
    if (is_object($node)) {
      // \Drupal::logger('asu_collection_extras')->info('it is in the endpoint! node id = ' . $node->id());
      $node_relations = asu_collection_extras_syncNodeRelations($node);
      $node_matomo_stats = asu_collection_extras_syncNodeMatomoStats($node->id());
      // \Drupal::logger('asu_collection_extras')->info('$node_relations = ' . print_r($node_relations, true));
      // \Drupal::logger('asu_collection_extras')->info('$node_matomo_stats = ' . print_r($node_matomo_stats, true));
    }
    return [];
  }

}
