<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Create new paragraph.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraph_generate"
 * )
 *
 * @code
 *   plugin: paragraph_generate
 *   paragraph_type: 'typed_identifier'
 *   delimiter: '|'
 *   fields:
 *    field_identifier_value:
 *      order: 0
 *      type: text
 *    field_identifier_type:
 *      order: 1
 *      type: taxonomy_term
 *      lookup_field: field_identifier_predicate
 */
class ParagraphGenerate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($string, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $delimeter = $this->configuration['delimiter'];
    $fields = $this->configuration['fields'];
    if ($delimeter) {
      if (str_contains($string, $delimeter)) {
        $tparts = explode($delimeter, $string);
        $tparts = array_map('trim', $tparts);
      }
      else {
        $tparts = [$string];
      }
    }
    foreach ($fields as $k => $field) {
      if (is_array($field)) {
        if ($field['type'] == "text") {
          $fields[$k] = ["value" => $tparts[$field['order']]];
        }
        elseif ($field['type'] == "taxonomy_term") {
          $fields[$k] = ["target_id" => $this->getTidByValue($tparts[$field['order']], $field['lookup_field'])];
        }
      }
      else {
        if ($field == 0) {
          $fields[$k] = ["value" => $tparts[$field]];
        }
        if ($field != "" || $field != " " || $field != NULL) {
          $fields[$k] = ["value" => $tparts[$field]];
        }
      }
    }
    $paragraph = $this->createParagraph($this->configuration['paragraph_type'], $fields);
    return $paragraph;
  }

  public function createParagraph($type, $fields) {
    $parr = ['type' => $type] + $fields;
    $paragraph = Paragraph::create($parr);
    $paragraph->save();
    // $node =
    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

  /**
   * Load term by value.
   */
  protected function getTidByValue($value = NULL, $field = NULL) {
    $properties = [];
    if (!empty($value) && !empty($field)) {
      $properties[$field] = $value;
    }
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    return !empty($term) ? $term->id() : 0;
  }
}
