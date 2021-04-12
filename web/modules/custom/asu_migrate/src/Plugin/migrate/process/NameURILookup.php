<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check if term exists and create new if doesn't.
 *
 * @MigrateProcessPlugin(
 *   id = "name_uri_lookup"
 * )
 */
class NameURILookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {
  protected $name;
  protected $uri;

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a NameURILookup object.
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

  /**
   * {@inheritdoc}
   */
  public function transform($name_uri_pair, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $uri_field = $this->configuration['uri_field'];
    if (is_array($name_uri_pair)) {
      $this->name = $name_uri_pair[$this->configuration['name_array_key']];
      $this->uri = $name_uri_pair[$this->configuration['uri_array_key']];
    }
    else {
      $delimiter = $this->configuration['delimiter'];
      if (empty($name_uri_pair) || empty($delimiter)) {
        throw new MigrateSkipProcessException();
      }
      $thisone = array_map('trim', explode($delimiter, $name_uri_pair));
      if (count($thisone) > 1) {
        list($this->name, $this->uri) = $thisone;
      } else {
        $this->name = $thisone[0];
        $this->uri = NULL;
      }
    }
    if (!empty($this->uri) && $tid = $this->getTidByURI($this->uri, $uri_field)) {
      $term = Term::load($tid);
    }
    elseif ($tid = $this->getTidByName($this->name)) {
      $term = Term::load($tid);
    }
    return  isset($term) && is_object($term) ? $term->id() : 0 ;
  }
  /**
   * Load term by URI.
   */
  protected function getTidByURI($uri = NULL, $field = NULL) {
    $properties = [];
    if (!empty($uri) && !empty($field)) {
      $properties[$field] = $uri;
      // This value may come from an inherited class NameURIGenerate.
      $default_vocabulary = $this->configuration['default_vocabulary'];
      if ($default_vocabulary) {
        $properties['vid'] = $default_vocabulary;
      }
    }
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    return !empty($term) ? $term->id() : 0;
  }
  /**
   * Load term by name.
   */
  protected function getTidByName($name = NULL) {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
      // This value may come from an inherited class NameURIGenerate.
      $default_vocabulary = $this->configuration['default_vocabulary'];
      if ($default_vocabulary) {
        $properties['vid'] = $default_vocabulary;
      }
    }
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    return !empty($term) ? $term->id() : 0;
  }
}
