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
    $form['block_legacy'] = [
      '#type' => 'fieldset',
      '#title' => t('Legacy Repository settings'),
    ];
    $form['block_legacy']['legacy_repository_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Legacy Repository API URL'),
      '#description' => $this->t('The URL to the legacy repository API search endpoint, like https://repo-dev.lib.asu.edu/api/items/search'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('legacy_repository_api_url'),
    ];
    $form['block_legacy']['legacy_repository_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Legacy Repository API Key'),
      '#description' => $this->t('API Key for the legacy repository search API'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('legacy_repository_api_key'),
    ];
    $form['block_legacy']['legacy_repo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block title for Legacy repository'),
      '#default_value' => $config->get('titles_legacy_repo') ?: $this->t('Repository'),
    ];
    $form['block_legacy']['legacy_repo_tooltip'] = [
      "#type" => 'textarea',
      '#title' => $this->t('Tooltip text'),
      '#description' => $this->t('The text to display in the info icon tooltip describing this system'),
      '#default_value' => $config->get('legacy_repo_tooltip') ?: '',
    ];
    $form['block_dataverse'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Dataverse settings'),
    ];
    $form['block_dataverse']['dataverse_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dataverse API URL'),
      '#description' => $this->t('The URL to the dataverse API search endpoint, like https://dataverse-test.lib.asu.edu/api/search'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('dataverse_api_url'),
    ];
    $form['block_dataverse']['dataverse'] = [
      '#type' => 'textfield',
      '#title' => t('Block title for Dataverse'),
      '#default_value' => $config->get('titles_dataverse') ?: t('Dataverse'),
    ];
    $form['block_dataverse']['dataverse_tooltip'] = [
      "#type" => 'textarea',
      '#title' => $this->t('Tooltip text'),
      '#description' => $this->t('The text to display in the info icon tooltip describing this system'),
      '#default_value' => $config->get('dataverse_tooltip') ?: '',
    ];
    $form['block_this_i8'] = [
      '#type' => 'fieldset',
      '#title' => t('This Islandora 8 settings'),
    ];
    $form['block_this_i8']['this_i8_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Islandora 8 site API URL'),
      '#description' => $this->t('The URL to this Islandora 8 search endpoint, like https://repo-dev.aws.lib.asu.edu/api/search'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('this_i8_api_url'),
    ];
    $form['block_this_i8']['titles_this_i8'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block title for this Islandora 8 site'),
      '#default_value' => $config->get('titles_this_i8') ?: t('KEEP'),
    ];
    $form['block_this_i8']['this_i8_tooltip'] = [
      "#type" => 'textarea',
      '#title' => $this->t('Tooltip text'),
      '#description' => $this->t('The text to display in the info icon tooltip describing this system'),
      '#default_value' => $config->get('this_i8_tooltip') ?: '',
    ];
    $form['block_second_i8'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Second Islandora 8 settings'),
    ];
    $form['block_second_i8']['second_i8_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Islandora 8 site API URL'),
      '#description' => $this->t('The URL to the second Islandora 8 search endpoint, like https://repo-dev.aws.lib.asu.edu/api/search'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('second_i8_api_url'),
    ];
    $form['block_second_i8']['titles_second_i8'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block title for second Islandora 8 site'),
      '#default_value' => $config->get('titles_second_i8') ?: $this->t('PRISM'),
    ];
    $form['block_second_i8']['second_i8_tooltip'] = [
      "#type" => 'textarea',
      '#title' => $this->t('Tooltip text'),
      '#description' => $this->t('The text to display in the info icon tooltip describing this system'),
      '#default_value' => $config->get('second_i8_tooltip') ?: '',
    ];
    $form['num_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results to show per box'),
      '#description' => $this->t('The number of results to show per bento box. Default value is 10 results.'),
      '#default_value' => $config->get('num_results') ?: 10,
    ];
    $form['recent_items_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recent Items Api URL'),
      '#description' => $this->t('The URL for the API endpoint to use for recent items on the landing site homepage'),
      '#default_value' => $config->get('recent_items_api'),
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
      ->set('this_i8_api_url', $form_state->getValue('this_i8_api_url'))
      ->set('second_i8_api_url', $form_state->getValue('second_i8_api_url'))
      ->set('legacy_repository_api_key', $form_state->getValue('legacy_repository_api_key'))
      ->set('num_results', $form_state->getValue('num_results'))
      ->set('titles_this_i8', $form_state->getValue('titles_this_i8'))
      ->set('titles_second_i8', $form_state->getValue('titles_second_i8'))
      ->set('titles_legacy_repo', $form_state->getValue('legacy_repo'))
      ->set('titles_dataverse', $form_state->getValue('dataverse'))
      ->set('recent_items_api', $form_state->getValue('recent_items_api'))
      ->set('dataverse_tooltip', $form_state->getValue('dataverse_tooltip'))
      ->set('legacy_repo_tooltip', $form_state->getValue('legacy_repo_tooltip'))
      ->set('this_i8_tooltip', $form_state->getValue('this_i8_tooltip'))
      ->set('second_i8_tooltip', $form_state->getValue('second_i8_tooltip'))
      ->save();
  }

}
