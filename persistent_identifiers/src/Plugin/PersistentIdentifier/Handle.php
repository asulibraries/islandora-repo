<?php

namespace Drupal\persistent_identifiers\Plugin\PersistentIdentifier;

use Drupal\persistent_identifiers\PersistentIdentifierPluginBase;
use Drupal\persistent_identifiers\PersistentIdentifierPluginInterface;
// use Drupal\Core\Config\ConfigManagerInterface;
// use Drupal\Core\Config\StorageInterface;
// use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * A plugin for handle.net.
 *
 * @PersistentIdentifierPlugin(
 *  id="pi_handle",
 *  label="Handle"
 * )
 */
class Handle extends PersistentIdentifierPluginBase implements PersistentIdentifierPluginInterface {

  /**
   * Get or create the identifier.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   *
   * @return string
   *   The url.
   */
  public function getOrCreatePi(EntityInterface $entity = NULL) {
    \Drupal::logger('persistent identifiers')->info('in the getOrCreatePI method');
    // Actually hit the REST API for handle.
    $entity->set('field_identifier', 'thisisthehandle');
    $entity->setNewRevision(FALSE);
    $entity->save();
    return "";
  }

  /**
   * Point the identifier to a tombstone page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The url.
   */
  public function tombstonePi(EntityInterface $entity) {
    return "";
  }

}
