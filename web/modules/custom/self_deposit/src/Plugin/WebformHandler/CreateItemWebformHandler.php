<?php

namespace Drupal\self_deposit\Plugin\WebformHandler;

use Drupal\asu_deposit_methods\DepositUtils;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a new repository item entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Create a repository item",
 *   label = @Translation("Create a repository item"),
 *   category = @Translation("Entity Creation"),
 *   description = @Translation("Creates a new repository item from Webform Submissions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class CreateItemWebformHandler extends WebformHandlerBase {

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
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator
   * @param \Drupal\asu_deposit_methods\DepositUtils $deposit_utils
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    WebformSubmissionConditionsValidatorInterface $conditions_validator,
    DepositUtils $deposit_utils,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->loggerFactory = $logger_factory->get('custom_webform_handler');
    $this->configFactory = $config_factory;
    $this->conditionsValidator = $conditions_validator;
    $this->entityTypeManager = $entity_type_manager;
    $this->depositUtils = $deposit_utils;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return \Drupal\Core\Plugin\ContainerFactoryPluginInterface|EmailWebformHandler|WebformHandlerBase|WebformHandlerInterface|WebformHandlerMessageInterface|static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('asu_deposit_methods.deposit_utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * Actually creates the node.
   */
  private function createNode($webform_submission, $values, $title, $copyright_term, $perm_term, $member_of) {
    $paragraph = Paragraph::create(
      ['type' => 'complex_title', 'field_main_title' => $title]
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
      'field_embargo_release_date' => [
        $values['embargo_release_date'] . "T23:59:59",
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
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    // Get an array of the values from the submission.
    $values = $webform_submission->getData();

    $copyright_term = current($taxo_manager->loadByProperties([
      'name' => 'In Copyright',
    ]));

    $perm_term = current($taxo_manager->loadByProperties([
      'name' => $values['file_permissions_select'],
    ]));

    $config = $this->configFactory->get('self_deposit.selfdepositsettings');
    if ($config->get('collection_for_deposits')) {
      $member_of = $config->get('collection_for_deposits');
    }

    $node = $this->createNode($webform_submission, $values, $values['item_title'], $taxo_term, $copyright_term, $perm_term, $member_of);
    $files = $values['file'];
    $file_repository = \Drupal::service('file.repository');
    $new_dest = "private://c160/";

    $work_products = [];
    foreach ($files as $file_id) {
      $file = $this->entityTypeManager->getStorage('file')->load(intval($file_id));
      $file_copy = $file_repository->copy($file, $new_dest . $filename);
      $file_model_info = $this->depositUtils->getModel($file_copy->getMimeType(), $file_copy->getFilename());
      $media = Media::create([
        'bundle' => $file_model_infor[1],
        $file_model_properties[2] => ['target_id' => $file_copy->id()],
        'uid' => \Drupal::currentUser()->id(),
        'field_access_terms' => ['target_id' => $perm_term->id()],
      ]);
      $media->save();
      $work_products[] = ['target_id' => $media->id()];
    }

    $node->set('field_work_products', $work_products);
    $node->save();

    $webform_submission->setElementData('item_node', $node->id());
  }

}
