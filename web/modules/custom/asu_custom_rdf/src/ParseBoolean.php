<?php

namespace Drupal\asu_custom_rdf;

use Drupal\rdf\CommonDataConverter;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * {@inheritdoc}
 */
class ParseBoolean extends CommonDataConverter {

  /**
   * Parses a boolean value into a string.
   *
   * @param mixed $data
   *   The array containing the 'target_id' element.
   * @param mixed $arguments
   *   The array containing the arguments.
   *
   * @return string
   *   Returns the string.
   */
  public static function tostring($data, $arguments) {
    if (is_array($data)) {
      \Drupal::logger('asu parse boolean')->info(print_r($data, TRUE));
      $value = $data['value'];
    }
    else {
      \Drupal::logger('asu parse boolean')->info("not a boolean");
      // $paragraph = $data;
    }
    \Drupal::logger('asu parse boolean')->info(print_r($arguments, TRUE));
    $string = "";
    foreach ($arguments as $key => $val) {
      $value = intval($value);
      if ($value === $key) {
        $string = $val;
      }
    }
    return $string;
  }

}
