<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Create new paragraph.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraph_title_generate"
 * )
 *
 * @code
 *    plugin: paragraph_title_generate
 *     paragraph_type: 'complex_title'
 *    split_into_parts: true
 *     fields:
 *      field_nonsort: ""
 *       field_main_title: ""
 *      field_subtitle: ""
 */
class ParagraphTitleGenerate extends ParagraphGenerate {
  /**
   * @todo would be great to pull these from a config.
   */
  protected $nonsorts = [
    'the', 'an', 'a',
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($title_string, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $split = $this->configuration['split_into_parts'];
    $delimiter = $this->configuration['delimiter'];
    $fields = $this->configuration['fields'];
    $fields['field_main_title'] = html_entity_decode(trim($title_string));
    if ($split) {
      if ($delimiter) {
        $tparts = explode($delimiter, $fields['field_main_title']);
        $tparts = array_map('trim', $tparts);
        if (count($tparts) == 3) {
          $fields['field_nonsort'] = $tparts[0];
          $fields['field_main_title'] = $tparts[1];
          $fields['field_subtitle'] = $tparts[2];
        }
        elseif (count($tparts) == 2) {
          $fields['field_main_title'] = $tparts[0];
          $fields['field_subtitle'] = $tparts[1];
        }
        else {
          $fields['field_main_title'] = $tparts[0];
        }
      }
      else {
        if (str_contains($fields['field_main_title'], ':')) {
          $tparts = explode(':', $fields['field_main_title']);
          $tparts = array_map('trim', $tparts);
          $fields['field_subtitle'] = trim(array_pop($tparts));
          $fields['field_main_title'] = trim(implode(":", $tparts));
        }
        foreach ($this->nonsorts as $ns) {
          $ns = $ns . " ";
          if (substr(strtolower($fields['field_main_title']), 0, strlen($ns)) === $ns) {
            $tparts = explode(" ", $fields['field_main_title'], 2);
            $fields['field_nonsort'] = trim($tparts[0]);
            $fields['field_main_title'] = trim(end($tparts));
            break;
          }
        }
      }
      if ($fields['field_subtitle'] == " ") {
        $fields['field_subtitle'] = NULL;
      }
      foreach ($fields as $k => $field) {
        if ($field != "" || $field != " " || $field != NULL) {
          $fields[$k] = ["value" => $field];
        }
      }
    }
    $paragraph = parent::createParagraph($this->configuration['paragraph_type'], $fields);
    return $paragraph;
  }

}
