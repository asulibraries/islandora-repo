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
    return asu_statistics_get_stats($collection_node_id);
  }

}
