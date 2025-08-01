<?php

namespace Drupal\asu_item_extras\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Action\ActionManager;

/**
 * Form to batch populate compound object thumbnails.
 */
class BatchPopulateCompoundObjThumbs extends FormBase {


  /**
   * The action manager.
   *
   * @var Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new BatchPopulateCompoundObjThumbs form.
   *
   * @param Drupal\Core\Action\ActionManager $actionManager
   *   The action manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   */
  public function __construct(
    ActionManager $actionManager,
    EntityTypeManagerInterface $entityTypeManager,
    Connection $connection,
  ) {
    $this->actionManager = $actionManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.action'),
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'batch_populate_compound_object_thumbnails';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['asu_repository.settings'];
  }

  /**
   * Processes a batch of nodes to copy thumbnails from the first member.
   */
  public static function processNodes($nids, array &$context) {
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    $action = \Drupal::entityTypeManager()->getStorage('action')->load('copy_islandora_thumbnail_media_from_first_child_node');
    $action->execute($nodes);
    $context['results'][] = count($nodes);
    $context['message'] = t('Processed @count nodes', ['@count' => array_sum($context['results'])]);
  }

  /**
   * Callback for batch processing completion.
   */
  public static function batchFinished($success, array $results, array $operations) {
    if ($success) {
      \Drupal::messenger()->addStatus(t('Processed @count repository nodes.', ['@count' => array_sum($results)]));
    }
    else {
      \Drupal::messenger()->addError(t('An error occurred during batch processing.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['node_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Max nodes per run'),
      '#default_value' => 10,
      '#min' => 1,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run'),
    ];

    return $form;
  }

  /**
   * Submits the form and starts the batch process.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // parent::submitForm($form, $form_state);.
    $limit = (int) $form_state->getValue('node_limit');

    $nids = array_slice($this->getEligibleRepositoryNodeIds(), 0, $limit);
    $chunks = array_chunk($nids, 5);
    if (empty($nids)) {
      $this->messenger()->addStatus($this->t('No eligible nodes to process.'));
      return;
    }

    $operations = [];
    foreach ($chunks as $nids) {
      $operations[] = [
        [static::class, 'processNodes'],
        [$nids],
      ];
    }

    $batch = [
      'title' => $this->t('Copying Compount Object thumbnails.'),
      'operations' => $operations,
      'finished' => [static::class, 'batchFinished'],
    ];
    batch_set($batch);
  }

  /**
   * Gets a list of eligible repository node IDs that do not have a thumbnail.
   */
  protected function getEligibleRepositoryNodeIds(): array {

    $t = current($this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => 'Thumbnail Image']));
    $th_sq = $this->connection->select('media__field_media_of', 'mo');
    $th_sq->join('media__field_media_use', 'mu', 'mo.entity_id = mu.entity_id');
    $th_sq->fields('mo', ['field_media_of_target_id']);
    $th_sq->condition('mu.field_media_use_target_id', $t->id());

    $cm_sq = $this->connection->select('node__field_model', 'mo');
    $cm_sq->fields('mo', ['entity_id']);
    $cm_sq->join('taxonomy_term_field_data', 't', 'mo.field_model_target_id = t.tid');
    $cm_sq->condition('t.name', ['Page', 'Image', 'Document', 'Video', 'Digital Document'], 'IN');
    $cm_sq->condition('t.vid', 'islandora_models');

    $q = $this->connection->select('node', 'n');
    $q->join('node__field_member_of', 'member', 'n.nid = member.field_member_of_target_id');
    $q->fields('n', ['nid']);
    $q->condition('n.nid', $th_sq, 'NOT IN');
    $q->condition('member.entity_id', $cm_sq, 'IN');
    $q->condition('type', 'asu_repository_item');

    return $q->execute()->fetchCol() ?: [];
  }

}
