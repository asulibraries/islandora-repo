<?php

namespace Drupal\asu_statistics\Plugin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings form.
 */
class ASUStatisticsReportsReportSelectorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'asu_statistics_report_selector';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $utilities = \Drupal::service('asu_statistics.utilities');
    $report_type = $utilities->getFormElementDefault('asu_statistics_report_type', 'mimetype');
    $services = $utilities->getServices();
    natsort($services);

    $form['asu_statistics_report_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Report type'),
      '#default_value' => $report_type,
      '#options' => $services,
      '#attributes' => [
        'name' => 'asu_statistics_report_type',
        'id' => 'asu_statistics_report_type',
      ],
    ];
    $form['asu_statistics_generate_csv'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate a CSV file of this data'),
      '#attributes' => [
        'name' => 'asu_statistics_generate_csv',
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Go'),
      '#suffix' => '<span id="islandora-repository-reports-is-loading-message">' . $this->t('Please stand by while your report is being prepared...') . '</span>',
      '#attributes' => [
        'id' => 'asu_statistics_go_button',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('asu_statistics');
    $tempstore->set('asu_statistics_report_type', $form_state->getValue('asu_statistics_report_type'));
    $tempstore->set('asu_statistics_generate_csv', $form_state->getValue('asu_statistics_generate_csv'));
    // Pass the entire form state in so third-party modules that alter the
    // form can retrieve their custom form values.
    $tempstore->set('asu_statistics_report_form_values', $form_state);
  }

}
