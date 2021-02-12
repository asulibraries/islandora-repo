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
   * @param int $collection_nid
   *   OPTIONAL collection node id() value.
   * @command asu_collection_extras:collection_summary
   * @aliases asu_cs
   * @usage asu_collection_extras:collection_summary
   * @usage asu_collection_extras:collection_summary 30
   */
  public function collection_summary($collection_nid = 0) {
    // When this runs, it may take a long time to complete.                 
    set_time_limit(0);
    if ($collection_nid > 0) {
      asu_collection_extras_doCollectionSummary($collection_nid, $this);
    } else {
      $all_collection_nids = asu_collection_extras_all_collections();
      $this->output()->writeln('All collection nodes');
      $this->output()->writeln(print_r($all_collection_nids, TRUE));
      foreach ($all_collection_nids as $nid) {
        asu_collection_extras_doCollectionSummary($nid, $this);
      }
    }
  }
  
}
