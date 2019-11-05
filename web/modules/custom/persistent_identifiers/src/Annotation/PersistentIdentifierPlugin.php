<?php

namespace Drupal\persistent_identifiers\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Persistent Identifier Plugin annotation object.
 *
 * @see \Drupal\persistent_identifiers\PersistentIdentifierPluginManager
 * @see \Drupal\persistent_identifiers\PersistentIdentifierPluginInterface
 * @see \Drupal\persistent_identifiers\PersistentIdentifierPluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class PersistentIdentifierPlugin extends Plugin {

  /**
   * The persistent identifier plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the persistent identifier plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the persistent identifier.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
