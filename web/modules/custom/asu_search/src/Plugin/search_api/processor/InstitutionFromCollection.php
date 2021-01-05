<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Gets the institution from the parent collection (field_member_of).
 *
 * @SearchApiProcessor(
 *   id = "institution_from_collection",
 *   label = @Translation("Institution From Collection"),
 *   description = @Translation("Gets the institution from the collection"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class InstitutionFromCollection extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Institution From Collection'),
        'description' => $this->t('Gets the institution from the parent collection (field_member_of)'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['institution_from_collection'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    if ($node->hasField('field_member_of') && $node->field_member_of->entity) {
      $parent = $node->field_member_of->entity;
      if ($parent->hasField('field_collaborating_institutions') && !$parent->get('field_collaborating_institutions')->isEmpty()) {
        $insts = $parent->get('field_collaborating_institutions')->referencedEntities();
        foreach ($insts as $element) {
          $fields = $item->getFields(FALSE);
          $inst = $element;
          $inst_name = $inst->getName();
          $fields = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'institution_from_collection');
          foreach ($fields as $field) {
            $field->addValue($inst_name);
          }
        }
      }

    }
  }

}
