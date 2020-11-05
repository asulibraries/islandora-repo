<?php

namespace Drupal\asu_statistics\Controller;

use Drupal\Core\Controller\ControllerBase;

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
    $show_csv_link = ($tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics')) ?
      $tempstore->get('asu_statistics_generate_csv') : FALSE;
    $form = \Drupal::formBuilder()->getForm('Drupal\asu_statistics\Plugin\Form\ASUStatisticsReportsReportSelectorForm');
    $node = \Drupal::routeMatch()->getParameter('node');
    $collection_node_id = ($node) ? $node->id(): 0;
    $collection_stats = $this->get_stats($collection_node_id);
    return [
      '#form' => $form,
      '#show_csv_link' => $show_csv_link,
      '#theme' => 'asu_statistics_chart',
      '#stats' => print_r($collection_stats, true),
    ];
  }

  public function get_stats($collection_node_id = NULL) {
    $query = \Drupal::database()->select('node_field_data', 'node_field_data');
    $query->addExpression('COUNT(node_field_data.nid)', 'items');
    $query->addExpression('YEAR(FROM_UNIXTIME(node_field_data.created))', 'item_year');
    $query->addExpression('MONTH(FROM_UNIXTIME(node_field_data.created))', 'item_month');
      if ($collection_node_id) {
      $query->join('node__field_member_of', 'node__field_member_of',
          'node__field_member_of.entity_id = node_field_data.nid');
      $query->condition('node__field_member_of.field_member_of_target_id', $collection_node_id);
    }
    // Do not limit the results by published nodes only -- the make_table_rows_from_result
    // will need to inspect the item's moderation state by using
    //     $asu_utils = \Drupal::service('asu_utils');
    //     $node = \Drupal::entityTypeManager()->getStorage('node')->load($node);
    //     $node_is_published = $asu_utils->isNodePublished($node);
    
    // $query->condition('node_field_data.status', 1);
    $query->groupBy('YEAR(FROM_UNIXTIME(node_field_data.created)), MONTH(FROM_UNIXTIME(node_field_data.created))');
    $result = $query->execute()->fetchAll();
    return $this->make_table_rows_from_result($result);
  }

  function make_table_rows_from_result($result) {
    $build_output = $rows = [];
    // get all of the rows into an array where each row contains the results for
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
      $record_arr = (array)$child_obj;
      $record_arr['item_month_name'] =  date("F", mktime(0, 0, 0, $child_obj->item_month, 10));
      $rows[$child_obj->item_year . "-" . $child_obj->item_month] = $record_arr;
    }
    for ($year = $earliest_year; $year <= $latest_year; $year++) {
      for ($month = 1; $month < 13; $month++) {
        $pad0_month = (($month < 10) ? "0" : "") . $month;
        $year_month_key = $year . "-" . $pad0_month;
        if (!array_key_exists($year_month_key, $rows)) {
          $build_output[$year_month_key] = [
            'item_year' => $year,
            'item_month' => $month,
            'item_month_name' => date("F", mktime(0, 0, 0, $month, 10)),
            'items' => 0];
        } else {
          $build_output[$year_month_key] = $rows[$year_month_key];
        }
      }
    }
    return $build_output;
  }
}
