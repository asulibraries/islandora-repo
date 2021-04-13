<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'TypedRelationCSVFormatter'.
 *
 * @FieldFormatter(
 *   id = "typed_relation_csv",
 *   label = @Translation("Typed Relation CSV Formatter"),
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
        'agent_type' => 'person',
      ] + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
      $default_value = $this->getSetting('agent_type') ?
        $this->getSetting('agent_type') : 'person';
      $element['agent_type'] = [
        '#title' => t('URI field name'),
        '#type' => 'select',
        '#options' => [
          'person' => 'Personal Contributor',
          'corporate_body' => 'Corporate Contributor',
          'conference' => 'Event Contributor'
        ],
        '#default_value' => $default_value,
        '#required' => TRUE,
      ];
      return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function settingsSummary() {
      $summary = [];
      $summary[] = t('Contributor type: @agent_type', ['@agent_type' => $this->getSetting('agent_type')]);
      return $summary;
    }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $agent_vocab = $this->getSetting('agent_type');
    foreach ($items as $delta => $item) {
      $term = $item->entity;
      if ($term && $term->bundle() == $agent_vocab) {
        if (isset($elements[$delta])) {
          // Even if the config is to output links, this is not ever intended
          // for CSV output of these.
          if (array_key_exists("#title", $elements[$delta])) {
            $elements[$delta]["#markup"] = $elements[$delta]["#title"];
            unset($elements[$delta]["#url"]);
            unset($elements[$delta]["#options"]);
            unset($elements[$delta]["#type"]);
          }
          $uri_field_name = $this->getAuthorityLinkFieldName($term);
          $uri_field = $term->hasField($uri_field_name) ? $term->get($uri_field_name) : NULL;
          $rel_uri = (is_object($uri_field) ? $uri_field->uri : "");
          $rel_types = $item->getRelTypes();
          $rel_type = isset($rel_types[$item->rel_type]) ? $rel_types[$item->rel_type] : $item->rel_type;
          $re = '/(.*) \(\S*/m';
          $str = $rel_type;
          $subst = '$1';
          $rel_type = preg_replace($re, $subst, $str);
          $string_value = (array_key_exists("#plain_text", $elements[$delta]) ?
            $elements[$delta]['#plain_text'] : $elements[$delta]["#title"]);
          $elements[$delta]['#markup'] = $string_value . "|" . $rel_uri . '|' .
            $rel_type;
          if (array_key_exists("#plain_text", $elements[$delta])) {
            unset($elements[$delta]["#plain_text"]);
          }
        }
      }
      else {
        unset($elements[$delta]);
      }
    }

    return $elements;
  }

  private function getAuthorityLinkFieldName($term) {
    // Inspect all fields on this term until one that contains "authority_link"
    // is found.
    $fields = $term->getFields();
    return 'field_authority_link';
  }
}
