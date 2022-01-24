<?php

namespace Drupal\asu_default_fields\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create a handle for an object.
 *
 * @Action(
 *   id = "mint_handle",
 *   label = @Translation("Mint a handle"),
 *   type = "node"
 * )
 */
class MintHandle extends ActionBase implements ContainerFactoryPluginInterface {

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

    $config = \Drupal::config('persistent_identifiers.settings');
    $content_type = $entity->bundle();
    $allowed_types = $config->get('persistent_identifiers_bundles');
    if ($entity->getEntityTypeId() == 'node' && in_array($content_type, $allowed_types, TRUE)) {
      $minter = \Drupal::service($config->get('persistent_identifiers_minter'));
      \Drupal::logger('mint handle action')->info("about to call the minter");
      $pid = $minter->mint($entity, NULL);
      if (is_null($pid)) {
        \Drupal::logger('persistent_identifiers')->warning(t("Persistent identifier not created for node @nid.", ['@nid' => $entity->id()]));
        \Drupal::messenger()->addWarning(t("Problem creating persistent identifier for this node. Details are available in the Drupal system log."));
        return;
      }
      $persister = \Drupal::service($config->get('persistent_identifiers_persister'));
      if ($persister->persist($entity, $pid)) {
        \Drupal::logger('persistent_identifiers')->info(t("Persistent identifier %pid created for node @nid.", ['%pid' => $pid, '@nid' => $entity->id()]));
        \Drupal::messenger()->addStatus(t("Persistent identifier %pid created for this node.", ['%pid' => $pid]));
      }
      else {
        \Drupal::logger('persistent_identifiers')->warning(t("Persistent identifier not created for node @nid.", ['@nid' => $entity->id()]));
        \Drupal::messenger()->addWarning(t("Problem creating persistent identifier for this node. Details are available in the Drupal system log."));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('edit', $account, $return_as_object);
  }

}
