<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'IdentifierWithTypeCSVFormatter'.
 *
 * @FieldFormatter(
 *   id = "identifier_with_type_csv",
 *   label = @Translation("Identifier With Type CSV Formatter"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class IdentifierWithTypeCSVFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($items as $delta => $item) {
      $paragraph = $item->entity;
      $identifier_text = $paragraph->field_identifier_value->value . '';
      $identifier_predicate = $paragraph->field_identifier_type->field_identifier_predicate->value . '';
      $identifier_type_term = $paragraph->field_identifier_type->referencedEntities();
      $identifier_predicate = (is_array($identifier_type_term) && 
        array_key_exists(0, $identifier_type_term) &&
        $identifier_type_term[0]->hasField('field_identifier_predicate')) ?
        $identifier_type_term[0]->get('field_identifier_predicate')->value : NULL;
      $nm = $identifier_text .
        ($identifier_predicate != NULL ? "|" . $identifier_predicate : "");
      $elements[$delta]['#plain_text'] = $nm;
    }
    return $elements;
  }

}
