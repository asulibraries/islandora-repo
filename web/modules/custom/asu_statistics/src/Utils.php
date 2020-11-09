<?php

namespace Drupal\asu_statistics;

use Drupal\node\NodeInterface;

/**
 * Utilities for the Media Formats Reports module.
 */
class Utils {

  /**
   * Gets a list of data source services to be used in the report selector form.
   *
   * @param bool $ids_only
   *   Whether to return an array of service IDs instead of an
   *   associative array of ids/names.
   *
   * @return array
   *   Associative array of services.
   */
  public function getServices($ids_only = FALSE) {
    $container = \Drupal::getContainer();
    $services = $container->getServiceIds();
    $services = preg_grep("/asu_statistics\.datasource\./", $services);

    if ($ids_only) {
      $service_ids_to_return = [];
      foreach ($services as $service_id) {
        $service_id = preg_replace('/^.*datasource\./', '', $service_id);
        $service_ids_to_return[] = $service_id;
      }
      return $service_ids_to_return;
    }

    $options = [];
    foreach ($services as $service_id) {
      $service = \Drupal::service($service_id);
      $service_id = preg_replace('/^.*datasource\./', '', $service_id);
      $options[$service_id] = $service->getName();
    }
    return $options;
  }

  /**
   * Gets a form element's default value.
   *
   * For use in implementations of hook_form_alter() and in data source plugins.
   *
   * @param string $form_element
   *   The machine name of the form element.
   * @param string|array $default_value
   *   The default value of the element if one is not present in the
   *   user's tempstore.
   *
   * @return string|array
   *   The default value of the element.
   */
  public function getFormElementDefault($form_element, $default_value) {
    if ($tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics')) {
      if ($form_state = $tempstore->get('islandora_repository_reports_report_form_values')) {
        $default_value = $form_state->getValue($form_element);
      }
    }
    return $default_value;
  }

  /**
   * Determines if the tempstore has been populated within a specific time.
   *
   * @param string $key
   *   The name of the key in the user's tempstore to check.
   * @param int $bb
   *   The 'best before' time, in seconds, for the tempstore.
   *
   * @return bool
   *   TRUE if the tempstore has passed its best before, FALSE if not.
   */
  public function tempstoreIsStale($key = 'islandora_repository_reports_report_type', $bb = 60) {
    $tempstore_age = 0;
    if ($tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics')) {
      if (is_null($tempstore->getMetadata($key))) {
        return TRUE;
      }
      $tempstore_age = time() - $tempstore->getMetadata($key)->getUpdated();
      if ($tempstore_age > $bb) {
        return TRUE;
      }
    }
  }

  /**
   * Generate a set of random colors to use in the chart.
   *
   * @param int $length
   *   The length of the array to generate.
   *
   * @return array
   *   An array of RGB values in the format required by Chart.js, e.g.,
   *   array('rgba(255, 99, 132)', 'rgba(54, 162, 235)', 'rgba(255, 206, 86)').
   */
  public function getRandomChartColors($length) {
    $colors = [];
    for ($i = 1; $i <= $length; $i++) {
      $rgb_color = [];
      foreach (['r', 'g', 'b'] as $color) {
        $rgb_color[$color] = rand(0, 255);
      }
      $colors[] = 'rgba(' . implode(',', $rgb_color) . ')';
    }
    return $colors;
  }

  /**
   * Gets content types from the user's tempstore.
   *
   * @return array
   *   An array containing machine names of the content types for use in
   *   the content type form widget.
   */
  public function getSelectedContentTypes() {
    $content_types = ['asu_repository_item'];
//    if ($tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics')) {
//      if ($form_state = $tempstore->get('islandora_repaository_reports_report_form_values')) {
//        $content_types = $form_state->getValue('islandora_repository_reports_content_types');
//      }
//    }
    return (array) $content_types;
  }

