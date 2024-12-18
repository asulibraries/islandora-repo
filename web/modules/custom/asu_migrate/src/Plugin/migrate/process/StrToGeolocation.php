<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin converts a string representation of "latitude, longitude" into
 * a geolocation.
 *
 * @MigrateProcessPlugin(
 *   id = "str_to_geolocation"
 * )
 */
class StrToGeolocation extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $geolocation = [];
    if ($value && strstr(",", $value)) {
      @list($lat, $lon) = explode(",", $value);
      $geolocation = ['lat' => $lat, 'lng' => trim($lon)];
    }
    return $geolocation;
  }

}
