<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Traverses up the islandora tree and gets all collection parents.
 *
 * @SearchApiProcessor(
 *   id = "collection_parents",
 *   label = @Translation("Collection Parents"),
 *   description = @Translation("Traverses up the islandora tree and gets all collection parents."),
 *   stages = {
 *     "preprocess_index" = 5
 *   },
 * )
 */
class CollectionParents extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {

      $node = $item->getOriginalObject()->getValue();

      if ($node && $node->hasField('field_member_of') && !$node->field_member_of->isEmpty()) {
        $collection_parents = [];

        $ancestors = $item->getField('field_ancestors')->getValues();
        if (count($ancestors) > 0) {
          $entities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ancestors);
          foreach ($entities as $par) {
            if ($par->bundle() == 'collection') {
              $collection_parents[] = $par->label();
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
                }
              }
            }
          }
        }
        $field = $item->getField('field_member_of');
        $field->setValues($collection_parents);
      }
    }
  }

}
