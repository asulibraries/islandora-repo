<?php

namespace Drupal\archivesspace_extensions\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Save queue item in a node.
 *
 * To process the queue items whenever Cron is run,
 * we need a QueueWorker plugin with an annotation witch defines
 * to witch queue it applied.
 *
 * @QueueWorker(
 *   id = "as_digital_object_queue",
 *   title = @Translation("Trigger digital object update action."),
 *   cron = {"time" = 60}
 * )
 */
class DigitalObjectUpdate extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Contructs a Resource Description File Worker.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger for messages.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String translation for messages.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Storage to load nodes.
   */
  public function __construct(
     LoggerInterface $logger,
     TranslationInterface $stringTranslation,
     EntityTypeManagerInterface $entity_manager,
   ) {
    $this->entityTypeManager = $entity_manager;
    $this->logger = $logger;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('logger.channel.archivesspace'),
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    $action = $this->entityTypeManager
      ->getStorage('action')
      ->load('create_aspace_dig_obj');
    // @todo consider how we can consolidate updates
    // (e.g. create node then update with handle).
    $node = $this->entityTypeManager->getStorage('node')->load($item['nid']);
    \Drupal::logger('archivesspace')->info("Updating the ArchivesSpace digital object record for '@node' ('@nid').", [
      '@node' => $node->label(),
      '@nid' => $node->id(),
    ]);
    $action->execute([$node]);
  }

}
