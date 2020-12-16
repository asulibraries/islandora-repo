<?php

namespace Drupal\asu_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
    //xxx $form = \Drupal::formBuilder()->getForm('Drupal\asu_statistics\Plugin\Form\ASUStatisticsReportsReportSelectorForm');
    $collection_node_id = ($node) ? $node->id(): NULL;
    $current_path = \Drupal::request()->getSchemeAndHttpHost() . "/" .
        \Drupal::service('path.current')->getPath();
    $download_url = $current_path . '/download';
    $download_stat_summary_url = $current_path . '/download_stat_summary';  

    // Private Items (total only)
    $total_file_sizes = $this->solr_get_sum($collection_node_id, TRUE);
    $total_file_size = 0;
    $asu_utils = \Drupal::service('asu_utils');
    // Total
    $collection_items_stats = $this->get_stats($collection_node_id, FALSE, TRUE);
    // Public Items
    $public_items_stats = $this->get_stats($collection_node_id, TRUE, TRUE);
    if ($collection_node_id) {
      foreach ($total_file_sizes as $mime_type => $sum) {
        $total_file_size += $sum['Size'];
        $total_file_sizes[$mime_type]['Size'] = $asu_utils->formatBytes($sum['Size'], 1);
      }
    }
    else {
      // Total
      $collection_collections_stats = $this->get_stats(NULL, FALSE, FALSE);
      $total_file_sizes = $this->collections_filesizes_customsort($total_file_sizes);
      // Public Items
      $public_collections_stats = $this->get_stats(NULL, TRUE, FALSE);
      foreach ($total_file_sizes as $tid => $sum_arr) {
        $institution_name = array_keys($sum_arr)[0];
        $total_file_size += $sum_arr[$institution_name][$tid]['Size'];
        $total_file_sizes[$tid]['Institution'] = $institution_name;
        $total_file_sizes[$tid]['# of Collections'] = 0 + $sum_arr[$institution_name][$tid]['# of Collections'];
        $total_file_sizes[$tid]['Size'] = $asu_utils->formatBytes($sum_arr[$institution_name][$tid]['Size'], 1);
        unset($total_file_sizes[$tid][$institution_name]);
      }
    }

    $firstKey = $this->array_key_first($collection_items_stats);
    $first_row = $collection_items_stats[$firstKey];
    $stats_table = [
        '#type' => 'table',
        '#rows' => $collection_items_stats,
        '#header' => array_keys($first_row),
        '#sticky' => true,
        '#caption' => '',
    ];

    $total_items = $total_collections = ['total' => 0, 'public' => 0, 'private' => 0];
    if ($collection_node_id) {
      $content_counts_header = ['Mime type','Attachment count','Size'];
      foreach ($collection_items_stats as $year=>$totals) {
        $total_items['total'] += $totals['Total'];
      }
      foreach ($public_items_stats as $year=>$totals) {
        $total_items['public'] += $totals['Total'];
      }
      $total_items['private'] = $total_items['total'] - $total_items['public'];
    }
    else {
      // institution
      $content_counts_header = ['Institution', '# of Collections','Size'];
      foreach ($collection_collections_stats as $year=>$totals) {
        $total_collections['total'] += $totals['Total'];
      }
      foreach ($public_collections_stats as $year=>$totals) {
        $total_collections['public'] += $totals['Total'];
      }
      $total_collections['private'] = $total_collections['total'] - $total_collections['public'];
    }
    $content_counts_table = [
        '#type' => 'table',
        '#rows' => $total_file_sizes,
        '#header' => $content_counts_header,
        '#caption' => '',
    ];
    
    $summary_row = $total_file_size;
    return [
      //xxx '#form' => $form,
      '#download_url' => $download_url,
      '#download_stat_summary_url' => $download_stat_summary_url,
      '#theme' => 'asu_statistics_chart',
      '#total_items' => $total_items,
      '#stats_table' => $stats_table,
      '#content_counts_table' => $content_counts_table,
      '#summary_row' => $summary_row,
      '#total_collections' => $total_collections,
      '#collections_by_institution' => (($collection_node_id) ? NULL : true),
    ];
  }
  
  public function download_accessions() {
    // for data, see PublishedNodesByMonth getData method
    // also for download method, see asu_statistics.module asu_statistics_file_download method.
    $node = \Drupal::routeMatch()->getParameter('node');
    $collection_node_id = ($node) ? $node->id(): NULL;
    $collection_items_stats = $this->get_stats($collection_node_id, FALSE, TRUE);
    $firstKey = $this->array_key_first($collection_items_stats);
    $first_row = $collection_items_stats[$firstKey];
    $written_filename = $this->writeCSV('accessions', $collection_node_id, array_keys($first_row), $collection_items_stats);
    return $this->do_csv_download($written_filename);
    // return $this->redirect_to_statpage($collection_node_id);
  }  
  
  public function download_stat_summary() {
    // for data, see PublishedNodesByMonth getData method
    // also for download method, see asu_statistics.module asu_statistics_file_download method.
    $node = \Drupal::routeMatch()->getParameter('node');
    $collection_node_id = ($node) ? $node->id(): NULL;
    $tmp = $this->solr_get_sum($collection_node_id, TRUE);
    $headers = ($collection_node_id) ?
      ['Mime type','Attachment count','Size (bytes)'] :
      ['Institution', '# of Collections','Size (bytes)'];
    // due to how the site-statistics array has three tiers, we must loop
    // through and adjust it for output.
    if (!$collection_node_id) {
      foreach ($tmp as $tid => $institution_arr) {
        $institution_name = $this->first_array_key($institution_arr);
        $inner_arr = [
          'Institution' => $institution_name,
          '# of Collections' => $institution_arr[$institution_name][$tid]['# of Collections'] + 0,
          'Size (bytes)' => $institution_arr[$institution_name][$tid]['Size (bytes)'] + 0,
        ];
        $total_file_sizes[] = $inner_arr; 
      }
    }
    else {
      $total_file_sizes = $tmp;
    }
    $written_filename = $this->writeCSV('summary', $collection_node_id, $headers, $total_file_sizes);
    return $this->do_csv_download($written_filename);
    // return $this->redirect_to_statpage($collection_node_id);
  }

  public function redirect_to_statpage($collection_node_id) {
    $url_str = \Drupal::request()->getSchemeAndHttpHost() . 
      (($collection_node_id) ? '/collections/' . $collection_node_id . '/statistics':
      '/admin/reports/asu_statistics');
    return new RedirectResponse($url_str);
  }

  public function do_csv_download($written_filename) {
    $headers = [
      'Content-Type' => 'text/csv',
      'Content-Description' => 'File Download',
      'Content-Disposition' => 'attachment; filename=' . $written_filename,
    ];
    $uri = \Drupal::config('system.file')->get('default_scheme') . "://" . $written_filename;
    // Return and trigger file download.
    return new BinaryFileResponse($uri, 200, $headers, true);
  }

  public function first_array_key(array $arr) {
    foreach($arr as $key => $unused) {
      return $key;
    }
    return NULL;
  }
  
  public function writeCSV($report_type, $collection_node_id, $header_row = array(), $data = array()) {
    $default_schema = \Drupal::config('system.file')->get('default_scheme');
    $files_path = \Drupal::service('file_system')->realpath($default_schema . "://");
    $filename = date('Ymd') . '_asu_statistics_' .
      (($collection_node_id) ? 'collection_' . $collection_node_id . '_' : '').
      $report_type . '.csv';    
    $fp = fopen($files_path . '/' . $filename, 'w');
    fputcsv($fp, $header_row);
    foreach ($data as $key => $fields) {
      fputcsv($fp, $fields);
    }
    fclose($fp);
    return $filename;
  }
  
  public function getTitle($node = NULL) {
    return (($node) ? $node->getTitle() . " " : "") . "Statistics";
  }

  public function get_stats($collection_node_id = NULL, $status, $count_items = TRUE) {
    return asu_statistics_get_stats($collection_node_id, $status, $count_items);
  }

  private function array_key_first(array $array) { foreach ($array as $key => $value) { return $key; } }

  /**
   * Function to run a Solr query on either the whole site or limited to a 
   * collection and return the stats getSum for the its_field_file_size field.
   * 
   * @param int $collection_node_id
   *   Optional parameter to limit the stats to children of a collection.
   * @param boolean $mime_type_facet
   *   Default = TRUE, whether or not to split sums up by the mime_type values.
   */
  public function solr_get_sum($collection_node_id = NULL, $mime_type_facet = TRUE) {
    if ($collection_node_id) {
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
      if ($mime_type_facet) {
        $query->addField('media__field_mime_type', 'field_mime_type_value');
      }
      $query->addExpression('COUNT(media__field_file_size.field_file_size_value)', '`Attachment_count`');
      $query->addExpression('SUM(media__field_file_size.field_file_size_value)', 'Size');
      $query->leftJoin('media__field_media_of', 'media__field_media_of',
        "(media_field_data.mid = media__field_media_of.entity_id AND " .
        "media__field_media_of.deleted = '0' AND (media__field_media_of.langcode " .
        "= media_field_data.langcode OR media__field_media_of.bundle IN " .
        "('audio', 'document', 'file', 'image', 'video')))");
      $query->leftJoin('media__field_file_size', 'media__field_file_size',
        "media_field_data.mid = media__field_file_size.entity_id");
      if ($mime_type_facet) {
        $query->leftJoin('media__field_mime_type', 'media__field_mime_type',
          "media_field_data.mid = media__field_mime_type.entity_id");
      }

      $query->condition('media__field_media_of.field_media_of_target_id', $nids_arr, 'IN');
      if ($mime_type_facet) {
        $query->groupBy('media__field_mime_type.field_mime_type_value');
      }
      $s = (string)$query;

      $result = $query->execute()->fetchAll();
      if ($mime_type_facet) {
        foreach ($result as $result_obj) {
          $sums[$result_obj->field_mime_type_value] = [
            'Mime type' => $result_obj->field_mime_type_value,
            'Attachment count' => $result_obj->Attachment_count,
            'Size' => $result_obj->Size];
        }
      }
      else {
        foreach ($result as $result_obj) {
          $sums[] = [
            '# of Collections' => $result_obj->Attachment_count,
            'Size' => $result_obj->Size];
        }
      }
    }
    else {
      // first get the set of Institutions and loop through to get the count of
      // collections and file sizes of each collection.
      // TODO: make a function to get all possible institutions.
      $vocab = Vocabulary::load('collaborating_institutions');
      $vid = $vocab->id();
      $institutions = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, 0, NULL, TRUE);
      foreach ($institutions as $institution) {
        $institution_name = $institution->getName();
        // make the key of this array by using the term tid value in the event
        // that we want the institution text to be a link to that taxonomy term.
        $id = $institution->id();
        $sums[$id] = [$institution_name => $this->get_institution_collection_sums($id)];
      }
    }
    return $sums;
  }

  /**
   *
   * For each collection that is related to the given institution, this will
   * need to run a solr query to get the sum of items' media field_file_size.
   *
   * @param integer $institution_tid
   *   This is the taxonomy term related to an institution.
   * @return array
   *   An array with values for Size and # of Collections related to the institution.
   */
  public function get_institution_collection_sums($institution_tid = 0) {
    $collection_nids = $this->get_institution_collections($institution_tid);
    $collection_sums = [];
    $collection_sums[$institution_tid]['Size'] = 0;
    foreach ($collection_nids as $collection_nid) {
      $collection_sum = $this->solr_get_sum($collection_nid, FALSE);
      $collection_sums[$institution_tid]['Size'] += $collection_sum[0]['Size'];
    }
    $collection_sums[$institution_tid]['# of Collections'] = count($collection_nids);
    return $collection_sums;
  }

  /**
   * Will get the collection node ids related to an instition.
   *
   * @param integer $institution_tid
   *   This is the taxonomy term related to an institution.
   * @return array
   *   An array of collection node ids related to the institution.
   */
  public function get_institution_collections($institution_tid) {
    // this will run a MySQL query and return the set of collection node id
    // values that are related to the given institution tid.
    $query = \Drupal::database()->select('node_field_data', 'node_field_data');
    $query->addField('node_field_data', 'nid');
    $query->leftJoin('node__field_collaborating_institutions', 'node__field_collaborating_institutions',
      "node_field_data.nid = node__field_collaborating_institutions.entity_id");
    $query->condition('node_field_data.type', 'collection');
    $query->condition('node__field_collaborating_institutions.field_collaborating_institutions_target_id', $institution_tid);
    $result = $query->execute()->fetchAll();
    $collection_nids = [];
    foreach ($result as $result_obj) {
      $collection_nids[$result_obj->nid] = $result_obj->nid;
    }    
    return $collection_nids;
  }

  /**
   * Custom usort callback to handle the sort of collections stats by the Size
   * values of the array elements.
   *
   * @param array $total_file_sizes
   *   Array of file size and # of Collections that is keyed by institution. 
   * For example, a value for $a or $b may be:
   *   [ASU Library] => [1100 => ['Size' => 40588394, '# of Collections' => 3']]
   * @return array
   *   Sorted array that is of the same structure as the incoming array.
   */
  function collections_filesizes_customsort($total_file_sizes) {
    // The usort() method cannot be used because it is not able to compare the
    // deeper elements while shifting around their parent elements.
    // 
    $ret_total_file_sizes = [];
    $institution_tids = array_keys($total_file_sizes);
    $elem_count = count($total_file_sizes);

    $institution_names = $array = [];
    for ($i = 0; $i < $elem_count; $i++) {
      $a = $total_file_sizes[$institution_tids[$i]];
      $first_key = $this->first_array_key($a);
      $a = array_shift($a)[$institution_tids[$i]];
      $institution_names[] = $first_key;
      $a['index'] = $i;
      $array[] = $a;
    }

    // Time to dust off the old bubble sort.
    $j=0;
    $flag = true;
    $temp=0;
    while ( $flag )
    {
      $flag = false;
      for( $j=0;  $j < count($array)-1; $j++)
      {
        if ( $array[$j]["Size"] < $array[$j+1]["Size"] )
        {
          $temp = $array[$j];
          //swap the two between each other
          $array[$j] = $array[$j+1];
          $array[$j+1]=$temp;
          $flag = true; //show that a swap occurred
        }
      }
    }
    foreach ($array as $index => $inner_arr) {
      $use_index = $inner_arr['index'];
      $ret_total_file_sizes[$institution_tids[$use_index]][$institution_names[$use_index]][$institution_tids[$use_index]] = $inner_arr;
    }
    return $ret_total_file_sizes;
  }
}
