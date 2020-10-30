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
   *   Markup used by the chart.
   */
  public function main() {
    if ($tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics')) {
      $show_csv_link = $tempstore->get('asu_statistics_generate_csv');
    }
    $form = \Drupal::formBuilder()->getForm('Drupal\asu_statistics\Plugin\Form\ASUStatisticsReportsReportSelectorForm');
    return [
      '#form' => $form,
      '#show_csv_link' => $show_csv_link,
      '#theme' => 'asu_statistics_chart',
    ];
  }

}
