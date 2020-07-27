<?php

namespace Drupal\asu_custom_rdf;

use Drupal\rdf\CommonDataConverter;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * {@inheritdoc}
 */
class ParagraphMapping extends CommonDataConverter {

  /**
   * Parses a title paragraph into a single string.
   *
   * @param mixed $data
   *   The array containing the 'target_id' element.
   * @param mixed $arguments
   *   The array containing the arguments.
   *
   * @return string
   *   Returns the title string.
   */
  public static function titlepartmerge($data, $arguments) {
    if (is_array($data)) {
      $paragraph = Paragraph::load($data['target_id']);
    }
    else {
      $paragraph = $data;
    }
    $nonsort = $arguments['nonsort'];
    $rest_of_title = $arguments['main'];
    $subtitle = $arguments['subtitle'];
    $nonsort_val = $paragraph->$nonsort->value;
    $rest_of_title_val = $paragraph->$rest_of_title->value;
    $subtitle_val = $paragraph->$subtitle->value;
    $string = "";
    if ($nonsort_val) {
      $string .= $nonsort_val;
    }
    if ($rest_of_title_val) {
      $string .= " " . $rest_of_title_val;
    }
    if ($subtitle_val) {
      $string .= ": " . $subtitle_val;
    }
    return $string;
  }

  /**
   * Outputs a single paragraph subfield.
   *
   * @param mixed $data
   *   The array containing the 'target_id' element.
   * @param mixed $arguments
   *   The array containing the arguments.
   *
   * @return string
   *   Returns the string.
   */
  public static function singlefield($data, $arguments) {
    if (is_array($data)) {
      $paragraph = Paragraph::load($data['target_id']);
    }
    else {
      $paragraph = $data;
    }
    $string = "";
    foreach ($arguments as $field) {
      $string .= $paragraph->$field->value;
    }
    return $string;
  }

  /**
   * Outputs a value, but allows additional customization in json_alter_hook.
   *
   * @param mixed $data
   *   The array containing the 'target_id' element.
   * @param mixed $arguments
   *   The array containing the arguments.
   *
   * @return string
   *   Returns the string.
   */
  public static function typedmap($data, $arguments) {
    if (is_array($data)) {
      $paragraph = Paragraph::load($data['target_id']);
    }
    else {
      $paragraph = $data;
    }
    $string = "";
    $string .= $paragraph->$arguments['value_field']->value;
    return $string;
  }

}
