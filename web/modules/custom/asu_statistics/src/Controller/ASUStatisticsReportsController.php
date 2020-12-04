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
    $solariumQuery = $solrConnector->getSelectQuery();
    $solariumQuery->addParam('q', 'itm_field_ancestors:' . $collection_node_id);
    $solariumQuery->setFields(array('its_nid'));

    $nids = $solrConnector->execute($solariumQuery);
    $nids_arr = [];
    foreach ($nids as $nid_doc) {
      $nids_arr[] = $nid_doc->its_nid;
    }
    // take the set of node ids and pass this into a mysql query for the 
    // node media file_size sum grouped by mime_types
    $query = \Drupal::database()->select('media_field_data', 'media_field_data');
    $query->addField('media__field_mime_type', 'field_mime_type_value');
    $query->addExpression('COUNT(media__field_file_size.field_file_size_value)', '`Attachment_count`');
    $query->addExpression('SUM(media__field_file_size.field_file_size_value)', 'Size');
//    $query->addExpression('YEAR(FROM_UNIXTIME(node_field_data.created))', 'item_year');
//    $query->addExpression('MONTH(FROM_UNIXTIME(node_field_data.created))', 'item_month');
    if ($collection_node_id) {
      $query->leftJoin('media__field_media_of', 'media__field_media_of',
        "(media_field_data.mid = media__field_media_of.entity_id AND " .
        "media__field_media_of.deleted = '0' AND (media__field_media_of.langcode " .
        "= media_field_data.langcode OR media__field_media_of.bundle IN " .
        "('audio', 'document', 'file', 'image', 'video')))");
      $query->leftJoin('media__field_file_size', 'media__field_file_size',
        "media_field_data.mid = media__field_file_size.entity_id");
      $query->leftJoin('media__field_mime_type', 'media__field_mime_type',
        "media_field_data.mid = media__field_mime_type.entity_id");
      $query->condition('media__field_media_of.field_media_of_target_id', $nids_arr, 'IN');
      $query->groupBy('media__field_mime_type.field_mime_type_value');
    }
    $result = $query->execute()->fetchAll();
    foreach ($result as $result_obj) {
      $sums[$result_obj->field_mime_type_value] = [
        'Mime type' => $result_obj->field_mime_type_value,
        'Attachment count' => $result_obj->Attachment_count,
        'Size' => $result_obj->Size
      ];
    }
    return $sums;
  }
}
/**
 * Run a solr query to get the set of node ids related to the collection by the
 * itm_field_ancestors and then pass this into a mysql query that is using a 
 * sum of field_file_size and grouped by field_mime_type.
 * 
 * 
 * 
SELECT media__field_mime_type.field_mime_type_value, 
 * COUNT(media__field_file_size.field_file_size_value), 
 * SUM(media__field_file_size.field_file_size_value)
FROM media_field_data media_field_data
LEFT JOIN media__field_media_of media__field_media_of ON media_field_data.mid = media__field_media_of.entity_id AND media__field_media_of.deleted = '0' AND (media__field_media_of.langcode = media_field_data.langcode OR media__field_media_of.bundle IN ( 'audio', 'document', 'file', 'image', 'video' ))
LEFT JOIN media__field_file_size media__field_file_size ON media_field_data.mid = media__field_file_size.entity_id
LEFT JOIN media__field_mime_type media__field_mime_type ON media_field_data.mid = media__field_mime_type.entity_id
WHERE (media__field_media_of.field_media_of_target_id IN (4, 5, 6, 7, 8, 9))
GROUP BY media__field_mime_type.field_mime_type_value
*/        
