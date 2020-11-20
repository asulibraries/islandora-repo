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
    $firstKey = $this->array_key_first($collection_stats);
    $first_row = $collection_stats[$firstKey];
    $stats_table = [
        '#type' => 'table',
        '#rows' => $collection_stats,
        '#header' => array_keys($first_row),
        '#sticky' => true,
        '#caption' => '',
    ];
    $summary_row = '';
    return [
      '#form' => $form,
      '#show_csv_link' => $show_csv_link,
      '#theme' => 'asu_statistics_chart',
      '#stats' => print_r($collection_stats, true),
      '#stats_table' => $stats_table,
      '#summary_row' => $summary_row,
    ];
  }

  public function getTitle($node = NULL) {
    return (($node) ? $node->getTitle() . " " : "") . "Statistics";
  }

  public function get_stats($collection_node_id = NULL) {
    $zz = $this->solr_get_stats($collection_node_id);
    return asu_statistics_get_stats($collection_node_id);
  }

  private function array_key_first(array $array) { foreach ($array as $key => $value) { return $key; } }

  public function solr_get_stats($collection_node_id = NULL) {
    /* @var $search_api_index \Drupal\search_api\IndexInterface */
//    $search_api_index = Index::load('default_solr_index');
//    // Create the query.
//    $query = $search_api_index->query([
//      'limit'  => 500,
//      'offset' => 0,
//    ]);
//    // Set the language to search.
//    $query->setLanguages(['en']);
//    $query->setOption('search_api_retrieved_field_values', [
//      ['created' => 'created', 'nid' => 'nid', 'field_file_size' => 'field_file_size']]);
//    $conditions = $query->createConditionGroup('OR');
//    $conditions->addCondition('field_ancestors', $collection_node_id, '=')
//      ->addCondition('field_additional_memberships', $collection_node_id, '=');
//    $query->addConditionGroup($conditions);
//    $query->setOption('search_api_facets', [
//      'created' => [
//        'field' => 'created',
//        'limit' => 0,
//        'min_count' => 1,
//        'operator' => 'or',
//        'missing' => FALSE,
//        'query_type' => 'search_api_date',
//      'granularity' => 5,
//        'gap' => 5,
//  //      'start' => 1,
//      ]
//    ]);
//    $query->sort('search_api_relevance', 'DESC');
//    $results = $query->execute();
//    $facets = $results->getExtraData('search_api_facets', []);
//    $out = '';
//    $counts = [];
//    $out .= "Result count: {$results->getResultCount()}<br>";
//    $ids = implode(', ', array_keys($results->getResultItems()));
//    $out .= "Returned IDs: $ids.<br>";
//    /** @var \Drupal\search_api\Item\ItemInterface $result */
//    foreach ($results as $result) {
//      /** @var \Solarium\QueryType\Select\Result\Document $solr_document */
//      // $solr_document = $result->getExtraData('search_api_solr_document', NULL);
//      // $fields = $solr_document->getFields();
//      // check ['its_nid', 'its_field_file_size', 'ds_created']
//      $nid = $result->getField('nid')->getValues()[0];
//      $out .= '<b>nid</b> = ' . $nid . '<hr>';
//
//      $file_sizes = $result->getField('field_file_size')->getValues();
//      $file_size = (is_array($file_sizes) && count($file_sizes) > 0) ? $file_sizes[0]: 0;
//      $out .= '<b>File size</b> = ' . $file_size . '<br>';
//
//      $created = $result->getField('created')->getValues()[0];
//      $out .= '<b>created</b> = ' . $created . '<br>';
//
//      $year = date('Y', $created);
//      $month = date('m', $created);
//      $day = date('d', $created);
//      $out .= "Y = " . $year . "/" . $month . "/" . $day . "<hr>";
//    }
//    $out .= "<br>Facets data: <pre>" . var_export($facets, TRUE) . "</pre>";
//    echo "Returned results: <pre>" . $out . "</pre><br>";
    // Call the REST endpoint to get the "Authored on" created month facets.
//    $month_facet_results = $this->call_REST($collection_node_id, 'month_facets');
    // Call the REST endpoint to get the statistics of file size total.
    $sum_filesize_results = \Drupal::service('asu_statistics.rest_solr_facets')->call_REST($collection_node_id, 'sum_filesize');
  }


}
