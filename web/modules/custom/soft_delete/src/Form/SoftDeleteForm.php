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
    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    $allBundleInfo = $bundle_info_service->getAllBundleInfo();
    $node_types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    $form['fieldset_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => t('Select content types to be handled by "Soft Delete".'),
      '#description' => t('When users delete content, instead of truly ' .
        'deleting, select a workflow "moderation state" to set for objects of ' .
        'various types.')
    ];
    foreach ($node_types as $node_type => $entity_type) {
      // adjust the content type to the $form_id value for these within their "delete node" page.
      $form_id = 'node_' . $node_type . '_delete_form';
      $content_types[$node_type] = $node_types[$node_type]->label();
      $disabled[$node_type] = FALSE;
      $entity_workflow = array_key_exists('workflow', $allBundleInfo['node'][$node_type]) ?
        $allBundleInfo['node'][$node_type]['workflow'] : FALSE;
      if ($entity_workflow) {
        $content_type_publication_statuses[$node_type] = ['' => t('Select workflow state')];
        // Add the workflow to each listing for reference:
        $content_types[$node_type] .= ' ("' . $entity_workflow . '" workflow)';
        // load the workflow that applies to this node content_type - and list
        // the workflow states that are related to it.
        $states = $workflow_configs['workflows.workflow.' . $entity_workflow]['states'];
        // Soft Deleting an item should never allow for setting it as Published.
        unset($states['published']);
        $set_disabled = TRUE;
        foreach ($states as $state_key => $state) {
          if ($state['default_revision']) {
            $states[$state_key] = $state['label'];
            $content_type_publication_statuses[$node_type][$state_key] = $state['label'];
            $set_disabled = FALSE;
          }
        }
        $disabled[$node_type] = $set_disabled;
      }
      else {
        $disabled[$node_type] = TRUE;
      }
    }

    foreach ($disabled as $node_type => $disable) {
      if ($disable === TRUE) {
        $form['fieldset_wrapper'][$node_type] = [
            '#disabled' => TRUE];
        $form['fieldset_wrapper'][$node_type . '_deleteto_workflow_state'] = [
          '#type' => 'item',
          '#description' => t('No workflow states can be used as a default revision ' .
            'for "@node_type".', array(
              '@node_type' => $node_types[$node_type]->label(),
            ))
        ];
      }
    }
    foreach ($content_type_publication_statuses as $node_type => $bundle_state_labels) {
      if (count($bundle_state_labels) > 0) {
        $form['fieldset_wrapper'][$node_type][$node_type . '_deleteto_workflow_state'] = [
          '#type' => 'select',
          '#title' => $this->t('"@node_type" workflow state', array(
            '@node_type' => $node_types[$node_type]->label(),
          )),
          '#options' => $bundle_state_labels,
          '#default_value' => $config->get($node_type . '_deleteto_workflow_state'),
        ];
      }
      else {
        $form['fieldset_wrapper'][$node_type . '_deleteto_workflow_state'] = [
          '#type' => 'item',
          '#title' => t('No workflow states can be used as a default revision ' .
            'for "@node_type".', array(
              '@node_type' => $node_types[$node_type]->label(),
            ))
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
      ->set('override_delete_default', $form_state->getValue('override_delete_default'))
      ->save();
    $form_state_values = $form_state->getValues();
    foreach ($form_state_values as $form_state_value_key => $form_value) {
      if (substr( $form_state_value_key, -24 ) === '_deleteto_workflow_state') {
        $this->config('soft_delete.adminsettings')
          ->set($form_state_value_key, $form_value)
          ->save();
      }
    }
  }
}