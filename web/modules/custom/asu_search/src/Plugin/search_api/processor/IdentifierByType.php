<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds the item's identifier separately by type.
 *
 * @SearchApiProcessor(
 *   id = "identifier_by_type",
 *   label = @Translation("Typed Identifier"),
 *   description = @Translation("adds the item's identifier separately by type"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class IdentifierByType extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Identifier'),
        'description' => $this->t('A typed identifier'),
        'type' => 'string',
        'is_list' => TRUE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['asu_isbn'] = new ProcessorProperty($definition);
      $properties['asu_local'] = new ProcessorProperty($definition);
      $properties['asu_issn'] = new ProcessorProperty($definition);
      $properties['asu_doi'] = new ProcessorProperty($definition);
      // Add as many identifier types you want - the code for the type is used.
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    if ($node->hasField('field_typed_identifier')) {
      $vals = $node->field_typed_identifier->getValue();
      foreach ($vals as $element) {
        $fields = $item->getFields(FALSE);
        $paragraph = Paragraph::load($element['target_id']);
        $ix = $paragraph->get('field_identifier_type');
        if (isset($ix) && isset($ix->first()->entity)) {
          if ($paragraph->get('field_identifier_type')->first()->entity->hasField('field_identifier_predicate')) {
            $ident_type = $paragraph->get('field_identifier_type')->first()->entity->get('field_identifier_predicate')->getValue()[0]['value'];
          }
          else {
            $ident_type = "identifier";
          }
          $fields = $this->getFieldsHelper()
            ->filterForPropertyPath($fields, NULL, 'asu_' . strtolower($ident_type));
          foreach ($fields as $field) {
            $field->addValue($paragraph->get('field_identifier_value')->value);
          }
        }
      }
    }
  }

}
