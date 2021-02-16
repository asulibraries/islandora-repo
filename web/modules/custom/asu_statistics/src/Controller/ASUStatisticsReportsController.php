<?php

namespace Drupal\asu_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Entity\Index;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller.
 */
class ASUStatisticsReportsController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The currentRouteMatch definition.
   *
   * @var CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * The fileSystem definition.
   *
   * @var fileSystem
   */
  protected $fileSystem;

  /**
   * The pathCurrent definition.
   *
   * @var pathCurrent
   */
  protected $pathCurrent;

  /**
   * The database definition.
   *
   * @var database
   */
  protected $database;

  /**
   * The service definition.
   *
   * @var service
   */
  protected $service;

  /**
   * The asuUtils definition.
   *
   * @var asuUtils
   */
  protected $asuUtils;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentRouteMatch = $container->get('current_route_match');
    $instance->requestStack = $container->get('request_stack');
    $instance->fileSystem = $container->get('file_system');
    $instance->pathCurrent = $container->get('path.current');
    $instance->database = $container->get('database');
    $instance->asuUtils = $container->get('asu_utils');
    return $instance;
  }

  /**
   * Output the report.
   *
   * The chart itself is rendered via Javascript.
   *
   * @return string
   *   Markup used by the chart and the statistics table.
   */
  public function main() {
    $node = $this->currentRouteMatch->getParameter('node');
    $collection_node_id = ($node) ? $node->id() : NULL;
    $current_path = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . "/" .
        $this->pathCurrent->getPath();
    $download_url = $current_path . '/download';
    $download_stat_summary_url = $current_path . '/downloadStatSummary';
    $download_downloads_url = $current_path . '/downloadDownloadStats';
    // Private Items (total only)
    $total_file_sizes = $this->solrGetSum($collection_node_id, TRUE);
    $total_file_size = 0;
    // Total.
    $collection_items_stats = $this->getStats($collection_node_id, FALSE, TRUE);
    // Public Items.
    $public_items_stats = $this->getStats($collection_node_id, TRUE, TRUE);
    if ($collection_node_id) {
      $collection_downloads = $this->getCollectionDownloads($collection_node_id);
      foreach ($total_file_sizes as $mime_type => $sum) {
        $total_file_size += $sum['Size'];
        $total_file_sizes[$mime_type]['Size'] = $this->asuUtils->formatBytes($sum['Size'], 1);
      }
    }
    else {
      // Total.
      $collection_collections_stats = $this->getStats(NULL, FALSE, FALSE);
      $total_file_sizes = $this->collectionsFilesizesCustomsort($total_file_sizes);
      // Public Items.
      $public_collections_stats = $this->getStats(NULL, TRUE, FALSE);
      foreach ($total_file_sizes as $tid => $sum_arr) {
        $institution_name = array_keys($sum_arr)[0];
        $total_file_size += $sum_arr[$institution_name][$tid]['Size'];
        $total_file_sizes[$tid]['Institution'] = $institution_name;
        $total_file_sizes[$tid]['# of Collections'] = 0 + $sum_arr[$institution_name][$tid]['# of Collections'];
        $total_file_sizes[$tid]['Size'] = $this->asuUtils->formatBytes($sum_arr[$institution_name][$tid]['Size'], 1);
        unset($total_file_sizes[$tid][$institution_name]);
      }
    }

    $firstKey = $this->firstArrayKey($collection_items_stats);
    $first_row = $collection_items_stats[$firstKey];
    $stats_table = [
      '#type' => 'table',
      '#rows' => $collection_items_stats,
      '#header' => array_keys($first_row),
      '#sticky' => TRUE,
      '#caption' => '',
    ];

    $total_items = $total_collections = [
      'total' => 0,
      'public' => 0,
      'private' => 0,
    ];
    foreach ($collection_items_stats as $totals) {
      $total_items['total'] += $totals['Total'];
    }
    foreach ($public_items_stats as $totals) {
      $total_items['public'] += $totals['Total'];
    }
    $total_items['private'] = $total_items['total'] - $total_items['public'];
    if ($collection_node_id) {
      foreach ($collection_downloads['collection_download_rows'] as $nid => $arr) {
        unset($collection_downloads['collection_download_rows'][$nid]['raw_title']);
      }
      $downloads_header = ['Title', 'Downloads', 'URL'];
      $downloads_table = [
        '#type' => 'table',
        '#rows' => $collection_downloads['collection_download_rows'],
        '#header' => $downloads_header,
        '#caption' => '',
      ];
      $downloads_total = $collection_downloads['collection_downloads'];
      $content_counts_header = ['Mime type', 'Attachment count', 'Size'];
    }
    else {
      $downloads_table = $downloads_total = '';
      // Institution.
      $content_counts_header = ['Institution', '# of Collections', 'Size'];
      foreach ($collection_collections_stats as $totals) {
        $total_collections['total'] += $totals['Total'];
      }
      foreach ($public_collections_stats as $totals) {
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
      '#download_url' => $download_url,
      '#download_stat_summary_url' => $download_stat_summary_url,
      '#download_downloads_url' => $download_downloads_url,
      '#theme' => 'asu_statistics_chart',
      '#total_items' => $total_items,
      '#stats_table' => $stats_table,
      '#content_counts_table' => $content_counts_table,
      '#downloads_table' => $downloads_table,
      '#downloads_total' => $downloads_total,
      '#summary_row' => $summary_row,
      '#total_collections' => $total_collections,
      '#collections_by_institution' => (($collection_node_id) ? NULL : TRUE),
    ];
  }

  /**
   * The download method for the controller.
   *
   * @return binary
   *   Stream of the CSV file.
   */
  public function downloadAccessions() {
    $node = $this->currentRouteMatch->getParameter('node');
    $collection_node_id = ($node) ? $node->id() : NULL;
    $collection_items_stats = $this->getStats($collection_node_id, FALSE, TRUE);
    $firstKey = $this->firstArrayKey($collection_items_stats);
    $first_row = $collection_items_stats[$firstKey];
    $written_filename = $this->writeCsv('accessions', $collection_node_id, array_keys($first_row), $collection_items_stats);
    return $this->doCsvDownload($written_filename);
  }

  /**
   * The download downloads method for the controller.
   *
   * @return binary
   *   Stream of the summary CSV file.
   */
  public function downloadStatSummary() {
    $total_file_sizes = [];
    $node = $this->currentRouteMatch->getParameter('node');
    $collection_node_id = ($node) ? $node->id() : NULL;
    $tmp = $this->solrGetSum($collection_node_id, TRUE);
    $headers = ($collection_node_id) ?
      ['Mime type', 'Attachment count', 'Size (bytes)'] :
      ['Institution', '# of Collections', 'Size (bytes)'];
    // Due to how the site-statistics array has three tiers, we must loop
    // through and adjust it for output.
    if (!$collection_node_id) {
      foreach ($tmp as $tid => $institution_arr) {
        $institution_name = $this->firstArrayKey($institution_arr);
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
    $written_filename = $this->writeCsv('summary', $collection_node_id, $headers, $total_file_sizes);
    return $this->doCsvDownload($written_filename);
  }

  /**
   * The download summary method for the controller.
   *
   * @return binary
   *   Stream of the summary CSV file.
   */
  function downloadDownloadStats() {
    $rows = [];
    $node = $this->currentRouteMatch->getParameter('node');
    $collection_node_id = ($node) ? $node->id() : NULL;
    $tmp = $this->getCollectionDownloads($collection_node_id, FALSE);
    $headers = ['Title', 'Downloads', 'URL'];
    // Due to how the site-statistics array has three tiers, we must loop
    // through and adjust it for output.
    foreach ($tmp['collection_download_rows'] as $nid => $arr) {
      unset($tmp['collection_download_rows'][$nid]['title']);
      $inner_arr = [
        'Title' => trim($arr['raw_title']),
        'Downloads' => $arr['downloads'] + 0,
        'URL' => $arr['url'],
      ];
      $rows[] = $inner_arr;
    }
    $written_filename = $this->writeCsv('downloads', $collection_node_id, $headers, $rows);
    return $this->doCsvDownload($written_filename);
  }


  /**
   * Will cause the browser to download the given file.
   *
   * @param string $written_filename
   *   The file for downloading.
   *
   * @return Symfony\Component\HttpFoundation\BinaryFileResponse\BinaryFileResponse
   *   File.
   */
  public function doCsvDownload($written_filename) {
    $headers = [
      'Content-Type' => 'text/csv',
      'Content-Description' => 'File Download',
      'Content-Disposition' => 'attachment; filename=' . $written_filename,
    ];
    $uri = $this->config('system.file')->get('default_scheme') . "://" . $written_filename;
    // Return and trigger file download.
    return new BinaryFileResponse($uri, 200, $headers, TRUE);
  }

  /**
   * Replacement for deprecated function.
   *
   * @param array $arr
   *   The array to inspect.
   *
   * @return mixed
   *   "String" or NULL based on whether or not there are any array elements.
   */
  public function firstArrayKey(array $arr) {
    foreach ($arr as $key => $unused) {
      return $key;
    }
    return NULL;
  }

  /**
   * The helper method for the given page to make a download CSV.
   *
   * @param string $report_type
   *   Value "accessions" or "summary".
   * @param mixed $collection_node_id
   *   The collection node id() or NULL.
   * @param array $header_row
   *   The headers for the CSV table.
   * @param array $data
   *   The rows for the CSV table.
   *
   * @return string
   *   The filename of the CSV file that was created.
   */
  public function writeCsv($report_type, $collection_node_id, array $header_row = [], array $data = []) {
    $default_schema = $this->config('system.file')->get('default_scheme');
    $files_path = $this->fileSystem->realpath($default_schema . "://");
    $filename = date('Ymd') . '_asu_statistics_' .
      (($collection_node_id) ? 'collection_' . $collection_node_id . '_' : '') .
      $report_type . '.csv';
    $fp = fopen($files_path . '/' . $filename, 'w');
    fputcsv($fp, $header_row);
    foreach ($data as $fields) {
      fputcsv($fp, $fields);
    }
    fclose($fp);
    return $filename;
  }

  /**
   * Gets a "Statistics title" for the page title callback.
   *
   * @param object $node
   *   A drupal node object.
   *
   * @return string
   *   The title value.
   */
  public function getTitle($node = NULL) {
    return (($node) ? $node->getTitle() . " " : "") . "Statistics";
  }

  /**
   * Get the stats for a given page.
   *
   * @param mixed $collection_node_id
   *   Optional parameter to limit the stats to children of a collection.
   * @param bool $status
   *   Published status for query.
   * @param bool $count_items
   *   Whether or not to group by collection.
   *
   * @return array
   *   A build array for the page to display the stats table.
   */
  public function getStats($collection_node_id = NULL, $status = FALSE, $count_items = TRUE) {
    $query = $this->database->select('node_field_data', 'node_field_data');
    $query->addExpression('COUNT(node_field_data.nid)', 'items');
    $query->addExpression('YEAR(FROM_UNIXTIME(node_field_data.created))', 'item_year');
    $query->addExpression('MONTH(FROM_UNIXTIME(node_field_data.created))', 'item_month');
    if ($collection_node_id) {
      $query->join(
        'node__field_member_of',
        'node__field_member_of',
        'node__field_member_of.entity_id = node_field_data.nid'
      );
      $query->condition('node__field_member_of.field_member_of_target_id', $collection_node_id);
    }
    if (!$count_items) {
      $query->condition('node_field_data.type', 'collection');
    }
    if ($status) {
      $query->condition('node_field_data.status', 1);
    }
    $query->groupBy('YEAR(FROM_UNIXTIME(node_field_data.created)), MONTH(FROM_UNIXTIME(node_field_data.created))');
    $result = $query->execute()->fetchAll();

    return $this->makeTableRowsFromResult($result);
  }

  /**
   * Gets the stats getSum for the its_field_file_size field.
   *
   * @param int $collection_node_id
   *   Optional parameter to limit the stats to children of a collection.
   * @param bool $mime_type_facet
   *   Default = TRUE, whether or not to split sums up by the mime_type values.
   */
  public function solrGetSum($collection_node_id = NULL, $mime_type_facet = TRUE) {
    $sums = [];
    if ($collection_node_id) {
      $nids_arr = $this->getCollectionNids($collection_node_id);
      // Take the set of node ids and pass this into a mysql query for the
      // node media file_size sum grouped by mime_types.
      $query = $this->database->select('media_field_data', 'media_field_data');
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
      $result = $query->execute()->fetchAll();
      if ($mime_type_facet) {
        foreach ($result as $result_obj) {
          $sums[$result_obj->field_mime_type_value] = [
            'Mime type' => $result_obj->field_mime_type_value,
            'Attachment count' => $result_obj->Attachment_count,
            'Size' => $result_obj->Size,
          ];
        }
      }
      else {
        foreach ($result as $result_obj) {
          $sums[] = [
            '# of Collections' => $result_obj->Attachment_count,
            'Size' => $result_obj->Size,
          ];
        }
      }
    }
    else {
      // First get the set of Institutions and loop through to get the count of
      // collections and file sizes of each collection.
      $institutions = $this->entityTypeManager()->getStorage('taxonomy_term')->loadTree('collaborating_institutions', 0, NULL, TRUE);
      foreach ($institutions as $institution) {
        $institution_name = $institution->getName();
        // Make the key of this array by using the term tid value in the event
        // that we want the institution text to be a link to that taxonomy term.
        $id = $institution->id();
        $sums[$id] = [$institution_name => $this->getInstitutionCollectionSums($id)];
      }
    }
    return $sums;
  }


  /**
   * Gets the Collection Nids using ancestors by running a Solr query.
   *
   * @param int $collection_node_id
   *   Optional parameter to limit the stats to children of a collection.
   */
  public function getCollectionNids($collection_node_id = NULL) {
    $nids_arr = [];
    if (!is_null($collection_node_id)) {
      $index = Index::load('default_solr_index');
      $server = $index->getServerInstance();
      $backend = $server->getBackend();
      $solrConnector = $backend->getSolrConnector();
      $solariumQuery = $solrConnector->getSelectQuery();
      // @todo Fix this so it loops through a reasonable amount of records
      // instead of setting a dangerously high value.
      $solariumQuery->setRows(2147483630);
      $solariumQuery->addParam('q', 'itm_field_ancestors:' . $collection_node_id);
      $solariumQuery->setFields(['its_nid']);
      $nids = $solrConnector->execute($solariumQuery);
      foreach ($nids as $nid_doc) {
        $nids_arr[] = $nid_doc->its_nid;
      }
    }
    return $nids_arr;
  }

  /**
   * Gets the Collection download sum for.
   *
   * @param int $collection_node_id
   *   Optional parameter to limit the stats to children of a collection.
   * @param bool $limited_display
   *   Default is TRUE when FALSE all results for the collection are returned.
   */
  public function getCollectionDownloads($collection_node_id = NULL, $limited_display = TRUE) {
    $collection_downloads = 0;
    $collection_download_rows = [];
    if (!$this->database->schema()->tableExists('asu_collection_extras_item_downloads')) {
      \Drupal::logger('asu_collection_extras')->warning('asu_collection_extras_item_downloads table does not exist. Re-install asu_collection_extras module or run SQL:
  ' . "CREATE TABLE `asu_collection_extras_item_downloads` (
  `collection_nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The collection \"node\".nid this record affects.',
  `nid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The item \"node\".nid this record affects.',
  `views` int(11) NOT NULL DEFAULT '0' COMMENT 'View total for all objects in the collection.',
  `downloads` int(11) NOT NULL DEFAULT '0' COMMENT 'Download total for all objects in the collection.',
  PRIMARY KEY (`collection_nid`,`nid`),
  KEY `downloads` (`downloads`),
  KEY `collection_nid` (`collection_nid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Store the # of downloads for each asu_repository_item'");
      return;
    }
    // Query the mysql summary table and order by downloads.
    $query = $this->database
      ->select('asu_collection_extras_item_downloads', 'a')
      ->fields('a', ['nid', 'downloads'])
      ->condition('a.collection_nid', $collection_node_id);
    if ($limited_display) {
      $query->condition('a.downloads', 0, '>');
      $query->range(0, 50);
    }
    $query->orderBy('a.downloads', 'DESC');
    $collection_item_views = $query
      ->execute()
      ->fetchAll();
    $options = ['absolute' => TRUE];
    foreach ($collection_item_views as $c_obj) {
      $nid = $c_obj->nid;
      $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $nid], $options);
      $node_title = $this->getNodeComplexTitle($nid);
      $link = Link::fromTextAndUrl($node_title, $url);
      $link = $link->toRenderable();
      $collection_download_rows[$nid] = [
        'raw_title' => $node_title,
        'title' => render($link),
        'downloads' => $c_obj->downloads,
        'url' => $url->toString()
      ];
      $collection_downloads += $c_obj->downloads;
   }
    return [
      'collection_downloads' => $collection_downloads,
      'collection_download_rows' => $collection_download_rows
    ];
  }

  /**
   * Returns the complex title value of a node.
   *
   * @param int $nid
   *   This is the node id() value.
   *
   * @return string
   *   The title value of a node as rendered via complex title field values.
   */
  public function getNodeComplexTitle(int $nid) {
    $title = '';
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if (is_object($node) && $node->bundle() == 'asu_repository_item') {
      $first_title =  $node->field_title[0];
      $view = ['type' => 'complex_title_formatter'];
      $first_title_view = $first_title->view($view);
      $title = \Drupal::service('renderer')->render($first_title_view);
    }
    return $title;
  }

  /**
   * Returns the sum of items' media field_file_size.
   *
   * @param int $institution_tid
   *   This is the taxonomy term related to an institution.
   *
   * @return array
   *   An array with values for Size and # of Collections per institution.
   */
  public function getInstitutionCollectionSums($institution_tid = 0) {
    $collection_nids = $this->getInstitutionCollections($institution_tid);
    $collection_sums = [];
    $collection_sums[$institution_tid]['Size'] = 0;
    foreach ($collection_nids as $collection_nid) {
      $collection_sum = $this->solrGetSum($collection_nid, FALSE);
      $collection_sums[$institution_tid]['Size'] += $collection_sum[0]['Size'];
    }
    $collection_sums[$institution_tid]['# of Collections'] = count($collection_nids);
    return $collection_sums;
  }

  /**
   * Will get the collection node ids related to an instition.
   *
   * @param int $institution_tid
   *   This is the taxonomy term related to an institution.
   *
   * @return array
   *   An array of collection node ids related to the institution.
   */
  public function getInstitutionCollections($institution_tid) {
    // This will run a MySQL query and return the set of collection node id
    // values that are related to the given institution tid.
    $query = $this->database->select('node_field_data', 'node_field_data');
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
   * Custom sort handle the sort of collections stats by the Size.
   *
   * @param array $total_file_sizes
   *   Array of file size and # of Collections that is keyed by institution.
   *   For example, a value for $a or $b may be:
   *   [ASU Library] => [1100 => ['Size' => 4058394, '# of Collections' => 3']].
   *
   * @return array
   *   Sorted array that is of the same structure as the incoming array.
   */
  public function collectionsFilesizesCustomsort(array $total_file_sizes) {
    // The usort() method cannot be used because it is not able to compare the
    // deeper elements while shifting around their parent elements.
    //
    $ret_total_file_sizes = [];
    $institution_tids = array_keys($total_file_sizes);
    $elem_count = count($total_file_sizes);

    $institution_names = $array = [];
    for ($i = 0; $i < $elem_count; $i++) {
      $a = $total_file_sizes[$institution_tids[$i]];
      $first_key = $this->firstArrayKey($a);
      $a = array_shift($a)[$institution_tids[$i]];
      $institution_names[] = $first_key;
      $a['index'] = $i;
      $array[] = $a;
    }

    // Time to dust off the old bubble sort.
    $j = 0;
    $flag = TRUE;
    $temp = 0;
    while ($flag) {
      $flag = FALSE;
      for ($j = 0; $j < count($array) - 1; $j++) {
        if ($array[$j]["Size"] < $array[$j + 1]["Size"]) {
          $temp = $array[$j];
          // Swap the two between each other.
          $array[$j] = $array[$j + 1];
          $array[$j + 1] = $temp;
          // Show that a swap occurred.
          $flag = TRUE;
        }
      }
    }
    foreach ($array as $inner_arr) {
      $use_index = $inner_arr['index'];
      $ret_total_file_sizes[$institution_tids[$use_index]][$institution_names[$use_index]][$institution_tids[$use_index]] = $inner_arr;
    }
    return $ret_total_file_sizes;
  }

  /**
   * Returns build array from data - filling in any gaps with 0s.
   *
   * @param array $result
   *   The incoming data array that will define the build array.
   */
  public function makeTableRowsFromResult(array $result) {
    $build_output = $rows = [];
    $month_names = [];
    for ($month = 1; $month < 13; $month++) {
      $month_names[$month] = date("F", mktime(0, 0, 0, $month, 10));
    }

    // Get all of the rows into an array where each row contains the results for
    // the query result which have records with these years / months.
    $earliest_year = PHP_INT_MAX;
    $latest_year = 0;
    foreach ($result as $child_obj) {
      if ($child_obj->item_year < $earliest_year) {
        $earliest_year = $child_obj->item_year;
      }
      if ($child_obj->item_year > $latest_year) {
        $latest_year = $child_obj->item_year;
      }
      $record_arr = (array) $child_obj;
      $record_arr['created'] = mktime(0, 0, 0, $child_obj->item_month, 10);
      $record_arr['item_month_name'] = $month_names[$child_obj->item_month];
      $pad0_month = (($child_obj->item_month < 10) ? "0" : "") . $child_obj->item_month;
      $rows[$child_obj->item_year . "-" . $pad0_month] = $record_arr;
    }

    // Fill any empty months with a 0.
    for ($year = $earliest_year; $year <= $latest_year; $year++) {
      $build_output[$year]['Year'] = $year;
      for ($month = 1; $month < 13; $month++) {
        $pad0_month = (($month < 10) ? "0" : "") . $month;
        $year_month_key = $year . "-" . $pad0_month;
        $month_key = substr($month_names[$month], 0, 3);
        if (!array_key_exists($year_month_key, $rows)) {
          $build_output[$year][$month_key] = 0;
        }
        else {
          $build_output[$year][$month_key] = $rows[$year_month_key]['items'];
        }
      }
    }

    // now, add a row total at the end.
    foreach ($build_output as $year => $month_data) {
      $row_total = 0;
      $skip_first_cell = TRUE;
      foreach ($month_data as $items) {
        $row_total += ($skip_first_cell) ? 0 : $items;
        $skip_first_cell = FALSE;
      }

      $build_output[$year]['Total'] = $row_total;
    }

    return $build_output;
  }

}
