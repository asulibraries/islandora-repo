<?php

namespace Drupal\archivesspace_extensions\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'AspaceTraverseFormatter'.
 *
 * @FieldFormatter(
 *   id = "aspace_traverse",
 *   label = @Translation("Archivesspace Object Traversal"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AspaceTraverseFormatter extends EntityReferenceLabelFormatter
{

    /**
     * {@inheritdoc}
     */
    public function viewElements(FieldItemListInterface $items, $langcode)
    {
        $elements_to_add = [];
        $elements = parent::viewElements($items, $langcode);
        foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
            // field source is limited to 1 value
            // $entity = $items[0]->entity;
            $id = $entity->id();
            \Drupal::logger('aspacetravers')->info("item is " . $this->getTitle($entity));
            $elements_to_add[] = [
                // '#url' => $entity->toUrl(),
                // '#title' => $this->getTitle($entity),
                // '#type' => 'link'
                '#markup' => '<span>' . $this->getTitle($entity) . '</span>'
            ];

            $elements_to_add = $this->traverseAspaceTree($entity, $elements_to_add);
        }

        $elements = array_merge($elements, $elements_to_add);

        return $elements_to_add;
    }

    private function traverseAspaceTree($entity, $elements_to_add) {
        $member_of = $entity->get('field_member_of')->referencedEntities()[0];
        $resource = $entity->get('field_as_resource')->referencedEntities()[0];
        $elements_to_add[] = [
            '#url' => $member_of->toUrl(),
            '#title' => $this->getTitle($member_of),
            '#type' => 'link'
        ];
        \Drupal::logger('aspacetravers')->info("add " . $this->getTitle($member_of));
        if ($resource == $member_of) {
            return $elements_to_add;
        } else {
            $this->traverseAspaceTree($member_of, $elements_to_add);
        }
    }

    private function getTitle($entity) {
        if ($entity->hasField('field_as_title')) {
            return $entity->get('field_as_title')->value;
        }
        return $entity->get('title')->value;
    }
}
