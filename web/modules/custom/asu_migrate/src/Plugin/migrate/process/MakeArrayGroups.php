<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;


/**
 * Convert a string and a key into an associative array.
 *
 * @MigrateProcessPlugin(
 *   id = "make_array_groups"
 * )
 *
 * To transform a string into an associative array
 * to be used with sub_process:
 *
 * @code
 * genres:
 *   plugin: make_array_groups
 *   source:
 *     - names
 *     - uris
 *   keys:
 *     - name
 *     - uri
 * @endcode
 *
 * result:
 * @code
 * [
 * {"name" => "name val", "uri" => "uri val"},
 * {"name" => "name val2", "uri" => "uri val2"},
 * ]
 * @endcode
 *
 */
class MakeArrayGroups extends ProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
	    throw new MigrateException('Plugin make_array_groups requires a array input.');
    }
    \Drupal::logger('make array groups')->info(print_r($value, TRUE));
    $new_array = [];
    $labels = $value[0];
    if (count($value) > 1) {
      $uris = $value[1];
    }
    else {
      $uris = NULL;
    }
    $keys = $this->configuration['keys'];
    if (is_array($labels)){
      foreach ($labels as $index => $label) {
        $obj = [
          $keys[0] => $label,
        ];
        if (count($keys) > 1) {
          $obj[$keys[1]] = $uris[$index];
        }
        $new_array[] = $obj;
      }
    }
    else {
      $new_obj = [
        $keys[0] => $labels
      ];
      if ($uris) {
        $new_obj[$keys[1]] = $uris;
      }
      $new_array[] = $new_obj;
    }


    \Drupal::logger('make array groups')->info(print_r($new_array, TRUE));
    return $new_array;
  }
}

