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
   * @option collection_nid
   *   The collection node id value (optional).
   * @aliases asu_cs collection_summary
   * @usage asu_collection_extras:collection_summary --siteuri https://localhost:8000
   * @usage asu_collection_extras:collection_summary --collection_nid 30 --siteuri https://localhost:8000
   */
  public function collection_summary($options = ['siteuri' => '', 'collection_nid' => 0]) {
    // Since the uri parameter must be named, the function parameter(s) would
    // relate to any value provided after the asu_cs command.

    // When this runs, it may take a long time to complete.
    set_time_limit(0);
    if ($options['collection_nid'] > 0) {
      asu_collection_extras_doCollectionSummary($options['collection_nid'], $options['siteuri'], $this);
    } else {
      $all_collection_nids = asu_collection_extras_all_collections();
      $this->output()->writeln('All collection nodes');
      $this->output()->writeln(print_r($all_collection_nids, TRUE));
      foreach ($all_collection_nids as $nid) {
        asu_collection_extras_doCollectionSummary($nid, $options['siteuri'], $this);
      }
    }
  }
  
}
