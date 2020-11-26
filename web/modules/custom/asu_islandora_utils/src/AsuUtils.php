<?php

namespace Drupal\asu_islandora_utils;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Class AsuUtils.
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
        // do nothing special... just allow the node's isPublished state to be
        // returned.
      }
    }
    return $retval;
  }

  public function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow]; 
  } 
}
