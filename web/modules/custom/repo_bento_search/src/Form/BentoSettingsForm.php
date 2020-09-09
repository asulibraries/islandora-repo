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
    $form['block_config'] = [
      '#type' => 'fieldset',
      '#title' => t('Search block settings')];
    $form['block_config']['num_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results to show per box'),
      '#description' => $this->t('The number of results to show per bento box. Default value is 10 results.'),
      '#default_value' => $config->get('num_results') ?: 10,
    ];
    $form['block_config']['this_site'] = [
      '#type' => 'textfield',
      '#title' => t('This site'),
      '#default_value' => $config->get('titles_this_site') ?: t('This site'),
    ];
    $form['block_config']['legacy_repo'] = [
      '#type' => 'textfield',
      '#title' => t('Legacy repository'),
      '#default_value' => $config->get('titles_legacy_repo') ?: t('Repository'),
    ];
    $form['block_config']['dataverse'] = [
      '#type' => 'textfield',
      '#title' => t('Dataverse'),
      '#default_value' => $config->get('titles_dataverse') ?: t('Dataverse'),
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
      ->set('titles_this_site', $form_state->getValue('this_site'))
      ->set('titles_legacy_repo', $form_state->getValue('legacy_repo'))
      ->set('titles_dataverse', $form_state->getValue('dataverse'))
      ->save();
  }
}
