<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
// If this needs a Settings form.
// use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TaxonomyWithURIFormatter'.
 *
 * @FieldFormatter(
 *   id = "taxonomy_with_uri_formatter",
 *   label = @Translation("Taxonomy with URI Formatter - CSV export"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TaxonomyWithURIFormatter extends EntityReferenceLabelFormatter {

  // If this needs a Settings form.
  //  /**
  //   * {@inheritdoc}
  //   */
  //  public static function defaultSettings() {
  //    return [
  //      'uri_field_name' => 'field_authority_link',
  //    ] + parent::defaultSettings();
  //  }
  //
  //  /**
  //   * {@inheritdoc}
  //   */
  //  public function settingsForm(array $form, FormStateInterface $form_state) {
  //    $default_value = $this->getSetting('uri_field_name') ? 
  //      $this->getSetting('uri_field_name') : 'field_authority_link'; 
  //    $element['uri_field_name'] = [
  //      '#title' => t('URI field name'),
  //      '#type' => 'select',
  //      '#options' => [
  //        "field_authority_link" => "Authority Link",
  //        "field_external_authority_link" => "External Authority Link"
  //      ],
  //      '#default_value' => $default_value,
  //      '#required' => TRUE,
  //    ];
  //    return $element;
  //  }
  //
  //  /**
  //   * {@inheritdoc}
  //   */
  //  public function settingsSummary() {
  //    $summary = [];
  //    $summary[] = t('URI field name: @uri_field_name', ['@uri_field_name' => $this->getSetting('uri_field_name')]);
  //    return $summary;
  //  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Should return the taxonommy term AND uri together with delimiter "|"
    // like: "Weight loss|http://id.loc.gov/authorities/subjects/sh85112135".
    // If this needs a Settings form.
    // $uri_field_name = $this->getSetting('uri_field_name');
    $uri_field_name = 'field_authority_link';
    $elements = parent::viewElements($items, $langcode);
    foreach ($items as $delta => $item) {
      $taxo_term = $item->entity;
      $authority_link = ($taxo_term->hasField($uri_field_name) ? $taxo_term->get($uri_field_name) : NULL);
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
