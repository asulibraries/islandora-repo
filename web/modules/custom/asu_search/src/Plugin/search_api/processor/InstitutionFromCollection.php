<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\node\NodeInterface;
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
      $parent = $this->getParentCollection($node, 3, 0);
      if ($parent && $parent->hasField('field_collaborating_institutions') && !$parent->get('field_collaborating_institutions')->isEmpty()) {
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

  /**
   * Function to get parent of item that is a collection - recursive.
   *
   * @param NodeInterface $node
   *   The node object for which to find the parent collection.
   * @param int $max_depth
   *   Search depth.
   * @param int $depth
   *   Current iteration's depth.
   *
   * @return NodeInterface
   *   The parent collection.
   */
  private function getParentCollection(NodeInterface $node, $max_depth = 3, $depth = 0) {
    if ($depth == $max_depth) {
      return NULL;
    }
    $parent_object = $node->field_member_of->entity;
    // If the parent object is just an asu_repository_item, then the parent
    // is a Complex Object.
    if ($parent_object && $parent_object->bundle() == "asu_repository_item") {
      $depth++;
      $parent_collection = $this->getParentCollection($parent_object, $max_depth, $depth);
    }
    else {
      $parent_collection = $parent_object;
    }
    return $parent_collection;
  }


}
