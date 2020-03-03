<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds the item's idnetifier separately by type.
 *
 * @SearchApiProcessor(
 *   id = "identifier_by_type",
 *   label = @Translation("ISBN Identifier"),
 *   description = @Translation("adds the item's idnetifier separately by type"),
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
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL)
  {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('ISBN Identifier'),
        'description' => $this->t('A ISBN-type identifier'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['asu_isbn'] = new ProcessorProperty($definition);
      $properties['asu_local'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item)
  {
    $node = $item->getOriginalObject()->getValue();
    if ($node->hasField('field_typed_identifier')){
      $vals = $node->field_typed_identifier->getValue();
      foreach ($vals as $element) {
        $fields = $item->getFields(FALSE);
        $paragraph = Paragraph::load($element['target_id']);
        $ident_type = $paragraph->get('field_identifier_type')->first()->entity->getName();
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($fields, NULL, 'asu_' . strtolower($ident_type));
        foreach ($fields as $field) {
          $field->addValue($paragraph->get('field_identifier_value')->value);
        }
      }
    }
  }
}
