<?php

namespace Drupal\asu_search\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'TaxonomyWithURIFormatter'.
 *
 * @FieldFormatter(
 *   id = "taxonomy_with_uri_formatter",
 *   label = @Translation("Taxonomy with URI Formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TaxonomyWithURIFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Should return the taxonommy term AND uri together with delimiter "|"
    // like: "Weight loss|http://id.loc.gov/authorities/subjects/sh85112135".
    $elements = parent::viewElements($items, $langcode);
    foreach ($items as $delta => $item) {
      $taxo_term = $item->entity;
      $authority_link = $taxo_term->get("field_authority_link");
      if (is_object($authority_link)) {
        $authority_field_uri = $authority_link->uri;
        $term_name = $taxo_term->getName();
        $elements[$delta]['#plain_text'] = $term_name . 
          (($authority_field_uri) ?  "|" . $authority_field_uri : "");
      }
    }
    return $elements;
  }

}
