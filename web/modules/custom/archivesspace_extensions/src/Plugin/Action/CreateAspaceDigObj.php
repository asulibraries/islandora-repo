<?php

namespace Drupal\archivesspace_extensions\Plugin\Action;

use Drupal\archivesspace\ArchivesSpaceSession;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a handle for an object.
 *
 * @Action(
 *   id = "create_aspace_dig_obj",
 *   label = @Translation("Create/Update an Archivesspace Digital Object"),
 *   type = "node"
 * )
 */
class CreateAspaceDigObj extends ActionBase implements ContainerFactoryPluginInterface {
  use \Drupal\Core\StringTranslation\StringTranslationTrait;
  /**
   * Logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * ArchivesSpaceSession that will allow us to issue API requests.
   *
   * @var \Drupal\archivesspace\ArchivesSpaceSession
   */
  protected $archivesspaceSession;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        LoggerInterface $logger
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->archivesspaceSession = new ArchivesSpaceSession();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
          $configuration,
          $plugin_id,
          $plugin_definition,
          $container->get('logger.channel.archivesspace')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!$entity) {
      return;
    }
    $entity_type = $entity->getEntityTypeId();

    if (!$entity_type == 'node') {
      return;
    }

    $extensions_settings = \Drupal::config('archivesspace_extensions.settings');

    // Sanity checking for required bits.
    if (!$entity->hasField('field_typed_identifier') || $entity->field_typed_identifier->isEmpty() || empty($entity->field_typed_identifier->entity->field_identifier_value->value)) {
      $message = $this->t('Node %nid does not have an identifier value; using the UUID.', [
        '%nid' => $entity->id(),
      ]);
      $this->logger->warning($message);
      \Drupal::messenger()->addWarning($message);
      $identifier = $entity->uuid();
    }
    else {
      $identifier = $entity->get('field_typed_identifier')->entity->field_identifier_value->value;
    }
    if (!$entity->hasField($extensions_settings->get('reference_field'))) {
      $this->logger->warning('Node %nid does not have an ArchivesSpace reference field %field.', [
        '%nid' => $entity->id(),
        '%field' => $extensions_settings->get('reference_field'),
      ]);
      return;
    }
    $reference_paragraph = $entity->get($extensions_settings->get('reference_field'))->entity;
    if (!$reference_paragraph) {
      $this->logger->warning('Node %nid does not have an ArchivesSpace reference field value.', [
        '%nid' => $entity->id(),
      ]);
      return;
    }
    if (!$reference_paragraph->get($extensions_settings->get('repo_id_field')) || $reference_paragraph->get($extensions_settings->get('repo_id_field'))->isEmpty()) {
      $this->logger->warning('Node %nid ArchivesSpace reference field value has no repository identifer.', [
        '%nid' => $entity->id(),
      ]);
      return;
    }
    $repo_id = $reference_paragraph->get($extensions_settings->get('repo_id_field'))->value;
    $archival_object = [];
    if (!$reference_paragraph->hasField($extensions_settings->get('ao_ref_id_field')) || $reference_paragraph->get($extensions_settings->get('ao_ref_id_field'))->isEmpty()) {
      $this->logger->warning('Node %nid ArchivesSpace reference field value has no archival object ref_id.', [
        '%nid' => $entity->id(),
      ]);
      return;
    }
    $ao_results = $this->archivesspaceSession->request('GET', "/repositories/{$repo_id}/find_by_id/archival_objects", [
      "ref_id" => [
        trim($reference_paragraph->get($extensions_settings->get('ao_ref_id_field'))->value),
      ],
      'resolve' => ['archival_objects'],
    ]);
    if (!array_key_exists('archival_objects', $ao_results) || empty($ao_results['archival_objects'])) {
      $this->logger->warning('Could not find archival object with ref_id %ref_id.', [
        '%ref_id' => $reference_paragraph->get($extensions_settings->get('ao_ref_id_field'))->value,
      ]);
      return;
    }
    $archival_object = $ao_results['archival_objects'][0]['_resolved'];

    // Get URL for digital object file version.
    $link = ($entity->hasField($extensions_settings->get('link_field')) && !$entity->get($extensions_settings->get('link_field'))->isEmpty()) ? $entity->get($extensions_settings->get('link_field'))->value : $entity->toUrl('canonical', [
      'https' => TRUE,
      'absolute' => TRUE,
    ])->toString();

    // Create or Update ArchivesSpace digital object.
    if ($reference_paragraph->get($extensions_settings->get('do_id_field'))->isEmpty()) {
      // Create digital object.
      $do_json = [
        'jsonmodel_type' => 'digital_object',
        'title' => $entity->getTitle(),
        'file_versions' => [
          [
            'file_uri' => $link,
          ],
        ],
        'linked_instances' => [
          [
            'ref' => $archival_object['uri'],
          ],
        ],
        'digital_object_id' => $identifier,
      ];
      $do_json['publish'] = ($entity->status->value) ? TRUE : FALSE;
      try {
        $create_response = $this->createUpdateDigitalObject($do_json, $repo_id);
      }
      catch (ClientException $e) {
        $this->logger->error("Could not create digital object '%did' for node '%nt' (%nid): %message", [
          '%did' => $identifier,
          '%nt' => $entity->label(),
          '%nid' => $entity->id(),
          '%message' => $e->getMessage(),
        ]);
        return;
      }
      $do_uri = $create_response['uri'];
      $do_id = $this->getIdFromUri($do_uri);
      $reference_paragraph->set($extensions_settings->get('do_id_field'), $do_id);
      $reference_paragraph->save();

      // Update archival object.
      $archival_object['instances'][] = [
        'lock_version' => 0,
        'instance_type' => 'digital_object',
        'jsonmodel_type' => 'instance',
        'is_representative' => FALSE,
        'digital_object' => [
          'ref' => $do_uri,
        ],
      ];
      $this->logger->debug("archival object: " . json_encode($archival_object));
      $response = $this->archivesspaceSession->request('POST', $archival_object['uri'], $archival_object);
      $this->logger->info("Updated archival object %ao_uri with digital object %do_uri (%body)", [
        '%ao_uri' => $archival_object['uri'],
        '%do_uri' => $do_uri,
        '%body' => json_encode($response),
      ]);
    }
    else {
      // Update digital object.
      $do_id = $reference_paragraph->get($extensions_settings->get('do_id_field'))->value;
      try {
        $do = $this->archivesspaceSession->request('GET', "/repositories/{$repo_id}/digital_objects/{$do_id}");
      }
      catch (ClientException $e) {
        $this->logger->error("Could not update digital object '%did' for node '%nt' (%nid): %message", [
          '%did' => $identifier,
          '%nt' => $entity->label(),
          '%nid' => $entity->id(),
          '%message' => $e->getMessage(),
        ]);
        return;
      }
      $do['title'] = $entity->getTitle();
      $do['digital_object_id'] = $identifier;
      $do['publish'] = ($entity->status->value) ? TRUE : FALSE;
      $do['file_versions'][0]['file_uri'] = $link;
      $update_response = $this->createUpdateDigitalObject($do, $repo_id, $do_id);
      if (!empty($update_reponse['warnings'])) {
        $this->logger->info("Updated digital object %do_uri for node '%nt' (%nid) with warnings: '%warnings'", [
          '%do_uri' => $do['uri'],
          '%nt' => $entity->label(),
          '%nid' => $entity->id(),
          '%warnings' => json_encode($update_response['warnings']),
        ]);
      }
      else {
        $this->logger->info("Updated digital object %do_uri for node '%nt' (%nid)", [
          '%do_uri' => $do['uri'],
          '%nt' => $entity->label(),
          '%nid' => $entity->id(),
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Creates or updates an ArchivesSpace digital object.
   */
  private function createUpdateDigitalObject($do_json, $repo_id = NULL, $do_id = NULL) {
    $url = "/repositories/{$repo_id}/digital_objects";
    if ($do_id) {
      $url .= "/{$do_id}";
    }
    $response = $this->archivesspaceSession->request('POST', $url, $do_json);
    if ($response['status'] == 'Created') {
      $this->messenger()->addStatus('Archivesspace digital object created');
    }
    if ($response['status'] == 'Updated') {
      $this->messenger()->addStatus('Archivesspace digital object updated');
    }
    return $response;
  }

  /**
   * Returns the numeric ID from an ArchivesSpace URI.
   */
  private function getIdFromUri($uri) {
    $parts = explode('/', $uri);
    return end($parts);
  }

}
