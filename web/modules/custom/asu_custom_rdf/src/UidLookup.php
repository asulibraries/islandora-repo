<?php

namespace Drupal\asu_custom_rdf;

use Drupal\rdf\CommonDataConverter;
use Drupal\user\Entity\User;

/**
 * {@inheritdoc}
 */
class UidLookup extends CommonDataConverter {

  /**
   * Converts an Uid to a username.
   *
   * @param mixed $data
   *   The array containing the 'target_id' element.
   *
   * @return string
   *   Returns the username string.
   */
  public static function username($data) {
    if (is_array($data)) {
      if (in_array('target_id', $data)) {
        $user = User::load($data['target_id']);
        return $user->getDisplayName();
      }
      else {
        // \Drupal::logger('custom rdf')->info(print_r($data, TRUE));
      }
    }
    else {
      return $data->getDisplayName();
    }
  }
}
