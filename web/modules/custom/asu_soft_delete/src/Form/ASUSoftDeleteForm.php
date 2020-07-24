<?php

/**
 * @file
 * Contains Drupal\asu_soft_delete\Form\ASUSoftDeleteForm
 */
namespace Drupal\asu_soft_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ASUSoftDeleteForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'asu_soft_delete.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'asu_soft_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('asu_soft_delete.adminsettings');
    $configured_types = $config->get('asu_soft_delete_content_types');

    $node_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    foreach (array_keys($node_types) as $content_type) {
      // adjust the content type to the $form_id value for these within their "delete node" page.
      $form_id = 'node_' . str_replace(
        array("-"),
        array("_"),
        strtolower($content_type)) . '_delete_form';
      $content_types[$form_id] = $node_types[$content_type]->label();
    }

    $form['fieldset_wrapper']['asu_soft_delete_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select which content types will be handled by "Soft Delete".'),
      '#options' => $content_types,
      '#default_value' => $config->get('asu_soft_delete_content_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('asu_soft_delete.adminsettings')
      ->set('asu_soft_delete_content_types', $form_state->getValue('asu_soft_delete_content_types'))
      ->save();
  }
}