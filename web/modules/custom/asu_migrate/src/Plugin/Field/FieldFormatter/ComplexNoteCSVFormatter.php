<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'ComplexNoteCSVFormatter'.
 *
 * @FieldFormatter(
 *   id = "complex_note_csv",
 *   label = @Translation("Complex Note CSV Formatter"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class ComplexNoteCSVFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($items as $delta => $item) {
      $paragraph = $item->entity;
      $note_text = $paragraph->field_note_text->value . '';
      $note_type_obj = $paragraph->field_note_type->referencedEntities();
      $note_type_term = (is_array($note_type_obj) && array_key_exists(0, $note_type_obj)) ?
        $note_type_obj[0]->getName() : NULL;
      $nm = $note_text .
        ($note_type_term != NULL ? "|" . $note_type_term : "");
      $elements[$delta]['#plain_text'] = $nm;
    }
    return $elements;
  }

}
