<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'AutoLinkTextFormatter'.
 *
 * @FieldFormatter(
 *   id = "auto_link_text",
 *   label = @Translation("Auto_link text"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class AutoLinkTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $item_text = $item->get('value')->getValue();
      $elements[$delta]['#markup'] = $this->auto_link_text($item_text);
    }

    return $elements;
  }


  private function auto_link_text($string) {
    $url = '@(http(s)?)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
    $string = preg_replace($url, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $string);

    return $string;
  }
}
