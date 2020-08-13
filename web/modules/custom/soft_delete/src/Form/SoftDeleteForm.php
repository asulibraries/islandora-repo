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
public function ag($in) {
  //
}
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $content_types = $content_type_publication_statuses = $workflow_configs = $disabled = [];
    $database = \Drupal::database();
    $workflow_config_results = $database->query("SELECT name FROM config WHERE name like 'workflows.workflow.%'");
    if ($workflow_config_results) {
      while ($row = $workflow_config_results->fetchAssoc()) {
        $config = \Drupal::config($row['name']);
        $workflow_key = str_replace('workflows.workflow.', '', $row['name']);
        $workflow_configs[$workflow_key] = $config->get('type_settings');
      }
    }
    $this->ag($workflow_configs);
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
    foreach ($node_types as $key => $entity_type) {
      // adjust the content type to the $form_id value for these within their "delete node" page.
      $form_id = 'node_' . str_replace(
        array("-"),
        array("_"),
        strtolower($key)) . '_delete_form';
      $content_types[$key] = $node_types[$key]->label();
      $disabled[$key] = FALSE;
      $entity_workflow = array_key_exists('workflow', $allBundleInfo['node'][$key]) ?
        $allBundleInfo['node'][$key]['workflow'] : FALSE;
      if ($entity_workflow) {
        // Add the workflow to each listing for reference:
        $content_types[$key] .= ' ("' . $entity_workflow . '" workflow)';
        // load the workflow that applies to this node content_type - and list
        // the workflow states that are related to it.
//        $workflow_configs[$entity_workflow]
        $states = $workflow_configs[$entity_workflow]['states'];
        // Soft Deleting an item should never allow for setting it as Published.
        unset($states['published']);
        foreach ($states as $state_key => $state) {
          $states[$state_key] = $state['label'];
        }
        $content_type_publication_statuses[$key] = $states;
      }
      else {
        $disabled[$key] = TRUE;
      }
    }


    $form['fieldset_wrapper']['content_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select which content types will be handled by "Soft Delete".'),
      '#options' => $content_types,
      '#default_value' => $configured_types,
    ];
    foreach ($disabled as $key => $disable) {
      if ($disable === TRUE) {
        $form['fieldset_wrapper']['content_types'][$key] = [
            '#disabled' => TRUE];
      }
    }
    foreach ($content_type_publication_statuses as $key => $bundle_state_labels) {
      if (count($bundle_state_labels) > 0) {
        $form[$key . '_deleteto_workflow_state'] = [
          '#type' => 'select',
          '#title' => $this->t('"' . $key . '" soft deletion in workflow state'),
          '#options' => $bundle_state_labels,
          '#default_value' => $config->get($key . '_deleteto_workflow_state'),
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
    foreach ($form_state->getValue('content_types') as $key => $val) {
      if ($val) {
        $deleteto_form_id = $key . '_deleteto_workflow_state';
        $this->config('soft_delete.adminsettings')
          ->set($deleteto_form_id, $form_state->getValue($deleteto_form_id))
          ->save();
      }
    }
  }
}