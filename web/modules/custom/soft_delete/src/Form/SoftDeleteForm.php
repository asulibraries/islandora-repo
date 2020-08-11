<?php

/**
 * @file
 * Contains Drupal\soft_delete\Form\SoftDeleteForm
 */
namespace Drupal\soft_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\content_moderation\ModerationInformationInterface;

class SoftDeleteForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'soft_delete.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'soft_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('soft_delete.adminsettings');
    $configured_types = $config->get('soft_delete_content_types');
    $moderation_info = \Drupal::service('content_moderation.moderation_information');

    $node_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    $content_types = $content_type_publication_statuses = $selected_bundles = [];

    $disabled = array();
    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    $allBundleInfo = $bundle_info_service->getAllBundleInfo();
    foreach ($node_types as $key => $entity_type) {
      // adjust the content type to the $form_id value for these within their "delete node" page.
      $form_id = 'node_' . str_replace(
        array("-"),
        array("_"),
        strtolower($key)) . '_delete_form';
      $content_types[$form_id] = $node_types[$key]->label();
      $disabled[$form_id] = FALSE;
      $entity_workflow = array_key_exists('workflow', $allBundleInfo['node'][$key]) ? 
        $allBundleInfo['node'][$key]['workflow'] : FALSE;
      if ($entity_workflow) {
        // Add the workflow to each listing for reference:
        $content_types[$form_id] .= ' ("' . $entity_workflow . '" workflow)';
        // load the workflow that applies to this node content_type - and list
        // the workflow states that are related to it.
        $content_type_publication_statuses[$key][$bundle_id] = array(
          'draft' => 'Draft',
          'archived' => 'Archived',
          'published' => 'Published',
        );
      }
      else {
        $disabled[$form_id] = TRUE;
      }
    }

    $form['fieldset_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => t('Soft Delete Options'),
    ];
    $form['fieldset_wrapper']['soft_delete_content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select which content types will be handled by "Soft Delete".'),
      '#options' => $content_types,
      '#default_value' => $config->get('soft_delete_content_types'),
    ];
    foreach ($disabled as $form_id => $disable) {
      if ($disable === TRUE) {
        $form['fieldset_wrapper']['soft_delete_content_types'][$form_id] = [
            '#disabled' => TRUE];
      }
    }
    foreach ($content_type_publication_statuses as $key => $bundle_state_labels) {
      if (count($bundle_state_labels) > 0) {
        $form['soft_delete_' . $key . '_deleteto_workflow_state'] = [
          '#type' => 'select',
          '#title' => $this->t('"' . $key . '" soft deletion in workflow state'),
          '#options' => $bundle_state_labels,
          '#default_value' => [],
        ];
      }
    }

    $default_value = (null !== ($config->get('soft_delete_override_delete_default'))) ?
      $config->get('soft_delete_override_delete_default') : [0 => "FALSE"];
    $form['soft_delete_override_delete_default'] = [
      '#type' => 'select',
      '#title' => $this->t('Default setting for "Hard delete [content]?" prompt.'),
      '#options' => array(
          0 => "FALSE",
          1 => "TRUE"),
      '#default_value' => $default_value,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('soft_delete.adminsettings')
      ->set('soft_delete_content_types', $form_state->getValue('soft_delete_content_types'))
      ->save();
    $this->config('soft_delete.adminsettings')
      ->set('soft_delete_override_delete_default', $form_state->getValue('soft_delete_override_delete_default'))
      ->save();
  }
}