<?php

/**
 * @file
 * Contains Drupal\asu_search\Form\ASUSearchConfigForm
 */
namespace Drupal\asu_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ASUSearchConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'asu_search.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'asu_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('asu_search.adminsettings');

    $form['fieldset_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => t('ASU Search Features'),
    ];
    $form['fieldset_wrapper']['description_item'] = [
      '#type' => 'item',
      '#description' => t('Select which facet fields will be rendered as search filters'),
    ];
    $form['fieldset_wrapper']['asu_search_filters'] = [
      '#type' => 'select',
      '#multiple' => true,
      '#size' => 8,
      '#title' => $this->t('Filters'),
      '#description' => $this->t('Select facets'),
      '#options' => $this-> get_facets(),
      '#default_value' => $config->get('content_change_digest_facets'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('asu_search.adminsettings')
      ->set('asu_search_filters', $form_state->getValue('asu_search_filters'))
      ->save();
  }

  /**
   *
   */
  function get_facets() {
    // run a query against in solr for all of the possible facets.
    $facets = array();
    return $facets;
  }
}