<?php

namespace Drupal\asu_admin_toolbox\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Reindex an item in Solr.
 *
 * @Action(
 *   id = "solr_reindex_item",
 *   label = @Translation("Solr Reindex Item"),
 *   type = "node"
 * )
 */
class SolrReindexAction extends ActionBase implements ContainerFactoryPluginInterface
{

  /**
   * Logger.
   *
   * @var Psr\Log\LoggerInterface
   */
  protected $logger;

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
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.islandora')
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
      \Drupal::logger('solr reindex item action')->info("about to reindex item");
      search_api_entity_update($entity);
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