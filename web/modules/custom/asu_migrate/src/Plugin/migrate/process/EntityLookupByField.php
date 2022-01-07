<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Check if term exists and create new if doesn't.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_lookup_by_field"
 * )
 *
 * @code
 *   field_copyright_statement:
 *      plugin: entity_lookup_by_field
 *      source: Copyright
 *      lookup_field: field_source/uri
 *      entity_type: taxonomy_term
 *      bundle: copyright_statements
 */
class EntityLookupByField extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityLookupByField object.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   A drupal entity type manager object.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EntityTypeManagerInterface $entityTypeManager
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * @inheritdoc */
  public function transform($string, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $this->getTidByValue($string, $this->configuration['lookup_field'], $this->configuration['bundle']);
  }

  /**
   * Load term by value.
   */
  public function getTidByValue($value = NULL, $field = NULL, $bundle = NULL) {
    $properties = [];
    if ($bundle) {
      $properties['vid'] = [$bundle];
    }
    if (!empty($value) && !empty($field)) {
      if (strpos($field, '/') !== FALSE) {
        $field = explode('/', $field);
        $properties[$field[0]][$field[1]] = $value;
      }
      else {
        $properties[$field] = $value;
      }
    }
    // @todo possible improvement would be to limit by the bundle available in the config
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    return !empty($term) ? $term->id() : 0;
  }

}
