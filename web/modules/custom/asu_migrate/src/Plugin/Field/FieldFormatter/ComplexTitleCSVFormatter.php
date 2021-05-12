<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'ComplexTitleCSVFormatter'.
 *
 * @FieldFormatter(
 *   id = "complex_title_csv",
 *   label = @Translation("Complex Title CSV Formatter"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ComplexTitleCSVFormatter  extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($items as $delta => $item) {
      $nonsort = $item->entity->field_nonsort->value . '';
      $main = $item->entity->field_main_title->value;
      $sub = $item->entity->field_subtitle->value . '';
      $nm = (($nonsort) ? $nonsort . ' ' : '').
        ($main != NULL ? $main : "[untitled]") .
        (($sub) ? ':' . $sub : '');
      \Drupal::logger('title formatter')->info($nm);
      $elements[$delta]['#text'] = $nm;
      $elements[$delta]['#type'] = 'processed_text';
    }

    return $elements;
  }

}
