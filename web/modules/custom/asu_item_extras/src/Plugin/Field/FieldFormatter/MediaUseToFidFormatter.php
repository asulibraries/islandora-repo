<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'MediaUseToFidFormatter'.
 *
 * @FieldFormatter(
 *   id = "media_use_to_fid_formatter",
 *   label = @Translation("Media use to fid"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaUseToFidFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    // Simply return the media file's $fid (entity->id()) value. The view
    // display will use this value to make the link to Replace the file.
    foreach ($items as $delta => $item) {
      $elements[$delta]['#plain_text'] = $item->entity->id();
    }

    return $elements;
  }

}
