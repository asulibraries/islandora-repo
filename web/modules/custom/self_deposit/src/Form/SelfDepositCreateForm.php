<?php

namespace Drupal\self_deposit\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformAjaxElementTrait;
use Drupal\webform\Plugin\WebformHandlerMessageInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Defines a webform that resends webform submission.
 */
class SelfDepositCreateForm extends FormBase {

  use WebformAjaxElementTrait;

  /**
   * A webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\webform\WebformSubmissionConditionsValidatorInterface
   */
  protected $conditionsValidator;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\asu_deposit_methods\DepositUtils
   */
  protected $depositUtils;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'self_deposit_create';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->configFactory = $container->get('config.factory');
    $instance->conditionsValidator = $container->get('webform_submission.conditions_validator');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->depositUtils = $container->get('asu_deposit_methods.deposit_utils');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission = NULL) {
    $this->webformSubmission = $webform_submission;
    // Apply variants to the webform.
    $webform = $webform_submission->getWebform();
    $webform->applyVariants($webform_submission);

    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'Create Repository Item',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $this->createItem($this->webformSubmission);

    $t_args = [
      ':url' => $node->toUrl()->toString(),
    ];
    $this->messenger()->addStatus($this->t('Successfuly created repository item. <a href=":url">View item</a>', $t_args));
  }

  /* ************************************************************************ */
  // Helper methods.
  /* ************************************************************************ */

  /**
   * Helper for createItem() method.
   */
  private function createNode($webform_submission, $values, $title, $model, $copyright_term, $perm_term, $member_of) {
    $paragraph = Paragraph::create(
      ['type' => 'complex_title', 'field_main_title' => $title]
    );

    $paragraph->save();

    $keywords = [];
    foreach ($values['keywords'] as $key) {
      $kterm = $this->depositUtils->getOrCreateTerm($key, 'subject');
      array_push($keywords, $kterm);
    }

    $node_args = [
      'type' => 'asu_repository_item',
      'langcode' => 'en',
      'created' => time(),
      'changed' => time(),
      'uid' => \Drupal::currentUser()->id(),
      'moderation_state' => 'draft',
      'field_title' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
      'field_rich_description' => [
        'value' => $values['item_description'],
        'format' => 'description_restricted_items',
      ],
      'field_reuse_permissions' => [
        ['target_id' => $values['reuse_permissions']],
      ],
      'field_subjects' => $keywords,
      'field_copyright_statement' => [
        ['target_id' => $copyright_term->id()],
      ],
      'field_default_derivative_file_pe' => [
        ['target_id' => $perm_term->id()],
      ],
      'field_default_original_file_perm' => [
        ['target_id' => $perm_term->id()],
      ],
      'field_embargo_release_date' => [
        $values['embargo_release_date'] . "T23:59:59",
      ],
      'field_model' => [
        ['target_id' => $model->id()],
      ],
      'field_member_of' => [
        ['target_id' => $member_of],
      ],
    ];

    $node = Node::create($node_args);
    $node->save();

    return $node;
  }

  /**
   * Creates a repository item from a given webform submission
   */
  protected function createItem(WebformSubmissionInterface $webform_submission) {
    // Get an array of the values from the submission.
    $values = $webform_submission->getData();
    $files = $values['file'];
    $file_repository = \Drupal::service('file.repository');
    $new_dest = "fedora://c160/";

    if (count($files) > 1) {
      $model = 'Complex Object';
      $child_files = [];
      foreach ($files as $file_id) {
        $file = $this->entityTypeManager->getStorage('file')->load(intval($file_id));
        $mime = $file->getMimeType();
        $filename = $file->getFilename();
        $file = $file_repository->copy($file, $new_dest . $filename);
        $file_id = $file->id();
        list($fmodel, $fmedia_type, $ffield_name) = $this->depositUtils->getModel($mime, $filename);
        $child_files[$file_id] = [
          'model' => $fmodel,
          'media_type' => $fmedia_type,
          'field_name' => $ffield_name,
          'file_name' => $filename,
        ];
      }
    }
    else {
      $file = $this->entityTypeManager->getStorage('file')->load(intval($files[0]));
      $mime = $file->getMimeType();
      $filename = $file->getFilename();
      $files[0] = $file_repository->copy($file, $new_dest . $filename)->id();
      list($model, $media_type, $field_name) = $this->depositUtils->getModel($mime, $filename);
    }

    $term = $model;
    $taxo_manager = $this->entityTypeManager->getStorage('taxonomy_term');
    $taxo_terms = $taxo_manager->loadByProperties(['name' => $term]);
    $taxo_term = reset($taxo_terms);

    $copyright_term_arr = $taxo_manager->loadByProperties(['name' => 'In Copyright']);
    $copyright_term = reset($copyright_term_arr);

    $perm_term_arr =
    $taxo_manager->loadByProperties(['name' => $values['file_permissions_select']]);
    $perm_term = reset($perm_term_arr);

    $config = $this->configFactory->get('self_deposit.selfdepositsettings');
    if ($config->get('collection_for_deposits')) {
      $member_of = $config->get('collection_for_deposits');
    }

    if ($model == 'Complex Object') {
      $node = $this->createNode($webform_submission, $values, $values['item_title'], $taxo_term, $copyright_term, $perm_term, $member_of);
      foreach ($child_files as $cfkey => $cfvalues) {
        $fmember_of = $node->id();
        $ftaxo_terms = $taxo_manager->loadByProperties(['name' => $cfvalues['model']]);
        $ftaxo_term = reset($ftaxo_terms);
        $child_node = $this->createNode($webform_submission, $values, $cfvalues['file_name'], $ftaxo_term, $copyright_term, $perm_term, $fmember_of);
        $this->depositUtils->createMedia($cfvalues['media_type'], $cfvalues['field_name'], $cfkey, $child_node->id());
      }
    }
    else {
      $node = $this->createNode($webform_submission, $values, $values['item_title'], $taxo_term, $copyright_term, $perm_term, $member_of);
      $this->depositUtils->createMedia($media_type, $field_name, $files[0], $node->id());
    }

    $webform_submission->setElementData('item_node', $node->id());
    return $node;
  }

}