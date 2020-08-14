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
use Drupal\content_moderation\ContentModerationState;

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
    $content_types = $content_type_publication_statuses = $workflow_configs = $disabled = [];
    $workflow_keys = \Drupal::service('config.factory')
      ->listAll("workflows.workflow");
    foreach ($workflow_keys as $key) {
      $config = \Drupal::config($key);
      $workflow_configs[$key] = $config->get('type_settings');
    }
    $config = $this->config('soft_delete.adminsettings');
    $configured_types = $config->get('content_types');
    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    $allBundleInfo = $bundle_info_service->getAllBundleInfo();
    $node_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    $form['fieldset_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => t('Soft Delete Options'),
    ];
    foreach ($node_types as $node_type => $entity_type) {
      // adjust the content type to the $form_id value for these within their "delete node" page.
      $form_id = 'node_' . $node_type . '_delete_form';
      $content_types[$node_type] = $node_types[$node_type]->label();
      $disabled[$node_type] = FALSE;
      $entity_workflow = array_key_exists('workflow', $allBundleInfo['node'][$node_type]) ?
        $allBundleInfo['node'][$node_type]['workflow'] : FALSE;
      if ($entity_workflow) {
        // Add the workflow to each listing for reference:
        $content_types[$node_type] .= ' ("' . $entity_workflow . '" workflow)';
        // load the workflow that applies to this node content_type - and list
        // the workflow states that are related to it.
        $states = $workflow_configs['workflows.workflow.' . $entity_workflow]['states'];
        // Soft Deleting an item should never allow for setting it as Published.
        unset($states['published']);
        foreach ($states as $state_key => $state) {
          $states[$state_key] = $state['label'];
        }
        $content_type_publication_statuses[$node_type] = $states;
      }
      else {
        $disabled[$node_type] = TRUE;
      }
    }


    $form['fieldset_wrapper']['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select which content types will be handled by "Soft Delete".'),
      '#options' => $content_types,
      '#default_value' => $configured_types,
    ];
    foreach ($disabled as $node_type => $disable) {
      if ($disable === TRUE) {
        $form['fieldset_wrapper']['content_types'][$node_type] = [
            '#disabled' => TRUE];
      }
    }
    foreach ($content_type_publication_statuses as $node_type => $bundle_state_labels) {
      if (count($bundle_state_labels) > 0) {
        $form[$node_type . '_deleteto_workflow_state'] = [
          '#type' => 'select',
          '#title' => $this->t('"' . $node_type . '" soft deletion in workflow state'),
          '#options' => $bundle_state_labels,
          '#default_value' => $config->get($node_type . '_deleteto_workflow_state'),
        ];
      }
    }

    $default_value = (null !== ($config->get('override_delete_default'))) ?
      $config->get('override_delete_default') : [0 => "FALSE"];
    $form['override_delete_default'] = [
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
      ->set('content_types', $form_state->getValue('content_types'))
      ->save();
    $this->config('soft_delete.adminsettings')
      ->set('override_delete_default', $form_state->getValue('override_delete_default'))
      ->save();
    // loop through the content types to get the states for the enabled items.
    foreach ($form_state->getValue('content_types') as $node_type => $val) {
      if ($val) {
        $deleteto_form_id = $node_type . '_deleteto_workflow_state';
        $this->config('soft_delete.adminsettings')
          ->set($deleteto_form_id, $form_state->getValue($deleteto_form_id))
          ->save();
      }
    }
  }
}