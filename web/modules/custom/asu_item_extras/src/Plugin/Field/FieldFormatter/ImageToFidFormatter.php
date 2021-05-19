<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

//use Drupal\Core\Field\FormatterBase;
//use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
//use Drupal\islandora\Plugin\Field\FieldFormatter\IslandoraFileMediaFormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
//use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
//use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\islandora\MediaSource\MediaSourceService;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;

/**
 * Plugin implementation of the 'ImageToFidFormatter'.
 *
 * @FieldFormatter(
 *   id = "image_to_fid_formatter",
 *   label = @Translation("Image to fid"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageToFidFormatter extends ImageFormatterBase {

//  /**
//   * Service for business logic.
//   *
//   * @var \Drupal\islandora\MediaSource\MediaSourceService
//   */
//  protected $mediaSourceService;
//
//  /**
//   * MediaUseToFidFormatter constructor.
//   *
//   * @param \Drupal\islandora\MediaSource\MediaSourceService $service
//   *   Service for business logic.
//   */
//  public function __construct(
//      array $configuration,
//      $plugin_id,
//      $plugin_definition,
//      MediaSourceService $service
//  ) {
//    parent::__construct($configuration, $plugin_id, $plugin_definition);
//    $this->mediaSourceService = $service;
//  }
//
//  /**
//   * Controller's create method for dependecy injection.
//   *
//   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
//   *   The App Container.
//   *
//   * @return \Drupal\asu_item_extras\Plugin\Field\FieldFormatter\MediaUseToFidFormatter
//   *   Controller instance.
//   */
//  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
//    return new static(
//      $configuration,
//      $plugin_id,
//      $plugin_definition,
//      $container->get('islandora.media_source_service')
//    );
//  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = []; //parent::viewElements($items, $langcode);
    $mediaSourceService = \Drupal::service('islandora.media_source_service');
    $formattable = ['image', 'document', 'audio', 'video', 'file'];
    // Simply return the media file's $fid (entity->id()) value. The view
    // display will use this value to make the link to Replace the file.
    foreach ($items as $delta => $item) {
      // From the media object, based on the fields used by the type of media,
      // get the file object and return that id value.
      $entity_type = $item->entity->getEntityTypeId();

      if (!(array_search($entity_type, $formattable) === FALSE)) {
        $media_source_field = $mediaSourceService->getSourceFieldName($entity_type);
$f = ($item->entity->hasField($media_source_field)) ? $item->entity->get($media_source_field) : NULL;

        $v = ($item->entity->hasField($media_source_field)) ? $item->entity->get($media_source_field)->referencedEntities()[0] : '';
        \Drupal::logger('asu_item_extras')->info('$media_source_field =  <pre>' . print_r($media_source_field, true));
        \Drupal::logger('asu_item_extras')->info('value =  <pre>' . print_r($v, true));
        $elements[$delta]['#plain_text'] = $item->entity->id();
      } else {
        $elements[$delta]['#plain_text'] = $item->entity->id();
      }
    }

    return $elements;
  }

}
//
//TypeError: Argument 3 passed to Drupal\\Core\\Field\\FormatterBase::__construct() 
// must implement interface Drupal\\Core\\Field\\FieldDefinitionInterface, 
// array given, 
//
//called in /var/www/html/drupal/web/modules/custom/asu_item_extras/src/Plugin/Field/FieldFormatter/MediaUseToFidFormatter.php