<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the Copy Button formatter.
 *
 * @FieldFormatter(
 *   id = "copy_button",
 *   label = @Translation("Copy Button"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class CopyButton extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $item_text = $item->value;
      $elements[$delta] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['permalink_button']],
        'link' => [
          // Try replacing this with a link render array.
          '#markup' => "<a class='btn btn-maroon btn-md copy_permalink_link' title='$item_text'><i class='far fa-copy fa-lg copy_permalink_link' title='$item_text'></i>&nbsp;Copy permalink</a>",
        ],
        '#attached' => [
          'library' => ['asu_item_extras/interact'],
        ],
      ];
    }
    return $elements;
  }

}
