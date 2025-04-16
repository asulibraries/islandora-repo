<?php

namespace Drupal\self_deposit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Element\WebformAjaxElementTrait;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * For getting self-deposit settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * 🤷.
   *
   * @var \Drupal\webform\WebformSubmissionConditionsValidatorInterface
   */
  protected $conditionsValidator;

  /**
   * For Taxonomy term managment.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Used for looking up or creating terms.
   *
   * @var \Drupal\asu_deposit_methods\DepositUtils
   */
  protected $depositUtils;

  /**
   * File movement.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepo;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
    $instance->fileRepo = $container->get('file.repository');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?WebformSubmissionInterface $webform_submission = NULL) {
    $this->webformSubmission = $webform_submission;
    // Apply variants to the webform.
    $webform = $webform_submission->getWebform();
    $webform->applyVariants($webform_submission);

    $form['content_type'] = [
      '#type' => 'select',
      '#options' => [
        'scholarly_work' => $this->t('Scholarly Work'),
        'asu_repository_item' => $this->t('Repository Item'),
      ],
      '#default_value' => 'scholarly_work',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Create Item',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $this->createItem($this->webformSubmission, $form_state->getValue('content_type'));

    if ($node) {
      $t_args = [
        ':url' => $node->toUrl()->toString(),
      ];
      $this->messenger()->addStatus($this->t('Successfuly created repository item. <a href=":url">View item</a>', $t_args));
    }
    else {
      $this->messenger()->addError($this->t('Could not create the repository item.'));
    }
  }

  /* ************************************************************************ */
  // Helper methods.
  /* ************************************************************************ */

  /**
   * Creates a repository item from a given webform submission.
   */
  protected function createItem(WebformSubmissionInterface $webform_submission, $content_type) {
    // Get an array of the values from the submission.
    $values = $webform_submission->getData();
    $files = $values['file'];
    $new_dest = "private://c160/";
    $taxo_manager = $this->entityTypeManager->getStorage('taxonomy_term');

    $copyright_term_arr = $taxo_manager->loadByProperties(['name' => 'In Copyright']);
    $copyright_term = reset($copyright_term_arr);

    $config = $this->configFactory->get('self_deposit.selfdepositsettings');
    if ($config->get('collection_for_deposits')) {
      $member_of = $config->get('collection_for_deposits');
    }

    $paragraph = Paragraph::create(
        [
          'type' => 'complex_title',
          'field_main_title' => $values['item_title'],
        ]
    );

    $paragraph->save();

    $keywords = [];
    foreach ($values['keywords'] as $key) {
      $keywords[] = $this->depositUtils->getOrCreateTerm($key, 'subject');
    }

    $node_args = [
      'type' => $content_type,
      'langcode' => 'en',
      'created' => time(),
      'changed' => time(),
      'uid' => $this->currentUser->id(),
      'moderation_state' => 'draft',
      'title' => $values['item_title'],
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
      'field_subjects' => $keywords,
      'field_copyright_statement' => [
        ['target_id' => $copyright_term->id()],
      ],
      'field_embargo_release_date' => [
        $values['embargo_release_date'] . "T23:59:59",
      ],
    ];

    if ($member_of) {
      $node_args['field_member_of'] = [['target_id' => $member_of]];
    }

    if ($values['reuse_permissions']) {
      $node_args['field_reuse_permissions'] = [['target_id' => $values['reuse_permissions']]];
    }
    $node = Node::create($node_args);

    $perm_term = current($taxo_manager->loadByProperties([
      'name' => $values['file_permissions_select'],
      'vid' => 'islandora_access',
    ]));

    // Save an initial version so asu_repository_items can link components.
    $node->save();
    $files = $values['file'];
    $new_dest = "private://c130/";

    // This is where the similarities between
    // asu_repositori_item and scholarly_content end.
    if ($content_type == 'scholarly_work') {
      $work_products = [];
      foreach ($files as $file_id) {
        $file = $this->entityTypeManager->getStorage('file')->load(intval($file_id));
        $file_copy = $this->fileRepo->copy($file, $new_dest . $filename);
        $file_model_properties = $this->depositUtils->getModel($file_copy->getMimeType(), $file_copy->getFilename());
        $media_properties = [
          'bundle' => $file_model_properties[1],
          'uid' => $this->currentUser->id(),
          $file_model_properties[2] => ['target_id' => $file_copy->id()],
        ];
        if ($perm_term) {
          $media_properties['field_access_terms'] = ['target_id' => $perm_term->id()];
        }
        $media = Media::create($media_properties);
        $media->save();
        $work_products[] = ['target_id' => $media->id()];
      }
      $node->set('field_work_products', $work_products);
    }
    elseif ($content_type == 'asu_repository_item') {
      if (count($files) > 1) {
        $models = $taxo_manager->loadByProperties(['name' => 'Complex Object', 'vid' => 'islandora_models']);
        $model = reset($models);
        $node->set('field_model', $model->id());
        foreach ($files as $fkey => $file_id) {
          $file = $this->entityTypeManager->getStorage('file')->load(intval($file_id));
          $mime = $file->getMimeType();
          $filename = $file->getFilename();
          $file = $this->fileRepo->copy($file, $new_dest . $filename);
          $file_id = $file->id();
          [$fmodel, $fmedia_type, $ffield_name] = $this->depositUtils->getModel($mime, $filename);
          $ftaxo_terms = $taxo_manager->loadByProperties(['name' => $fmodel]);
          $ftaxo_term = reset($ftaxo_terms);
          $child_node = $this->createNode($webform_submission, $values, $filename, $ftaxo_term, $copyright_term, $perm_term, $node->id());
          $this->depositUtils->createMedia($fmedia_type, $ffield_name, $fkey, $child_node->id());
        }
      }
      else {
        $file = $this->entityTypeManager->getStorage('file')->load(intval($files[0]));
        $mime = $file->getMimeType();
        $filename = $file->getFilename();
        $files[0] = $this->fileRepo->copy($file, $new_dest . $filename)->id();
        [$model, $media_type, $field_name] = $this->depositUtils->getModel($mime, $filename);
        $ftaxo_terms = $taxo_manager->loadByProperties(['name' => $model]);
        $ftaxo_term = reset($ftaxo_terms);
        $node->set('field_model', $ftaxo_term->id());
        $this->depositUtils->createMedia($media_type, $field_name, $files[0], $node->id());
      }
    }

    $node->save();

    $webform_submission->setElementData('item_node', $node->id());
    return $node;
  }

  /**
   * Helper for asu repository items complex objects.
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
      'uid' => $this->currentUser->id(),
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

}
