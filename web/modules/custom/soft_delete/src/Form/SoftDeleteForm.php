<?php

/**
 * @file
 * Contains Drupal\soft_delete\Form\SoftDeleteForm
 */
namespace Drupal\soft_delete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
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
    /*
     * taken from the buildForm() of EntityModerationForm.php
     *
     * @var \Drupal\workflows\Transition[] $transitions
     * $transitions = $this->validation->getValidTransitions($entity, $this->currentUser());

     * // Exclude self-transitions.
     * $transitions = array_filter($transitions, function (Transition $transition) use ($current_state) {
     *   return $transition->to()->id() != $current_state;
     * });
     * ..... or ....
     * $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
     */
    // @todo - disable any of the content types that are not assigned to a workflow
    // because they cannot be set into another state when the delete is done. In
    // those cases, these should just be deleted when the delete button is clicked.
    $disabled = array();
    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    foreach ($node_types as $key => $entity_type) {
      // adjust the content type to the $form_id value for these within their "delete node" page.
      $form_id = 'node_' . str_replace(
        array("-"),
        array("_"),
        strtolower($key)) . '_delete_form';
      $content_types[$form_id] = $node_types[$key]->label();
      $disabled[$form_id] = (rand(0,9) >= 5) ? TRUE : FALSE;
// TODO - this does not do what it seems it should do...

      /// crap -- this may need to loop through all possible workflows and check to find the
      /// workflow that has a workflow state that applies to the current content type.
      // do this by a (Workflow::loadMultipleByType('content_moderation') as $workflow) loop
      foreach ($bundle_info_service->getBundleInfo('node_type') as $bundle_id => $bundle) {
    ///    if ($this->workflowType->appliesToEntityTypeAndBundle($entity_type->id(), $bundle_id)) {
          $content_type_publication_statuses[$key][$bundle_id] = $bundle['label'];
    ///    }
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
//      $workflow = $moderation_info->getWorkflowForEntity($node_types[$key]);
//dpm($workflow);
      $form['soft_delete_' . $key . '_deleteto_workflow_state'] = [
        '#type' => 'select',
        '#title' => $this->t('"' . $key . '" soft deletion in workflow state'),
        '#options' => $bundle_state_labels,
        '#default_value' => [],
      ];
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