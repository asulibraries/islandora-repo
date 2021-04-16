<?php

namespace Drupal\asu_collection_extras\Commands;

use Drush\Commands\DrushCommands;
use Drupal\asu_collection_extras\Controller\ASUSumaryClass;

/**
 * A drush command file for collection summary tabulation.
 *
 * @package Drupal\asu_collection_extras\Commands
 */
class DrushASUCollectionSummary extends DrushCommands {

  /**
   * Drush command that displays the given text.
   *
   * @command asu_collection_extras:collection_summary
   * @option siteuri
   *   The site url for purpose of requesting the correct URI per item.
   * @aliases asu_cs collection_summary
   * @usage asu_collection_extras:collection_summary --siteuri https://localhost:8000
   */
  public function collection_summary($options = ['siteuri' => '']) {
    // Since the uri parameter must be named, the function parameter(s) would
    // relate to any value provided after the asu_cs command.

    // When this runs, it may take a long time to complete.
    set_time_limit(0);
    asu_collection_extras_doCollectionSummary($options['siteuri'], $this);
  }
  
}
