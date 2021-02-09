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
        $elements = parent::viewElements($items, $langcode);

        foreach ($items as $delta => $item) {
            \Drupal::logger('aspace_traverse')->info($item->getName());
            // $rel_types = $item->getRelTypes();
            // $rel_type = isset($rel_types[$item->rel_type]) ? $rel_types[$item->rel_type] : $item->rel_type;
            // if (isset($elements[$delta])) {
            //     $re = '/(.*) \(\S*/m';
            //     $str = $rel_type;
            //     $subst = '$1';
            //     $rel_type = preg_replace($re, $subst, $str);
            //     $elements[$delta]['#suffix'] = ' (' . $rel_type . ')';
            // }
            // if (array_key_exists($delta, $elements) && array_key_exists('#title', $elements[$delta])) {
            //     $url = \Drupal::service('facets.utility.url_generator')->getUrl(['linked_agents' => [$elements[$delta]['#title']]]);
            //     $elements[$delta]['#url'] = $url;
            // }
        }

        return $elements;
    }
}
