<?php

namespace Drupal\asu_collection_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\asu_collection_extras\Controller\ASUSumaryClass;
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
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   */
  public function matomoSync(NodeInterface $node) {
    \Drupal::logger('asu_collection_extras')->info('it is in the endpoint! node id = ' . $node->id());
    $node_relations = $this->syncNodeRelations($node);
    $node_matomo_stats = $this->syncNodeMatomoStats($node);

    return [];
  }

  private function syncNodeRelations($node) {
    // Query the node to determine all of the objects related via Member Of
    // and Additional Memberships.
    $member_of = $node->get('field_member_of')->entity;
    $member_of_id = (is_object($member_of)) ? $member_of->id() : 0;
    $member_of_model = (is_object($member_of)) ? $member_of->bundle() : '';
    \Drupal::logger('asu_collection_extras')->info('member_of ' . $member_of_id . ' which is ' . $member_of_model);

    if ($member_of_id) {
      // Insert a record for this item and the parent collection.
      // Object parent is a collection.
      // i_nid = $node->id();
      // c_nid = $member_of_id
      // parent_type = collec
    }
    if ($node->bundle() == 'asu_repository_item') {
      $additional_memberships = $node->get('field_additional_memberships')->referencedEntities();
      if (count($additional_memberships) > 0) {
        foreach ($additional_memberships as $additional_membership_entity) {
          $member_of_id = (is_object($additional_membership_entity)) ? $additional_membership_entity->id() : 0;
          $member_of_model = (is_object($additional_membership_entity)) ? $additional_membership_entity->bundle() : '';
          \Drupal::logger('asu_collection_extras')->info('member_of ' . $member_of_id . ' which is ' . $member_of_model);
        }
      }
    }
  }

  private function syncNodeMatomoStats($node) {
    // for the given node, call Matomo API to get the views AND downloads.
  }

}
