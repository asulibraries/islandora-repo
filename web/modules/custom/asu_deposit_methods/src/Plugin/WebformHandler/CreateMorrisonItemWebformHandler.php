<?php

namespace Drupal\asu_deposit_methods\Plugin\WebformHandler;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\asu_deposit_methods\DepositUtils;

/**
 * Create a new repository item entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "create_morrison_repository_item",
 *   label = @Translation("Create a Morrison repository item"),
 *   category = @Translation("Entity Creation"),
 *   description = @Translation("Creates a new Morrison repository item from Webform Submissions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class CreateMorrisonItemWebformHandler extends WebformHandlerBase {

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
    DepositUtils $deposit_utils
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
  private function createNode($webform_submission, $values, $title, $model, $copyright_term, $perm_term, $member_of) {
    $paragraph = Paragraph::create(
      ['type' => 'complex_title', 'field_main_title' => $title]
    );

    $paragraph->save();
    $titles = $contribs = [];
    $titles[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];

    if (array_key_exists('alternate_title', $values)) {
      foreach ($values['alternate_title'] as $alt) {
        $alt_title = Paragraph::create(
          ['type' => 'complex_title', 'field_main_title' => $alt]
        );
        $alt_title->save();
        $titles[] = [
          'target_id' => $alt_title->id(),
          'target_revision_id' => $alt_title->getRevisionId(),
        ];
      }
    }

    foreach ($values['additional_contributors'] as $ac) {
      // Make additional contribs as ctb.
      array_push($contribs, $this->depositUtils->getOrCreateTerm($ac['last'] . ", " . $ac['first'], 'person', 'relators:ctb'));
    }
    foreach ($values['institutional_contributors'] as $ic) {
      // Make insitutional contribs as ctb.
      array_push($contribs, $this->depositUtils->getOrCreateTerm($ic, 'corporate_body', 'relators:ctb'));
    }

    foreach ($values['event_contributors'] as $ic) {
      // Make event contribs as ctb.
      array_push($contribs, $this->depositUtils->getOrCreateTerm($ic, 'conference', 'relators:ctb'));
    }

    if (array_key_exists('series', $values) && $values['series'] != "") {
      $series_val = $values['series'];
    }

    if (array_key_exists('date_created', $values) && $values['date_created'] != "") {
      $created_date_val = $values['date_created'];
    }


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
      'moderation_state' => 'published',
      'field_title' => $titles,
      'field_rich_description' => [
        'value' => $values['item_description'],
        'format' => 'description_restricted_items',
      ],
      'field_reuse_permissions' => [
        ['target_id' => $values['reuse_permissions']],
      ],
      'field_subjects' => $keywords,
      'field_linked_agent' => $contribs,
      'field_edtf_date_created' => [
        'value' => $created_date_val,
      ],
      'field_series' => [
        'value' => $series_val,
      ],
      'field_extent' => [
        ['value' => $values['number_of_pages']],
      ],
      'field_copyright_statement' => [
        ['target_id' => $values['copyright_statement']],
      ],
      'field_default_derivative_file_pe' => [
        ['target_id' => $perm_term->id()],
      ],
      'field_default_original_file_perm' => [
        ['target_id' => $perm_term->id()],
      ],
      'field_model' => [
        ['target_id' => $model->id()],
      ],
      'field_member_of' => [
        ['target_id' => $member_of],
      ],
    ];

    if (array_key_exists('embargo_release_date', $values)) {
      $embargo_vals = explode('T', $values['embargo_release_date']);
      $node_args['field_embargo_release_date'] = ['value' => $embargo_vals[0] . "T23:59:59"];
    }
    if (array_key_exists('language1', $values)) {
      $node_args['field_language'] = [['target_id' => $values['language1']]];
    }
    if (array_key_exists('copyright_date', $values) && $values['copyright_date'] != "") {
      $node_args['field_edtf_copyright_date'] = ['value' => $values['copyright_date']];
    }
    if (array_key_exists('open_access', $values) && $values['open_access'] != "") {
      $node_args['field_open_access'] = ['value' => $values['open_access']];
    }
    if (array_key_exists('issuance', $values) && $values['issuance'] != "") {
      $node_args['field_issuance'] = ['value' => $values['issuance']];
    }
    if (array_key_exists('edition', $values) && $values['edition'] != "") {
      $node_args['field_edition'] = ['value' => $values['edition']];
    }

    if (array_key_exists('preferred_citation', $values) && $values['preferred_citation'] != "") {
      $node_args['field_preferred_citation'] = ['value' => $values['preferred_citation'], 'format' => 'basic_html'];
    }

    if (array_key_exists('place_of_publication', $values) && $values['place_of_publication'] != "") {
      $node_args['field_place_published'] = ['value' => $values['place_of_publication']];
    }

    $genres = [];
    foreach ($values['genre'] as $key) {
      $kterm = $this->depositUtils->getOrCreateTerm($key, 'genre');
      array_push($genres, $kterm);
    }
    if (count($genres) > 0) {
      $node_args['field_genre'] = $genres;
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
    $files = $values['file'];

    if (count($files) > 1) {
      $model = 'Complex Object';
      $child_files = [];
      foreach ($files as $file_id) {
        $file = $this->entityTypeManager->getStorage('file')->load(intval($file_id));
        $mime = $file->getMimeType();
        $filename = $file->getFilename();
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
      list($model, $media_type, $field_name) = $this->depositUtils->getModel($mime, $filename);
    }

    $term = $model;
    $taxo_manager = $this->entityTypeManager->getStorage('taxonomy_term');
    $taxo_terms = $taxo_manager->loadByProperties(['name' => $term]);
    $taxo_term = reset($taxo_terms);

    $copyright_term_arr = $taxo_manager->loadByProperties(['name' => $values['copyright_statement']]);
    $copyright_term = reset($copyright_term_arr);

    $config = \Drupal::config('asu_deposit_methods.depositsettings');
    if ($config->get('collection_for_morrison')) {
      $member_of = $config->get('collection_for_morrison');
      $collection = $this->entityTypeManager->getStorage('node')->load($member_of);
      $perm_term = $collection->get('field_default_original_file_perm')->entity;
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
      $this->depositUtils->createMedia($media_type, $field_name, $files[0], $node->id(), 'Public');
    }

    $webform_submission->setElementData('item_node', $node->id());
  }

}
