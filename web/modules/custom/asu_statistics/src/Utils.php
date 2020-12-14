<?php

namespace Drupal\asu_statistics;

use Drupal\node\NodeInterface;

/**
 * Utilities for the Media Formats Reports module.
 */
class Utils {

  /**
   * Gets the data from a data source plugin.
   *
   * @param string $report_type
   *   The report type.
   *
   * @return object
   *   A Chart.js dataset object.
   */
  public function getReportData($report_type) {
    $config = \Drupal::config('islandora_repository_reports.settings');
    $cache_data = FALSE; // $config->get('islandora_repository_reports_cache_report_data');

    $data_element_counts = &drupal_static(__FUNCTION__);
    $cid = 'asu_statistics_data_' . $report_type;
    $data_source_service_id = 'asu_statistics.datasource.' . $report_type;
    $data_source = \Drupal::service($data_source_service_id);

    $collection_id = NULL;
    if ($cache_data && $cache = \Drupal::cache()->get($cid)) {
      $data_element_counts = $cache->data;
    }
    else {
      // make the report here:
      $data_element_counts = $data_source->getData();
      if ($cache_data) {
        \Drupal::cache()->set($cid, $data_element_counts);
      }
    }

    // Populate the Chart.js dataset object.
    $num_data_elements = count($data_element_counts);
    $dataset = new \StdClass();
    $dataset->data = array_values($data_element_counts);

    if ($data_source->getChartType() == 'pie') {
      if (count($data_element_counts) > 0) {
        $chart_title = $data_source->getChartTitle(array_sum($dataset->data));
      }
      else {
        $chart_title = t("No @name data available to report on.", ['@name' => $data_source->getName()]);
      }

      $chart_data = [
        'labels' => array_keys($data_element_counts),
        'datasets' => [$dataset],
        'title' => $chart_title,
      ];
    }
    if ($data_source->getChartType() == 'bar') {
      if (count($data_element_counts) > 0) {
        $chart_title = $data_source->getChartTitle(array_sum($dataset->data));
      }
      else {
        $chart_title = t("No @name data available to report on.", ['@name' => $data_source->getName()]);
      }
      $dataset->label = $data_source->getName();
      $dataset->backgroundColor = $config->get('islandora_repository_reports_bar_chart_color');
      $chart_data = [
        'labels' => array_keys($data_element_counts),
        'datasets' => [$dataset],
        'title' => $chart_title,
      ];
    }

    // Unlike Chart.js reports, HTML reports need to call the writeCsvFile()
    // method explicitly in their getData() method.
    $this->writeCsvFile($report_type, $data_source->csvData, $collection_node_id);

    return $chart_data;
  }

  /**
   * Writes the CSV file.
   *
   * @param string $report_type
   *   The report type.
   * @param string $csv_data
   *   An array of arrays corresponding to CSV records.
   * @param integer $collection_node_id
   *   Default NULL, if passed, this will be the node id for the given 
   * collection else the report would be for site-wide report.
   */
  public function writeCsvFile($report_type, $csv_data, $collection_node_id = NULL) {
    if ($tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics')) {
      if ($tempstore->get('asu_statistics_generate_csv')) {
        $default_schema = \Drupal::config('system.file')->get('default_scheme');
        $files_path = \Drupal::service('file_system')->realpath($default_schema . "://");
        $filename = 'asu_statistics_' .
         (($collection_node_id) ? 'collection_' . $collection_node_id . '_' : '').
         $report_type . '.csv';
        $fp = fopen($files_path . '/' . $filename, 'w');
        foreach ($csv_data as $fields) {
          fputcsv($fp, $fields);
        }
        fclose($fp);

        // We're finished with this session variable, so clear it for
        // the next rendering of the report page.
        $tempstore->delete('asu_statistics_generate_csv');
      }
    }
  }

}
