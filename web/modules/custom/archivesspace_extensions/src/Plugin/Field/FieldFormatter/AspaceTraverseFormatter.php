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
        // field source is limited to 1 value
        $entity = $items[0]->entity;
        $title = $entity->get('field_as_title')->value;
        $id = $entity->id();
        $member_of = $entity->get('field_member_of')->referencedEntities()[0];
        $resource = $entity->get('field_as_resource')->referencedEntities()[0];
        if ($resource == $member_of) {
            $elements_to_add[] = [
                '#url' => $member_of->toUrl(),
                '#title' => $member_of->get('title')->value,
                '#type' => 'link'
            ];
        }

        $elements = array_merge($elements, $elements_to_add);

        return $elements;
    }

    private function traverseAspaceTree($node) {

    }
}
