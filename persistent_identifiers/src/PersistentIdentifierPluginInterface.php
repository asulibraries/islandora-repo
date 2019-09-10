<?php

namespace Drupal\persistent_identifiers;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for persistent identifier plugins.
 */
interface PersistentIdentifierPluginInterface extends PluginInspectionInterface {

  /**
   * Get or create the identifier.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   *
   * @return string
   *   The url.
   */
  public function getOrCreatePi(EntityInterface $entity = NULL);

  /**
   * Point the identifier to a tombstone page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The url.
   */
  public function tombstonePi(EntityInterface $entity);

}
