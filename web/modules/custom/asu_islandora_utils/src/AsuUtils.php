<?php

namespace Drupal\asu_islandora_utils;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Provides commonly used utility functions.
 */
class AsuUtils {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new AsuUtils object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Bases the published state on the moderation state of the latest revision.
   *
   * Or the node's isPublished() method if it is not moderated content.
   */
  public function isNodePublished(NodeInterface $node) {
    $retval = $node->isPublished();
    // now, set a variable that can be based on the moderation state.
    if ($node->isDefaultRevision() == TRUE) {
      try {
        // Get all of the revision ids.
        $revision_ids = $this->entityTypeManager->getStorage('node')->revisionIds($node);
        // Check if the last item in the revisions is the loaded one.
        $last_revision_id = end($revision_ids);
        if ($node->getRevisionId() != $last_revision_id) {
          $last_revision = $this->entityTypeManager->getStorage('node')->loadRevision($last_revision_id);
          // Get the revisions moderation state.
          $last_revision_state = $last_revision->get('moderation_state')->getString();
          $retval = ($last_revision_state == 'published');
        }
      }
      catch (\Exception $e) {
        // Do nothing special... just allow the node's isPublished state to be
        // returned.
      }
    }
    return $retval;
  }

  /**
   * Formats Bytes into B, KB, MB, GB, TB.
   */
  public function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
  }

  /**
   * This is used by the blocks in this module to get children of any item.
   *
   * @param mixed $node
   *   Can take a node object or the node ID value.
   * @param bool $sort_by_date
   *   (OPTIONAL) whether or not to sort by date, default is FALSE.
   * @param int $limit
   *   The number of results to return (to provide the recent additions for the
   *   block that only displays only 4 items)
   */
  public function getNodeChildren($node, $sort_by_date = FALSE, $limit = 0, $items_only = TRUE) {
    $nid = (is_object($node) ? $node->id() : $node);
    $childrenQuery = \Drupal::entityQuery('node');
    $childrenQuery
      ->condition('field_member_of', $nid)
      ->condition('status', 1);
    if ($items_only) {
      $childrenQuery
        ->condition('type', 'asu_repository_item');
    }
    if ($sort_by_date) {
      $childrenQuery
        ->sort('changed', 'DESC');
    }
    if ($limit) {
      $childrenQuery
        ->range(0, $limit);
    }
    return $childrenQuery->execute();
  }

}
