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
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $delimeter = $this->configuration['delimiter'];
    $fields = $this->configuration['fields'];
    if (!is_array($value) && $delimeter) {
      if (str_contains($value, $delimeter)) {
        $tparts = explode($delimeter, $value);
        $tparts = array_map('trim', $tparts);
      }
      else {
        $tparts = [$value];
      }
    }
    elseif (is_array($value)) {
      $tparts = $value;
    }
    foreach ($fields as $k => $field) {

      if (is_array($field)) {
        if (array_key_exists('key', $field)) {
          $order_or_key = $tparts[$field['key']];
        } else {
          $order_or_key = $tparts[$field['order']];
        }
        if ($field['type'] == "text") {
          $fields[$k] = ["value" => $order_or_key];
        }
        elseif ($field['type'] == "taxonomy_term" && $order_or_key != NULL) {
          $fields[$k] = ["target_id" => $this->getTidByValue($order_or_key, $field['lookup_field'])];
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
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    return !empty($term) ? $term->id() : 0;
  }
}
