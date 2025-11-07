<?php

namespace Drupal\asu_webform_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Provides a 'Source entity teaser (recent_item_teaser)' block.
 *
 * Reads source_entity_type and source_entity_id from the query string,
 * loads the entity and renders it with the 'recent_item_teaser' view mode.
 *
 * @Block(
 *   id = "feedback_item_preview",
 *   admin_label = @Translation("Feedback Form Item Preview"),
 *   category = @Translation("ASU")
 * )
 */
class FeedbackItemPreview extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a SourceEntityTeaserBlock.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $request = $this->requestStack->getCurrentRequest();
    $type = $request->query->get('source_entity_type');
    $id = $request->query->get('source_entity_id');

    // Always vary the block by those query args.
    $query_cache_contexts = [
      'url.query_args:source_entity_type',
      'url.query_args:source_entity_id',
    ];

    // Validate presence of both args.
    if (empty($type) || empty($id) || !$this->entityTypeManager->hasDefinition($type)) {
      return [];
    }

    // Load entity.
    $storage = $this->entityTypeManager->getStorage($type);
    $entity = $storage->load($id);

    if (!$entity) {
      return [];
    }

    return $this->entityTypeManager->getViewBuilder($type)->view($entity, 'recent_item_teaser_prism');
  }

}
