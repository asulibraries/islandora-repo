<?php

namespace Drupal\asu_item_extras\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\islandora\IslandoraUtils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copies the thumbnail from the first child node using IslandoraUtils.
 *
 * @Action(
 *   id = "copy_first_member_thumbnail",
 *   label = @Translation("Copy Islandora thumbnail media from first child node"),
 *   type = "node"
 * )
 */
class CopyFirstMemberThumbnail extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * IslandoraUtils service.
   *
   * @var \Drupal\islandora\IslandoraUtils
   */
  protected $utils;


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * Filesystem service.
   *
   * @var \Drupal\Core\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new CopyFirstMemberThumbnail action.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\islandora\IslandoraUtils $utils
   *   The IslandoraUtils service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    IslandoraUtils $utils,
    EntityTypeManagerInterface $entity_type_manager,
    FileRepositoryInterface $file_repository,
    FileSystemInterface $file_system,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->utils = $utils;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileRepository = $file_repository;
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('asu_item_extras');
  }

  /**
   * Creates an instance of the action.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   A new instance of the action.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('islandora.utils'),
      $container->get('entity_type.manager'),
      $container->get('file.repository'),
      $container->get('file_system'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {

    if (!$entity instanceof NodeInterface) {
      return;
    }

    // Skip if we already have a thumbnail.
    $thumbnail_term = $this->utils->getTermForUri("http://pcdm.org/use#ThumbnailImage");
    $existing_thumbnail = $this->utils->getMediaWithTerm($entity, $thumbnail_term);
    if ($existing_thumbnail && file_exists($existing_thumbnail->field_media_image?->entity?->uri?->value)) {
      $this->logger->info('Node @id already has a thumbnail, skipping copy.', ['@id' => $entity->id()]);
      return;
    }

    // Query for child nodes that reference this node via field_member_of.
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('field_member_of', $entity->id())
      ->sort('field_weight', 'ASC')
      ->sort('created', 'ASC')
      ->accessCheck(TRUE);

    $nids = $query->execute();

    foreach ($nids as $nid) {
      $member = $this->entityTypeManager->getStorage('node')->load($nid);
      $thumbnail_media = $this->utils->getMediaWithTerm($member, $thumbnail_term);
      if ($thumbnail_media) {
        $original_file = $thumbnail_media->field_media_image->entity ?? NULL;
        if (!$original_file) {
          $this->logger->warning('Could not copy first member thumbnail for @item_id. No thumbnail media file found for member: @member_id',
            ['@member_id' => $member->id(), '@item_id' => $entity->id()]
            );
          return;
        }
        // Copy the file & media.
        try {
          $path = dirname($original_file->uri->value);
          $this->fileSystem->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY);
          $new_file = $this->fileRepository->copy($original_file, str_replace($member->id(), $entity->id(), $original_file->uri->value));
          $new_file->setPermanent();
          $new_file->save();

          $new_thumbnail = $this->entityTypeManager->getStorage('media')->create([
            'bundle' => $thumbnail_media->bundle(),
            'name' => str_replace($member->id(), $entity->id(), $thumbnail_media->getName()),
            'field_media_image' => [
              'target_id' => $new_file->id(),
            ],
            'field_media_of' => [
              'target_id' => $entity->id(),
            ],
            'field_media_use' => [
              'target_id' => $thumbnail_media->field_media_use->target_id,
            ],
          ]);
          $new_thumbnail->save();
          return;
        }
        catch (\Exception $e) {
          $this->logger->error('Failed to copy thumbnail media for @item_id from member @member_id: @message',
            ['@item_id' => $entity->id(), '@member_id' => $member->id(), '@message' => $e->getMessage()]
          );
        }
      }
    }
    $this->logger->warning('Could not copy first member thumbnail for @item_id. No member node candidates with thumbnails found.',
      ['@item_id' => $entity->id()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
