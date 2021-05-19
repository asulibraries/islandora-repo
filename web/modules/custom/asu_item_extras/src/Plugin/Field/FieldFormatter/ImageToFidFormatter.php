<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
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

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // Simply return the media file's $fid (entity->id()) value. The view
    // display will use this value to make the link to Replace the file.
    foreach ($items as $delta => $item) {
      // From the media object, based on the fields used by the type of media,
      // get the file object and return that id value.
      $entity_type = $item->entity->getEntityTypeId();
      if ($entity_type == 'file') {
        $elements[$delta]['#plain_text'] = $item->getValue()['target_id'];
      }
    }

    return $elements;
  }

}
