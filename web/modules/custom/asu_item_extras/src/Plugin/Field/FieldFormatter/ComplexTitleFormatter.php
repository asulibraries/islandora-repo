<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'ComplexTitleFormatter'.
 *
 * @FieldFormatter(
 *   id = "complex_title_formatter",
 *   label = @Translation("Complex Title Formatter"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ComplexTitleFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      $nonsort = $item->entity->field_nonsort->value;
      $main = $item->entity->field_main_title->value;
      $sub = $item->entity->field_subtitle->value;
      $nm = ($nonsort != NULL ? $nonsort . " " : "") .
        ($main != NULL ? $main : "[untitled]") .
        ($sub != NULL ? ": " . $sub : "");
      $elements[$delta]['#plain_text'] = $nm;
    }

    return $elements;
  }

}
