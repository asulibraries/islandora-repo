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
 *   id = "typed_relation_generate"
 * )
 */
class TypedRelationGenerate extends NameURIGenerate {

  /**
   * {@inheritdoc}
   */
  public function transform($name_uri_pair, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $relator = $this->configuration['relator'];
    $term = parent::transform($name_uri_pair, $migrate_executable, $row, $destination_property);
    $typed_relation = [
      'rel_type' => $relator,
      'target_id' => $term
    ];
    return $typed_relation;
  }

}
