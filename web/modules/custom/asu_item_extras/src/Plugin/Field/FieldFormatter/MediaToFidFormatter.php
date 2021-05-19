<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\islandora\MediaSource\MediaSourceService;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'MediaToFidFormatter'.
 *
 * @FieldFormatter(
 *   id = "media_to_fid_formatter",
 *   label = @Translation("Media to fid"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class MediaToFidFormatter extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Service for business logic.
   *
   * @var \Drupal\islandora\MediaSource\MediaSourceService
   */
  protected $mediaSourceService;

  /**
   * MediaToFidFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\islandora\MediaSource\MediaSourceService $service
   *   Service for business logic.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MediaSourceService $service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $service);
    $this->mediaSourceService = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('islandora.media_source_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $formattable = ['document', 'audio', 'video', 'file'];
    // Simply return the media file's $fid (entity->id()) value. The view
    // display will use this value to make the link to Replace the file.
    foreach ($items as $delta => $item) {
      // From the media object, based on the fields used by the type of media,
      // get the file object and return that id value.
      $entity_type = $item->entity->getEntityTypeId();
      if (!(array_search($entity_type, $formattable) === FALSE)) {
        $media_source_field = $this->mediaSourceService->getSourceFieldName($entity_type);
        $elements[$delta]['#plain_text'] = ($item->entity->hasField($media_source_field)) ? $item->entity->get($media_source_field)->entity->id() : '';
      }
    }

    return $elements;
  }

}
