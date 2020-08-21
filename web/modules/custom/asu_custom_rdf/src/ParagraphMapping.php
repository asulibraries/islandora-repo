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
    $main_title = $arguments['main'];
    $subtitle = $arguments['subtitle'];
    $nonsort_val = $paragraph->$nonsort->getValue();
    $main_title_val = $paragraph->$main_title->getValue();
    $subtitle_val = $paragraph->$subtitle->getValue();
    $string = "";
    if ($nonsort_val && !empty($nonsort_val)) {
      foreach ($nonsort_val[0] as $val) {
        if (is_array($val)) {
          if (!empty($val)) {
            $string .= $val[0] . " ";
          }
        }
        else {
          $string .= $val . " ";
        }
      }
    }
    if ($main_title_val && !empty($main_title_val)) {
      foreach ($main_title_val[0] as $val) {
        if (is_array($val)) {
          if (!empty($val)) {
            $string .= $val[0];
          }
        }
        else {
          $string .= $val;
        }
      }
    }
    if ($subtitle_val && !empty($subtitle_val)) {
      foreach ($subtitle_val[0] as $val) {
        if (is_array($val)) {
          if (!empty($val)) {
            $string .= ": " . $val[0];
          }
        }
        else {
          $string .= ": " . $val;
        }
      }
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
      $value = $paragraph->$field->getValue();
      if (count($value) > 0) {
        foreach ($value[0] as $val) {
          if (is_array($val)) {
            if (!empty($val)) {
              $string .= $val[0];
            }
          }
          else {
            $string .= $val;
          }
        }
      }
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
    $field = $paragraph->get($arguments['value_field'])->getValue();
    if (count($field) > 0) {
      foreach ($field[0] as $val) {
        $string .= $val;
      }
    }
    return $string;
  }

}
