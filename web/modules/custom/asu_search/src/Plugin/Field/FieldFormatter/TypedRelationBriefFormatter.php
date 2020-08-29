<?php

namespace Drupal\asu_search\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'TypedRelationBriefFormatter'.
 *
 * @FieldFormatter(
 *   id = "typed_relation_brief",
 *   label = @Translation("Typed Relation Brief Formatter"),
 *   field_types = {
 *     "typed_relation"
 *   }
 * )
 */
class TypedRelationBriefFormatter extends EntityReferenceLabelFormatter
{

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {

      $rel_types = $item->getRelTypes();
      $rel_type = isset($rel_types[$item->rel_type]) ? $rel_types[$item->rel_type] : $item->rel_type;
      if (isset($elements[$delta])) {
        $re = '/(\S*) (\S*)/m';
        $str = $rel_type;
        $subst = '$1';
        $rel_type = preg_replace($re, $subst, $str);
        $elements[$delta]['#suffix'] = ' (' . $rel_type . ')';
      }
    }

    return $elements;
  }
}
