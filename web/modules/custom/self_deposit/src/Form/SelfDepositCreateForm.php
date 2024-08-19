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
   * Creates a repository item from a given webform submission.
   */
  protected function createItem(WebformSubmissionInterface $webform_submission) {
    // Get an array of the values from the submission.
    $values = $webform_submission->getData();
    $files = $values['file'];
    $file_repository = \Drupal::service('file.repository');
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
      'type' => 'scholarly_work',
      'langcode' => 'en',
      'created' => time(),
      'changed' => time(),
      'uid' => \Drupal::currentUser()->id(),
      'moderation_state' => 'draft',
      'title' => $title,
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

    $files = $values['file'];
    $file_repository = \Drupal::service('file.repository');
    $new_dest = "private://c130/";
    $work_products = [];
    foreach ($files as $file_id) {
      $file = $this->entityTypeManager->getStorage('file')->load(intval($file_id));
      $file_copy = $file_repository->copy($file, $new_dest . $filename);
      $file_model_properties = $this->depositUtils->getModel($file_copy->getMimeType(), $file_copy->getFilename());
      $media_properties = [
        'bundle' => $file_model_properties[1],
        'uid' => \Drupal::currentUser()->id(),
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
    $node->save();

    $webform_submission->setElementData('item_node', $node->id());
    return $node;
  }

}
