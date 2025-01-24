<?php

namespace Drupal\self_deposit\Plugin\WebformHandler;

use Drupal\asu_deposit_methods\DepositUtils;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a new repository item entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Create a Barrett repository item",
 *   label = @Translation("Create a Barrett repository item"),
 *   category = @Translation("Entity Creation"),
 *   description = @Translation("Creates a new Barrett repository item from Webform Submissions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class CreateBarrettItemWebformHandler extends WebformHandlerBase {

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
  private function createNode($webform_submission, $values, $title, $copyright_term, $perm_term, $member_of, $user = NULL) {
    $paragraph = Paragraph::create([
      'type' => 'complex_title',
      'field_main_title' => $title,
    ]);

    $paragraph->save();

    $keywords = [];
    foreach ($values['keywords'] as $key) {
      $keywords[] = $this->depositUtils->getOrCreateTerm($key, 'subject');
    }

    $contribs = [];
    if (array_key_exists('full_name', $values)) {
      array_push($contribs, $this->depositUtils->getOrCreateTerm($values['full_name']['last'] . ", " . $values['full_name']['first'], 'person', 'relators:aut'));
    }
    elseif (array_key_exists('your_name', $values)) {
      array_push($contribs, $this->depositUtils->getOrCreateTerm($values['your_name'], 'person', 'relators:aut'));
    }
    else {
      array_push($contribs, $this->depositUtils->getOrCreateTerm($values['student_name']['last'] . ", " . $values['student_name']['first'], 'person', 'relators:aut'));
    }
    foreach ($values['group_members'] as $gm) {
      // Make group members as aut.
      array_push($contribs, $this->depositUtils->getOrCreateTerm($gm['last'] . ", " . $gm['first'], 'person', 'barrettrelators:cau'));
    }

    foreach ($values['thesis_director'] as $td) {
      // Make group members as ths.
      array_push($contribs, $this->depositUtils->getOrCreateTerm($td['last'] . ", " . $td['first'], 'person', 'barrettrelators:ths'));
    }

    foreach ($values['committee_members'] as $cm) {
      // Make group members as dgc.
      array_push($contribs, $this->depositUtils->getOrCreateTerm($cm['last'] . ", " . $cm['first'], 'person', 'barrettrelators:dgc'));
    }

    foreach ($values['additional_contributors'] as $ac) {
      // Make additional contribs as ctb.
      array_push($contribs, $this->depositUtils->getOrCreateTerm($ac['last'] . ", " . $ac['first'], 'person', 'relators:ctb'));
    }

    foreach ($values['institutional_contributors'] as $ic) {
      // Make insitutional contribs as ctb.
      array_push($contribs, $this->depositUtils->getOrCreateTerm($ic, 'corporate_body', 'relators:ctb'));
    }

    array_push($contribs, $this->depositUtils->getOrCreateTerm('Barrett, The Honors College', 'corporate_body', 'relators:ctb'));

    if ($user && $user->hasField('field_programs')) {
      $prgs = $user->get('field_programs')->getValue();
      if (is_array($prgs)) {
        foreach ($prgs as $prg) {
          array_push($contribs, $this->depositUtils->getOrCreateTerm($prg, 'corporate_body', 'relators:ctb'));
        }
      }
      else {
        // This code *should* be un-reachable as `getValue` should always
        // return an array whereas the value property we called previously
        // always returned a string. But we'll leave it here for now,
        // just in case.
        if ($prgs != "") {
          array_push($contribs, $this->depositUtils->getOrCreateTerm($prgs, 'corporate_body', 'relators:ctb'));
        }
      }
    }

    if (array_key_exists('series', $values) && $values['series'] != "") {
      $series_val = $values['series'];
    }

    if (array_key_exists('date_created', $values) && $values['date_created'] != "") {
      $created_date_val = $values['date_created'];
    }

    $date_submitted = $webform_submission->getCreatedTime();
    $month = \Drupal::service('date.formatter')->format($date_submitted, 'custom', 'm');
    $year =
    \Drupal::service('date.formatter')->format($date_submitted, 'custom', 'Y');
    if ($month <= 7) {
      $spring_year = $year;
      $created_month = "05";
    }
    if ($month >= 8) {
      $created_month = "12";
      $fall_year = $year;
    }
    if (!isset($fall_year)) {
      $fall_year = $spring_year - 1;
    }
    if (!isset($spring_year)) {
      $spring_year = $fall_year + 1;
    }
    if (!isset($series_val)) {
      $series_val = "Academic Year " . $fall_year . "-" . $spring_year;
    }
    if (!isset($created_date_val)) {
      $created_date_val = $year . "-" . $created_month;
    }

    $mod_state = 'draft';
    if ($webform_submission->getWebform()->id() == 'barrett_staff_submission') {
      $mod_state = 'published';
    }

    $node_args = [
      'type' => 'scholarly_work',
      'langcode' => 'en',
      'created' => time(),
      'changed' => time(),
      'moderation_state' => $mod_state,
      'field_title' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
      'field_reuse_permissions' => [
        [
          'target_id' => $values['reuse_permissions'],
        ],
      ],
      'field_subject' => $keywords,
      'field_linked_agent' => $contribs,
      'field_edtf_date_created' => [
        'value' => $created_date_val,
      ],
      'field_series' => [
        'value' => $series_val,
      ],
      'field_copyright_statement' => [
        [
          'target_id' => $copyright_term->id(),
        ],
      ],
      'field_member_of' => [
          [
            'target_id' => $member_of,
          ],
      ],
    ];
    if ($values['number_of_pages']) {
      $node_args['field_extent'] = [
        [
          'value' => $values['number_of_pages'] . " pages",
        ],
      ];
    }
    if ($values['item_description']) {
      $node_args['field_rich_description'] = [
        'value' => $values['item_description'],
        'format' => 'description_restricted_items',
      ];
    }
    if ($user) {
      $node_args['uid'] = $user->id();
    }
    else {
      $node_args['uid'] = \Drupal::currentUser()->id();
    }

    if (array_key_exists('embargo_release_date', $values)) {
      $embargo_vals = explode('T', $values['embargo_release_date']);
      $node_args['field_embargo_release_date'] = ['value' => $embargo_vals[0] . "T23:59:59"];
    }
    if (array_key_exists('language1', $values)) {
      $node_args['field_language'] = [['target_id' => $values['language1']]];
    }

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

    $taxo_manager = $this->entityTypeManager->getStorage('taxonomy_term');
    $copyright_term_arr = $taxo_manager->loadByProperties(['name' => 'In Copyright']);
    $copyright_term = reset($copyright_term_arr);

    $perm_term_arr = $taxo_manager->loadByProperties(['name' => 'ASU Only']);
    $perm_term = reset($perm_term_arr);
    $config = \Drupal::config('self_deposit.selfdepositsettings');
    if ($config->get('barrett_collection_for_deposits')) {
      $member_of = $config->get('barrett_collection_for_deposits');
    }

    // Create user, populate from student_asurite, student_id.
    $user = user_load_by_name($values['student_asurite']);
    if ($user == NULL) {
      $user = User::create();
      $user->enforceIsNew();
      $user->setEmail($values['student_asurite'] . "@asu.edu");
      $user->setUsername($values['student_asurite']);
      $user->set('field_last_name', $values['student_name']['last']);
      $user->set('field_first_name', $values['student_name']['first']);
      $user->set('field_honors', TRUE);
      $user->set('field_emplid', $values['student_id']);
      $user->save();
      \Drupal::moduleHandler()->invoke('asu_permissions', 'user_insert', [$user]);
    }
    $node = $this->createNode($webform_submission, $values, $values['item_title'], $copyright_term, $perm_term, $member_of, $user);
    $files = $values['file'];
    $file_repository = \Drupal::service('file.repository');
    $new_dest = "private://c130/";
    $work_products = [];
    foreach ($files as $file_id) {
      $file = $this->entityTypeManager->getStorage('file')->load(intval($file_id));
      $file_copy = $file_repository->copy($file, $new_dest . $filename);
      $file_model_properties = $this->depositUtils->getModel($file_copy->getMimeType(), $file_copy->getFilename());
      $media = Media::create([
        'bundle' => $file_model_properties[1],
        'uid' => \Drupal::currentUser()->id(),
        $file_model_properties[2] => ['target_id' => $file_copy->id()],
        'field_access_terms' => ['target_id' => $perm_term->id()],
      ]);
      $media->save();
      $work_products[] = ['target_id' => $media->id()];
    }
    $node->set('field_work_products', $work_products);
    $node->save();
    $webform_submission->setOwnerId($user->id());

    $webform_submission->setElementData('item_node', $node->id());
  }

}
