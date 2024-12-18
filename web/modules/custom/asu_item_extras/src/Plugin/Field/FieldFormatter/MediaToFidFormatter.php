<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

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
class MediaToFidFormatter extends FileFormatterBase {

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
      if ($item->entity) {
        $entity_type = $item->entity->getEntityTypeId();
        if (!(array_search($entity_type, $formattable) === FALSE)) {
          $elements[$delta]['#plain_text'] = $item->getValue()['target_id'];
        }
      }
    }

    return $elements;
  }

}
