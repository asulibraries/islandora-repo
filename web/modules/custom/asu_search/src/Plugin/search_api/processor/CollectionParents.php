<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Traverses up the islandora tree and gets all collection parents.
 *
 * @SearchApiProcessor(
 *   id = "collection_parents",
 *   label = @Translation("Collection Parents"),
 *   description = @Translation("Traverses up the islandora tree and gets all collection parents."),
 *   stages = {
 *     "preprocess_index" = 5,
 *     "add_properties" = 0,
 *   },
 * )
 */
class CollectionParents extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Parent Published'),
        'description' => $this->t('Whether or not any of the parents are unpublished'),
        'type' => 'boolean',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['parent_published'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {

      $node = $item->getOriginalObject()->getValue();
      $parent_published = TRUE;

      if ($node && $node->hasField('field_member_of') && !$node->field_member_of->isEmpty()) {
        $collection_parents = [];

        $ancestors = $item->getField('field_ancestors')->getValues();
        if (count($ancestors) > 0) {
          $entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ancestors);
          foreach ($entities as $par) {
            if ($par->bundle() == 'collection') {
              $collection_parents[] = $par->label();
              $par_status = $par->status->getString();
              if ($par->status->getString() != "1") {
                $parent_published = FALSE;
              }
            }
          }
        }

        if ($node->hasField('field_additional_memberships')) {
          $additional_memberships = $item->getField('field_additional_memberships');
          if ($additional_memberships) {
            $additional_memberships = $additional_memberships->getValues();
            if (count($additional_memberships) > 0) {
              $entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($additional_memberships);
              foreach ($entities as $par) {
                if ($par->bundle() == 'collection') {
                  $collection_parents[] = $par->label();
                  if ($par->status->getValue() != "1") {
                    $parent_published = FALSE;
                  }
                }
              }
            }
          }
        }
        $mem_field = $item->getField('field_member_of');
        $mem_field->setValues($collection_parents);
        $fields = $item->getFields(FALSE);
        $fields = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'parent_published');
        foreach ($fields as $field) {
          $field->addValue($parent_published);
        }
      }
    }
  }

}
