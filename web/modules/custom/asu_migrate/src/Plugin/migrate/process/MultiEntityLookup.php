<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Check if term exists and create new if doesn't.
 *
 * @MigrateProcessPlugin(
 *   id = "multi_entity_lookup"
 * )
 *
 * @code
 *   field_member_of:
 *      plugin: multi_entity_lookup
 *      source:
 *        - Parent Item
 *        - Collection Title
 *      entity_type: node
 */
class MultiEntityLookup extends EntityLookup implements ContainerFactoryPluginInterface {

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MultiEntityLookup object.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   A drupal entity type manager object.
   * @param Drupal\migrate\Plugin\MigrationInterface      $migration
   *   The migration object.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EntityTypeManagerInterface $entityTypeManager,
      MigrationInterface $migration
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @todo write the comment correctly.
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $migration
    );
  }

  /**
   * @inheritdoc
   */
  public function transform($parent_columns, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // We assume the first in the list of configured sources is the primary parent column.
    if (!empty($parent_columns[0])) {
      // Pull the lookup field from configuration, defaulting to 'pid'.
      $lookup_field = (array_key_exists('lookup_field', $this->configuration)) ? $this->configuration['lookup_field'] : 'field_pid';
      $found = $this->entityTypeManager->getStorage('node')->loadByProperties([$lookup_field => $parent_columns[0]]);
 
      // loadByProperties returns an array of objects keyed by their node id, we just want the node id of the first result.
      if ($parent_nid = reset(array_keys($found))) {
        return $parent_nid;
      }

    }
    // Check for a second 'collection' column if the parent column was blank.
    elseif (count($parent_columns) > 1 && !empty($parent_columns[1]) ) {
      $this->configuration['bundle'] = 'collection';
      $this->configuration['value_key'] = 'title';
      return parent::transform($parent_columns[1], $migrate_executable, $row, $destination_property);
   }
  }
}
