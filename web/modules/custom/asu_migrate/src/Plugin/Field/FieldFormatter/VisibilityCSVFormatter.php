<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\IntegerFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'VisibilityCSVFormatter'.
 *
 * @FieldFormatter(
 *   id = "visibility_csv",
 *   label = @Translation("Visibility CSV Formatter"),
 *   field_types = {
 *     "text"
 *   }
 * )
 */
class VisibilityCSVFormatter extends IntegerFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $asu_utils = \Drupal::service('asu_utils');
    // This takes each node and converts the moderation_state of it into the
    // Visibility value.
    //  - Private: draft
    //  - Public: published
    foreach ($items as $delta => $item) {
      $item_entity_id = $item->value;
      $item_entity = @\Drupal::entityTypeManager()->getStorage('node')->load($item_entity_id);
      $is_published = $asu_utils->isNodePublished($item_entity);
      $elements[$delta]['#markup'] = ($is_published ? "Public" : "Private");
    }

    return $elements;
  }

}
