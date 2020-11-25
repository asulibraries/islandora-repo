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
  public function main($node = NULL) {
    // $node = !($node) ? $this->currentRouteMatch->getParameter('node'): $node;
    $show_csv_link = ($tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics')) ?
      $tempstore->get('asu_statistics_generate_csv') : FALSE;
    $form = \Drupal::formBuilder()->getForm('Drupal\asu_statistics\Plugin\Form\ASUStatisticsReportsReportSelectorForm');
    $collection_node_id = ($node) ? $node->id(): 0;
    $collection_stats = $this->get_stats($collection_node_id);
    $total_file_sizes = $this->solr_get_sum('its_field_file_size', $collection_node_id);
echo "<pre>" . print_r($total_file_sizes, true) . "</pre>";
    $total_file_size = 0;
    foreach ($total_file_sizes as $mime_type => $sum) {
      $total_file_size += $sum['value'];
    }
    
    $firstKey = $this->array_key_first($collection_stats);
    $first_row = $collection_stats[$firstKey];
    $stats_table = [
        '#type' => 'table',
        '#rows' => $collection_stats,
        '#header' => array_keys($first_row),
        '#sticky' => true,
        '#caption' => '',
    ];
    $summary_row = $total_file_size;
    return [
      '#form' => $form,
      '#show_csv_link' => $show_csv_link,
      '#theme' => 'asu_statistics_chart',
      '#stats' => print_r($collection_stats, true),
      '#stats_table' => $stats_table,
      '#mime_type_sums' => $total_file_sizes,
      '#summary_row' => $summary_row,
    ];
  }

  public function getTitle($node = NULL) {
    return (($node) ? $node->getTitle() . " " : "") . "Statistics";
  }

  public function get_stats($collection_node_id = NULL) {
    return asu_statistics_get_stats($collection_node_id);
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
        $sums[$mime_type] = ['name' => $mime_type, 'value' => $field->getSum(), 'count' => $executed->getNumFound()];
      }
    }
    return $sums;
  }
  
}
