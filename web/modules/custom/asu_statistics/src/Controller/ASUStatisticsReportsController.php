<?php

namespace Drupal\asu_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Url;

/**
 * Controller.
 */
class ASUStatisticsReportsController extends ControllerBase {
  /**
   * Output the report.
   *
   * The chart itself is rendered via Javascript.
   *
   * @return string
   *   Markup used by the chart and the statistics table.
   */
  public function main() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $show_csv_link = ($tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics')) ?
      $tempstore->get('asu_statistics_generate_csv') : FALSE;
    $form = \Drupal::formBuilder()->getForm('Drupal\asu_statistics\Plugin\Form\ASUStatisticsReportsReportSelectorForm');
    $collection_node_id = ($node) ? $node->id(): 0;
    // Total
    $collection_stats = $this->get_stats($collection_node_id, FALSE);
    // Public Items
    $public_items_stats = $this->get_stats($collection_node_id, TRUE);
    
    
    // Private Items (total only)
    $total_file_sizes = $this->solr_get_sum('its_field_file_size', $collection_node_id);
    $total_file_size = 0;
    $asu_utils = \Drupal::service('asu_utils');    
    foreach ($total_file_sizes as $mime_type => $sum) {
      $total_file_size += $sum['Size'];
      $total_file_sizes[$mime_type]['Size'] = $asu_utils->formatBytes($sum['Size'], 1);     
    }
    $total_items = ['total' => 0, 'public' => 0, 'private' => 0];
    foreach ($collection_stats as $year=>$totals) {
      $total_items['total'] += $totals['Total'];
    }
    foreach ($public_items_stats as $year=>$totals) {
      $total_items['public'] += $totals['Total'];
    }
    $total_items['private'] = $total_items['total'] - $total_items['public'];
    $firstKey = $this->array_key_first($collection_stats);
    $first_row = $collection_stats[$firstKey];
    $stats_table = [
        '#type' => 'table',
        '#rows' => $collection_stats,
        '#header' => array_keys($first_row),
        '#sticky' => true,
        '#caption' => '',
    ];
    $content_counts_header = ['Mime type','Attachment count','Size'];
    $content_counts_table = [
        '#type' => 'table',
        '#rows' => $total_file_sizes,
        '#header' => $content_counts_header,
        '#caption' => '',
    ];
    
///    echo "<pre style='color:black'>" . print_r(array_keys($first_row) , true) . "</pre>";
//   echo "<pre style='color:purple'>" . print_r($collection_stats, true) . "</pre>";
//    echo "<pre style='color:black'>" . print_r($content_counts_header , true) . "</pre>";
//   echo "<pre style='color:green'>" . print_r($total_file_sizes, true) . "</pre>";
//    
    $summary_row = $total_file_size;
    return [
      '#form' => $form,
      '#show_csv_link' => $show_csv_link,
      '#theme' => 'asu_statistics_chart',
      '#total_items' => $total_items,
      '#stats_table' => $stats_table,
      '#content_counts_table' => $content_counts_table,
      '#summary_row' => $summary_row,
      '#collections_by_institution' => (($collection_node_id) ? NULL : true),
    ];
  }

  public function getTitle($node = NULL) {
    return (($node) ? $node->getTitle() . " " : "") . "Statistics";
  }

  public function get_stats($collection_node_id = NULL, $status) {
    return asu_statistics_get_stats($collection_node_id, $status);
  }

  private function array_key_first(array $array) { foreach ($array as $key => $value) { return $key; } }

  /**
   * Function to run a Solr query on either the whole site or limited to a 
   * collection and return the stats getSum for the its_field_file_size field.
   * 
   * @param type $stats_field
   *   Optional field for which to return sum statistics. Default field is the
   * its_field_file_size.
   * @param int $collection_node_id
   *   Optional parameter to limit the stats to children of a collection.
   */
  public function solr_get_sum($stats_field = 'its_field_file_size', $collection_node_id = NULL) {
    $index = Index::load('default_solr_index');
    $server = $index->getServerInstance();
    $backend = $server->getBackend();
    $solrConnector = $backend->getSolrConnector();
    // first, run a facets query to get all possible mime_types
    // loop through the mime_types and get their sums
    $mime_types = ['image/jpeg', 'image/png'];
    $sums = [];
    foreach ($mime_types as $mime_type) {
      $solariumQuery = $solrConnector->getSelectQuery();
      $solariumQuery->setRows(0);    
      $solariumQuery->addParam('q', 'its_field_ancestors:' . $collection_node_id);
      $solariumQuery->addParam('q', 'sm_field_mime_type:' . $mime_type);
      $solariumQuery->addParam('rows', '0');
      $stats[$mime_type] = $solariumQuery->getStats();
      $stats[$mime_type]->createField('its_field_file_size');
      $executed = $solrConnector->execute($solariumQuery);
      $stats[$mime_type] = $executed->getStats();

      $stats[$mime_type] = $executed->getStats();
      foreach ($stats[$mime_type]->getResults() as $field) {
        $sums[] = [
          'Mime type' => $mime_type, 
          'Attachment count' => $executed->getNumFound(), 
          'Size' => $field->getSum()];
      }
    }
    return $sums;
  }
  
}
