<?php

namespace Drupal\asu_admin_toolbox\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\asu_islandora_utils\AsuUtils;

/**
 * Reindex an item and children in Solr.
 *
 * @Action(
 *   id = "solr_reindex_children",
 *   label = @Translation("Solr Reindex Item and Children"),
 *   type = "node"
 * )
 */
class SolrReindexChildAction extends ActionBase implements ContainerFactoryPluginInterface
{

  /**
   * The entityTypeManager definition.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The AsuUtils definition.
   *
   * @var \Drupal\asu_islandora_utils\AsuUtils
   */
  protected $asuUtils;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager definition.
   * @param $ASUUtils
   *   The ASU Utils service.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      EntityTypeManager $entityTypeManager,
      AsuUtils $ASUUtils
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->asuUtils = $ASUUtils;
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
      $container->get('asu_utils')
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

    $content_type = $entity->bundle();
    if ($entity->getEntityTypeId() == 'node' && $content_type == 'asu_repository_item') {
      search_api_entity_update($entity);
      if ($entity->hasField('field_model') && !$entity->get('field_model')->isEmpty()) {
        $model_term = $entity->get('field_model')->referencedEntities()[0];
        $model = $model_term->getName();
        if ($model == 'Complex Object') {
          $child_nids = $this->asuUtils->getCollectionChildren($entity, FALSE);
          foreach ($child_nids as $nid) {
            $node = $this->entityTypeManager->getStorage('node')->load($nid);
            search_api_entity_update($node);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    return $object->access('edit', $account, $return_as_object);
  }

}