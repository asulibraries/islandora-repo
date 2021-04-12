<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\EntityLookup;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a MultiEntityLookup object.
   *
   * @param Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A drupal entity type manager object.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EntityTypeManager $entityTypeManager
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /** @inheritdoc */
  public function transform($arr, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $item_parent = $arr[0];
    if (count($arr) > 1) {
      $collection_parent = $arr[1];
    }
    if ($item_parent) {
      if (array_key_exists('lookup_field', $this->configuration)) {
        $par = $this->entityTypeManager->getStorage('node')->loadByProperties([$this->configuration['lookup_field'] => $item_parent]);
      }
      else {
        // default is the pid field
        $par = $this->entityTypeManager->getStorage('node')->loadByProperties(['field_pid' => $item_parent]);
      }
      $par = array_keys($par)[0];
    }
    else {
      $this->configuration['bundle'] = 'collection';
      $this->configuration['value_key'] = 'title';
      $par = parent::transform($collection_parent, $migrate_executable, $row, $destination_property);
    }
    return $par;
  }
}
