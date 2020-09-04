<?php

namespace Drupal\repo_bento_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BentoSettingsForm.
 */
class BentoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'repo_bento_search.bentosettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bento_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('repo_bento_search.bentosettings');
    $form['legacy_repository_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Legacy Repository API URL'),
      '#description' => $this->t('The URL to the legacy repository API search endpoint, like https://repo-dev.lib.asu.edu/api/items/search'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('legacy_repository_api_url'),
    ];
    $form['dataverse_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dataverse API URL'),
      '#description' => $this->t('The URL to the dataverse API search endpoint, like https://dataverse-test.lib.asu.edu/api/search'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('dataverse_api_url'),
    ];
    $form['legacy_repository_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Legacy Repository API Key'),
      '#description' => $this->t('API Key for the legacy repository search API'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('legacy_repository_api_key'),
    ];
    $form['num_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results to show per box'),
      '#description' => $this->t('The number of results to show per bento box'),
      '#default_value' => $config->get('num_results'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('repo_bento_search.bentosettings')
      ->set('legacy_repository_api_url', $form_state->getValue('legacy_repository_api_url'))
      ->set('dataverse_api_url', $form_state->getValue('dataverse_api_url'))
      ->set('legacy_repository_api_key', $form_state->getValue('legacy_repository_api_key'))
      ->set('num_results', $form_state->getValue('num_results'))
      ->save();
  }

}