  /**
   * Return a static set of colors to use in the chart.
   *
   * @param int $length
   *   The length of the array to generate.
   *
   * @return array
   *   An array of pregenerated RGB values in the format required by
   *   Chart.js, e.g., array('rgba(255, 99, 132)', 'rgba(54, 162, 235)',
   *   'rgba(255, 206, 86)').
   */
  public function getStaticChartColors($length) {
    $colors = [
      'rgba(78,132,54)',
      'rgba(7,125,245)',
      'rgba(199,234,163)',
      'rgba(255,63,165)',
      'rgba(91,94,118)',
      'rgba(221,162,171)',
      'rgba(113,70,43)',
      'rgba(187,235,216)',
      'rgba(32,103,188)',
      'rgba(253,124,215)',
      'rgba(83,209,86)',
      'rgba(99,191,203)',
      'rgba(249,89,197)',
      'rgba(130,172,250)',
      'rgba(225,147,36)',
      'rgba(42,104,179)',
      'rgba(65,103,130)',
      'rgba(186,135,228)',
      'rgba(72,181,202)',
      'rgba(139,230,241)',
      'rgba(227,191,220)',
      'rgba(117,119,167)',
      'rgba(109,170,99)',
      'rgba(247,48,160)',
      'rgba(236,10,60)',
      'rgba(214,226,29)',
      'rgba(2,16,149)',
      'rgba(252,242,112)',
      'rgba(104,222,174)',
      'rgba(42,225,126)',
      'rgba(178,192,47)',
      'rgba(175,95,138)',
      'rgba(208,241,78)',
      'rgba(55,201,174)',
      'rgba(28,230,159)',
      'rgba(49,202,3)',
      'rgba(236,31,236)',
      'rgba(228,208,127)',
      'rgba(7,120,116)',
      'rgba(124,110,35)',
      'rgba(207,218,76)',
      'rgba(168,72,167)',
      'rgba(215,66,164)',
      'rgba(80,27,221)',
      'rgba(176,240,51)',
      'rgba(68,149,156)',
      'rgba(5,222,21)',
      'rgba(53,233,67)',
      'rgba(231,199,179)',
      'rgba(206,250,129)',
      'rgba(96,1,182)',
      'rgba(90,251,206)',
      'rgba(243,140,168)',
      'rgba(227,60,64)',
      'rgba(1,19,233)',
      'rgba(118,184,165)',
      'rgba(184,148,221)',
      'rgba(79,20,156)',
      'rgba(217,49,202)',
      'rgba(66,63,75)',
      'rgba(12,147,221)',
      'rgba(236,37,141)',
      'rgba(224,48,195)',
      'rgba(128,31,220)',
      'rgba(222,14,23)',
      'rgba(55,7,234)',
      'rgba(5,6,222)',
      'rgba(101,173,80)',
      'rgba(31,84,52)',
      'rgba(108,76,217)',
      'rgba(200,88,90)',
      'rgba(191,150,135)',
      'rgba(182,49,122)',
      'rgba(246,35,58)',
      'rgba(188,245,50)',
      'rgba(23,55,11)',
      'rgba(154,123,238)',
      'rgba(52,116,6)',
      'rgba(59,34,60)',
      'rgba(97,100,25)',
      'rgba(0,143,240)',
      'rgba(218,122,177)',
      'rgba(7,131,174)',
      'rgba(37,247,111)',
      'rgba(21,209,235)',
      'rgba(214,108,230)',
      'rgba(104,181,13)',
      'rgba(119,220,149)',
      'rgba(58,78,139)',
      'rgba(114,142,145)',
      'rgba(160,176,99)',
      'rgba(117,227,130)',
      'rgba(251,238,102)',
      'rgba(231,110,7)',
      'rgba(174,125,227)',
      'rgba(47,42,173)',
      'rgba(47,84,210)',
      'rgba(73,216,1)',
      'rgba(129,169,203)',
      'rgba(18,168,204)',
      'rgba(222,92,116)',
      'rgba(80,48,248)',
      'rgba(56,202,76)',
      'rgba(186,198,83)',
      'rgba(54,228,4)',
      'rgba(193,167,224)',
      'rgba(61,212,206)',
      'rgba(185,155,156)',
      'rgba(191,252,11)',
      'rgba(115,155,63)',
      'rgba(30,226,32)',
      'rgba(87,202,240)',
      'rgba(246,246,164)',
      'rgba(56,48,35)',
      'rgba(228,141,87)',
      'rgba(179,72,103)',
      'rgba(28,38,64)',
      'rgba(206,218,143)',
      'rgba(48,43,119)',
      'rgba(149,14,156)',
      'rgba(248,241,63)',
      'rgba(103,71,4)',
      'rgba(230,230,99)',
      'rgba(59,113,56)',
      'rgba(204,211,29)',
      'rgba(69,205,43)',
      'rgba(245,49,37)',
      'rgba(221,89,184)',
      'rgba(43,218,2)',
      'rgba(59,184,204)',
      'rgba(149,26,5)',
      'rgba(142,71,245)',
      'rgba(114,175,101)',
      'rgba(164,214,138)',
      'rgba(203,138,192)',
      'rgba(187,84,61)',
      'rgba(231,9,117)',
      'rgba(0,0,70)',
      'rgba(15,182,103)',
      'rgba(62,240,123)',
      'rgba(175,36,91)',
      'rgba(81,18,71)',
      'rgba(60,150,180)',
      'rgba(155,130,68)',
      'rgba(181,64,38)',
      'rgba(103,0,196)',
      'rgba(70,219,198)',
      'rgba(218,220,56)',
      'rgba(255,143,208)',
      'rgba(14,101,70)',
      'rgba(59,29,76)',
      'rgba(43,247,178)',
      'rgba(155,171,220)',
      'rgba(204,84,125)',
      'rgba(121,67,243)',
      'rgba(189,134,209)',
      'rgba(61,77,114)',
      'rgba(112,46,80)',
      'rgba(155,31,12)',
      'rgba(97,237,60)',
      'rgba(78,132,228)',
      'rgba(155,194,24)',
      'rgba(93,158,251)',
      'rgba(214,151,74)',
      'rgba(100,112,248)',
      'rgba(249,37,216)',
      'rgba(130,152,112)',
      'rgba(234,184,201)',
      'rgba(239,77,35)',
      'rgba(107,18,187)',
      'rgba(164,244,112)',
      'rgba(107,238,219)',
      'rgba(128,61,69)',
      'rgba(195,138,85)',
      'rgba(92,195,228)',
      'rgba(163,10,9)',
      'rgba(141,140,207)',
      'rgba(215,60,48)',
      'rgba(37,221,29)',
      'rgba(63,137,164)',
      'rgba(194,4,245)',
      'rgba(103,43,34)',
      'rgba(119,97,192)',
      'rgba(177,18,178)',
      'rgba(196,151,79)',
      'rgba(31,191,228)',
      'rgba(162,135,108)',
      'rgba(15,48,97)',
      'rgba(97,97,139)',
      'rgba(67,151,59)',
      'rgba(49,25,176)',
      'rgba(77,241,60)',
      'rgba(18,52,224)',
      'rgba(38,213,26)',
      'rgba(222,226,39)',
      'rgba(109,182,42)',
      'rgba(47,139,117)',
      'rgba(22,72,191)',
      'rgba(162,73,202)',
      'rgba(1,253,63)',
      'rgba(79,134,1)',
      'rgba(226,32,32)',
      'rgba(213,137,146)',
      'rgba(233,128,179)',
      'rgba(134,4,141)',
      'rgba(195,110,15)',
      'rgba(102,141,105)',
      'rgba(253,96,249)',
      'rgba(197,193,134)',
      'rgba(41,48,157)',
      'rgba(59,45,190)',
      'rgba(7,60,252)',
      'rgba(45,204,109)',
      'rgba(242,176,221)',
      'rgba(110,202,65)',
      'rgba(96,127,34)',
      'rgba(234,165,238)',
      'rgba(229,180,113)',
      'rgba(242,90,33)',
      'rgba(144,238,108)',
      'rgba(101,102,173)',
      'rgba(136,68,194)',
      'rgba(92,4,39)',
      'rgba(207,254,175)',
      'rgba(35,134,199)',
      'rgba(84,2,132)',
      'rgba(154,223,145)',
      'rgba(52,183,158)',
      'rgba(182,143,224)',
      'rgba(133,90,178)',
      'rgba(149,106,69)',
      'rgba(176,179,205)',
      'rgba(22,9,241)',
      'rgba(152,35,197)',
      'rgba(162,224,106)',
      'rgba(156,34,243)',
      'rgba(32,142,62)',
      'rgba(239,31,88)',
      'rgba(191,238,60)',
      'rgba(122,222,34)',
      'rgba(140,15,51)',
      'rgba(85,137,134)',
      'rgba(96,101,145)',
      'rgba(177,124,39)',
      'rgba(188,27,77)',
      'rgba(37,200,180)',
      'rgba(253,157,175)',
      'rgba(48,19,216)',
      'rgba(182,116,26)',
      'rgba(48,31,138)',
      'rgba(157,163,135)',
      'rgba(241,183,40)',
      'rgba(103,101,62)',
      'rgba(66,201,75)',
      'rgba(168,95,69)',
    ];
    return array_slice($colors, 0, $length);
  }

