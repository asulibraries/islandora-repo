<?php

namespace Drupal\self_deposit\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\controlled_access_terms\Plugin\Field\FieldWidget\TypedRelationWidget;

/**
 * Plugin implementation of the typed note widget.
 *
 * @FieldWidget(
 *   id = "typed_relation_auto",
 *   label = @Translation("Typed Relation Auto Widget"),
 *   field_types = {
 *     "typed_relation"
 *   }
 * )
 */
class TypedRelationAutoWidget extends TypedRelationWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#autocreate'] = [
      'bundle' => 'person',
    ];

    $item =& $items[$delta];
    $settings = $item->getFieldDefinition()->getSettings();
    $settings['handler_settings']['auto_create'] = 1;
    $settings['handler_settings']['auto_create_bundle'] = 'person';
    $item = $item->getFieldDefinition()->setSettings($settings);

    $items[$delta] = $item;

    $widget = parent::formElement($items, $delta, $element, $form, $form_state);
    return $widget;
  }

}
