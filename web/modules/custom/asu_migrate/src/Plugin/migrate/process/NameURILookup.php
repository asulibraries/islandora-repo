<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;
/**
 * Check if term exists and create new if doesn't.
 *
 * @MigrateProcessPlugin(
 *   id = "name_uri_lookup"
 * )
 */
class NameURILookup extends ProcessPluginBase {
  protected $name;
  protected $uri;
  /**
   * {@inheritdoc}
   */
  public function transform($name_uri_pair, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $uri_field = $this->configuration['uri_field'];
    $delimiter = $this->configuration['delimiter'];
    if (empty($name_uri_pair) || empty($delimiter)) {
      throw new MigrateSkipProcessException();
    }
    $thisone = array_map('trim', explode($delimiter, $name_uri_pair));
    if (count($thisone) > 1) {
      list($this->name, $this->uri) = $thisone;
    }
    else {
      $this->name = $thisone[0];
      $this->uri = NULL;
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
    }
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
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
    }
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    return !empty($term) ? $term->id() : 0;
  }
}