  /**
   * Checks if a node has term http://purl.org/dc/dcmitype/Collection.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   TRUE if the node has tag with that URI, FALSE if not.
   */
  public function nodeIsCollection(NodeInterface $node) {
    $islandora_utils = \Drupal::service('islandora.utils');
    $collection_term = $islandora_utils->getTermForUri('http://purl.org/dc/dcmitype/Collection');
    // Check that the node has the 'islandora_model' field and
    // that the term ID is a value in that field.
    if ($node->hasField('field_model')) {
      $model_terms_ids = [];
      $model_terms = $node->get('field_model')->referencedEntities();
      foreach ($model_terms as $model_term) {
        $model_terms_ids[] = $model_term->id();
      }
      if (in_array($collection_term->id(), $model_terms_ids)) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }

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
    $utilities = \Drupal::service('asu_statistics.utilities');

    $config = \Drupal::config('islandora_repository_reports.settings');
    $cache_data = $config->get('islandora_repository_reports_cache_report_data');

    $data_element_counts = &drupal_static(__FUNCTION__);
    $cid = 'asu_statistics_data_' . $report_type;
    $data_source_service_id = 'asu_statistics.datasource.' . $report_type;
    $data_source = \Drupal::service($data_source_service_id);

    if ($cache_data && $cache = \Drupal::cache()->get($cid)) {
      $data_element_counts = $cache->data;
    }
    else {
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

      if ($config->get('islandora_repository_reports_randomize_pie_chart_colors')) {
        $dataset->backgroundColor = $utilities->getRandomChartColors($num_data_elements);
      }
      else {
        $dataset->backgroundColor = $utilities->getStaticChartColors($num_data_elements);
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
    $this->writeCsvFile($report_type, $data_source->csvData);

    return $chart_data;
  }

  /**
   * Writes the CSV file.
   *
   * @param string $report_type
   *   The report type.
   * @param string $csv_data
   *   An array of arrays corresponding to CSV records.
   */
  public function writeCsvFile($report_type, $csv_data) {
    if ($tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics')) {
      if ($tempstore->get('asu_statistics_generate_csv')) {
        $default_schema = \Drupal::config('system.file')->get('default_scheme');
        $files_path = \Drupal::service('file_system')->realpath($default_schema . "://");
        $filename = 'asu_statistics_' . $report_type . '.csv';
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

  /**
   * Converts dates like 2020-06 to timestamps.
   *
   * @param string $start
   *   The start date, in yyyy-mm format.
   * @param string $end
   *   The end date, in yyyy-mm format.
   *
   * @return array
   *   An array whose first member is a Unix timestamp of the 00-00-00
   *   minute/second of the start date, and whose second member is
   *   a Unix timestamp of the 23-59-59 minute/second of the end date.
   */
  public function monthsToTimestamps($start, $end) {
    $start = empty($start) ? '1900-01' : $start;
    $end = empty($end) ? '2050-12' : $end;
    $start_timestamp = strtotime($start);
    $end_date = date('Y-m-t', strtotime($end));
    $end_date .= ' 23:59:59';
    $end_timestamp = strtotime($end_date);
    return [$start_timestamp, $end_timestamp];
  }

}
