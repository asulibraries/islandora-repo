<?php

namespace Drupal\asu_collection_extras\Commands;

use Drush\Commands\DrushCommands;
use Drupal\asu_collection_extras\Controller\MatomoSync;

/**
 * A drush command file for collection summary tabulation.
 *
 * @package Drupal\asu_collection_extras\Commands
 */
class DrushASUCalcViewsAndDownloads extends DrushCommands {

  /**
   * Drush command that does the initial pass to get all items it has .
   *
   * 1. compose requests to the Matomo service to get views and downloads.
   * 2. parse the response.
   * 3. update a single ace_items or ace_collections record that may be scanned
   * at a later time to populate the downloads.
   *
   * @command asu_collection_extras:initialize
   * @option siteuri
   *   The site url for purpose of requesting the correct URI per item.
   * @aliases asu_cs initialize
   * @usage asu_collection_extras:initialize --siteuri https://localhost:8000
   */
  public function initialize($options = ['siteuri' => '']) {
    // When this runs, it may take a long time to complete.
    set_time_limit(0);
    $connection = \Drupal::service('database');
    if (!$connection->schema()->tableExists('ace_items')) {
      \Drupal::logger('asu_collection_extras')->warning('ace_items table does not exist. Please run update.php.');
      return;
    }
    // Seems that matomo is not tracking thumbnails at all, so the media query
    // in the loop will not reduce the media objects in the media query below
    // to those with field_media_use = "Original file".
    // Set $unset_date_value variable to January 1 1970 00:00:00 GMT)
    $unset_date_value = 0;
    $items_matomo_data = \Drupal::service('islandora_matomo.default')->getAllPages($options['siteuri'] . "/items/");
    echo "\n\$items_matomo_data = \n" . print_r($items_matomo_data, TRUE) . "\n";
    // Loop through the $items_matomo_data array to populate the initial
    // views count. Downloads will be calculated during the sync method.
    $items_matomo_data = $this->rekeyData($items_matomo_data);
    if (is_array($items_matomo_data) && count($items_matomo_data) > 0) {
      foreach ($items_matomo_data as $nid => $views) {
        echo "nid = " . $nid . "\n";
        // Given the object $nid, we don't know the collection it is related to.
        $item_membership = asu_collection_extras_solr_get_node_membership($nid);
        echo "\n\$item_membership = \n" . print_r($item_membership, TRUE);
        // Use an "unset" modified value so that these may be processed in
        // a separate process.
        $connection->merge('ace_items')
          ->key(['i_nid' => $nid])
          ->fields([
            'views' => $views,
            'views_modified' => time(),
            'downloads_modified' => $unset_date_value,
          ])
          ->execute();
      }
    }
    $collections_matomo_data = \Drupal::service('islandora_matomo.default')->getAllPages($options['siteuri'] . "/collections/");
    $collections_matomo_data = $this->rekeyData($collections_matomo_data);
    echo "\n\$collections_matomo_data = \n" . print_r($collections_matomo_data, TRUE) . "\n";
    // Loop through the $collections_matomo_data array to populate the initial
    // views count.
    if (is_array($collections_matomo_data) && count($collections_matomo_data) > 0) {
      foreach ($collections_matomo_data as $nid => $views) {
        // Given the object $nid, we don't know the collection it is related to.
        $item_membership = asu_collection_extras_solr_get_node_membership($nid);
        echo "\n\$collection_membership = \n" . print_r($item_membership, TRUE);
        // Use an "unset" modified value so that these may be processed in
        // a separate process.
        $connection->merge('ace_collections')
          ->key(['c_nid' => $nid])
          ->fields([
            'views' => $views,
            'modified' => time(),
          ])
          ->execute();
      }
    }
  }

  /**
   * Drush command that displays the given text.
   *
   * NOTE: The core drush parameter uri is needed in order to get correct path
   * to each file as it processes the download counts.
   *
   * @command asu_collection_extras:sync
   * @option howmany
   *   How many items to process in this pass, default value is 100 items.
   * @aliases asu_cs sync
   * @usage asu_collection_extras:sync --uri https://localhost:8000
   * @usage asu_collection_extras:sync --uri https://localhost:8000
   */
  public function sync($options = ['howmany' => 100]) {
    // When this runs, it may take a long time to complete.
    set_time_limit(0);
    // Get $options['howmany'] records that need to be processed.
    $query = \Drupal::database()->select('ace_items', 'ace_i');
    $query->addField('ace_i', 'i_nid');
    $query->condition('downloads_modified', 0)
      ->range(0, $options['howmany']);

    $records = $query->execute();
    foreach ($records as $record) {
      MatomoSync::matomoSync($record->i_nid);
    }
  }

  /**
   * To remake the array so that there isn't a URL fragment such as "?x=10"
   * after the node id.
   *
   * @param array $matomo_data
   *   Key represents the page that was returned from matomo and the value is
   * the views for that page.
   */
  private function rekeyData(array $matomo_data) {
    $return_arr = [];
    foreach ($matomo_data as $page => $views) {
      // normalize the key for the array
      if (strstr($page, '?')) {
        list($page, $fragment) = explode("?", $page);
      }
      $return_arr[$page] = (array_key_exists($page, $return_arr) ? $return_arr[$page] + $views : $views);
    }
    return $return_arr;
  }

}
