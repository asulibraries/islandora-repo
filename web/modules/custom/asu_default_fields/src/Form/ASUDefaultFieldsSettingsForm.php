<?php

namespace Drupal\asu_default_fields\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ASUDefaultFieldsSettingsForm.
 */
class ASUDefaultFieldsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'asu_default_fields.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'asu_default_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('asu_default_fields.settings');
    $form['disable_handle_generation'] = [
      '#type' => 'checkbox',
      '#title' => 'Disable all Handles generation on the site.',
      '#default_value' => $config->get('disable_handle_generation'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('asu_default_fields.settings')
      ->set('disable_handle_generation', $form_state->getValue('disable_handle_generation'))
      ->save();
  }
}
