<?php

namespace Drupal\asu_admin_toolbox\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reindex an item in Solr.
 *
 * @Action(
 *   id = "solr_reindex_item",
 *   label = @Translation("Solr Reindex Item"),
 *   type = "node"
 * )
 */
class SolrReindexAction extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
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
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('edit', $account, $return_as_object);
  }

}
