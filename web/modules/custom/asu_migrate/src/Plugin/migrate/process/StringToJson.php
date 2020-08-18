<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Passing along a string for the JSON field.
 *
 * @MigrateProcessPlugin(
 *   id = "string_to_json"
 * )
 *
 * @code
 * placeholder1:
 *   -
 *     source: History JSON
 *     plugin: skip_on_empty
 *     method: process
 *   -
 *     plugin: string_to_json
 * @endcode
 */
class StringToJson extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $value;
  }

}
