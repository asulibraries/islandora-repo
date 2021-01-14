<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Adds the original file count for a node.
 *
 * @SearchApiProcessor(
 *   id = "original_file_count",
 *   label = @Translation("Original File Count"),
 *   description = @Translation("adds the original file count"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class OriginalFileCount extends ProcessorPluginBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->entityTypeManager = $container->get('entity_type.manager');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Original File Count'),
        'description' => $this->t('A string that combines parts of a title'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['original_file_count'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    $original_file_tid = key(\Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => "Original File"]));
    $files = 0;
    $mids = \Drupal::entityTypeManager()->getStorage('media')->getQuery()
      ->condition('field_media_of', $node->id())
      ->condition('field_media_use', $original_file_tid)
      ->execute();
    foreach ($mids as $mid) {
      $media = \Drupal::entityTypeManager()->getStorage('media')->load($mid);
      $files += (is_object($media) ? 1 : 0);
    }

    $fields = $item->getFields(FALSE);
    $fields = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'original_file_count');
    foreach ($fields as $field) {
      $field->addValue($files);
    }
  }

}
