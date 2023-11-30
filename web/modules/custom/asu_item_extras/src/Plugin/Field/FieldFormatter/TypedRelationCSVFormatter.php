<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TypedRelationCSVFormatter'.
 *
 * @FieldFormatter(
 *   id = "workbench_typed_relation",
 *   label = @Translation("Workbench Typed Relation Formatter"),
 *   field_types = {
 *     "typed_relation"
 *   }
 * )
 */
class TypedRelationCSVFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'subfield_delimiter' => ':',
      'term_identifier' => 'label',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['subfield_delimiter'] = [
      '#title' => $this->t('Component Delimiter'),
      '#type' => 'textfield',
      '#size' => 4,
      '#default_value' => $this->getSetting('subfield_delimiter') ? $this->getSetting('subfield_delimiter') : ':',
      '#required' => TRUE,
    ];
    $element['term_identifier'] = [
      '#title' => $this->t('Term Identifier'),
      '#type' => 'select',
      '#options' => [
        'label' => $this->t('Term Label'),
        'tid' => $this->t('Term Identifier'),
        'uri' => $this->t('Term URI'),
      ],
      '#default_value' => (!empty($this->getSetting('term_identifier'))) ? $this->getSetting('term_identifier') : 'label',
      '#required' => TRUE,
      '#description' => $this->t('Determines which aspect of the term will be used. Terms missing a URI will fall-back to term identifier.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Sub-Field delimiter: @subfield_delimiter', [
	    '@subfield_delimiter' => $this->getSetting('subfield_delimiter')
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $subfield_delimiter = $this->getSetting('subfield_delimiter');
    foreach ($items as $delta => $item) {
      $term = $item->entity;
      if (!array_key_exists($delta, $elements)) {
        \Drupal::logger('asu_item_extras')->warning('Referenced term no longer exists: ' . $item->getString());
        continue;
      }
      // Even if the config is to output links, this is not ever intended
      // for CSV output of these.
      if (array_key_exists("#title", $elements[$delta])) {
        $elements[$delta]["#markup"] = $elements[$delta]["#title"];
        unset($elements[$delta]["#url"]);
        unset($elements[$delta]["#options"]);
        unset($elements[$delta]["#type"]);
      }
      // Get URI.
      $uri_field_name = $this->getAuthorityLinkFieldName($term);
      $uri_field = $term->hasField($uri_field_name) ? $term->get($uri_field_name) : NULL;
      $rel_uri = (is_object($uri_field) ? $uri_field->uri : "");

      $string_value = (array_key_exists("#plain_text", $elements[$delta]) ?
      $elements[$delta]['#plain_text'] : $elements[$delta]["#title"]);

      switch ($this->getSetting('term_identifier')) {
        // Label case is the default; no need to set here.
        case 'uri':
          if (!empty($rel_uri)) {
            $string_value = $rel_uri;
            break;
          }
          // Missing URIs pass through to tid.
        case 'tid':
          $string_value = strval($term->id());
          break;
      }
      $elements[$delta]['#markup'] = implode($subfield_delimiter, [$item->rel_type, $term->bundle(), $string_value]);
      if (array_key_exists("#plain_text", $elements[$delta])) {
        unset($elements[$delta]["#plain_text"]);
      }
    }
    return $elements;
  }

  /**
   * Return the first known uri field.
   */
  private function getAuthorityLinkFieldName($term) {
    foreach (['field_authority_link', 'field_external_uri'] as $field) {
      if ($term->hasField($field)) {
        return $field;
      }
    }
    return '';
  }

}
