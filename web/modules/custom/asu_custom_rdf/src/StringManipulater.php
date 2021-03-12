<?php

namespace Drupal\asu_custom_rdf;

use Drupal\rdf\CommonDataConverter;
use Drupal\user\Entity\User;

/**
 * {@inheritdoc}
 */
class StringManipulater extends CommonDataConverter {

  /**
   * Manipulates strings for RDF output.
   *
   * @param mixed $data
   *   The array containing the 'value' element.
   * @param mixed $arguments
   *   The array containing the arguments.
   *
   * @return string
   *   Returns the string string.
   */
  public static function prependString($data, $arguments) {
    if (is_array($data) && array_key_exists('value', $data)) {
      $data = $data['value'];
    }
    if (isset($arguments['prefix']) && is_string($data)) {
      $data = $arguments['prefix'] . $data;
    }
    return $data;
  }

}
