<?php

namespace Drupal\asu_migrate\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Plugin implementation of the 'TypedRelationCSVFormatter'.
 *
 * @FieldFormatter(
 *   id = "parent_type_csv",
 *   label = @Translation("Parent Entity Type CSV Formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ParentTypeCSVFormatter extends EntityReferenceLabelFormatter {

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings() {
      return [
        'parent_type' => 'collection',
      ] + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
      $default_value = $this->getSetting('parent_type') ?
        $this->getSetting('parent_type') : 'collection';
      $element['parent_type'] = [
        '#title' => t('Parent type'),
        '#type' => 'select',
        '#options' => [
          'collection' => 'Collection',
          'complex_object' => 'Complex Object',
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
      $summary[] = t('Parent type: @parent_type', ['@parent_type' => $this->getSetting('parent_type')]);
      return $summary;
    }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $parent_type = $this->getSetting('parent_type');
    foreach ($items as $delta => $item) {
      $item_entity = $item->entity;
      $item_entity_model_term = $item_entity->get('field_model')->entity;
      // For dependency injection, call it like this...
      // $item_entity_model = $this->entityTypeManager->getStorage('taxonomy_term')->load($item_entity_model_term);
      $item_entity_model = (isset($item_entity_model_term) && is_object($item_entity_model_term)) ?
        $item_entity_model_term->getName() : '';

      // Depending on which parent type is configured, this may return either
      // Collection nodes, or asu_repository_item nodes that have the "Complex
      // object" model.
      if (($parent_type == "collection" && $item_entity->bundle() == "collection") ||
        ($parent_type == 'complex_object' && $item_entity_model == "Complex Object")) {
        if (isset($elements[$delta])) {
          // Even if the config is to output links, this is not ever intended
          // for CSV output of these.
          if (array_key_exists("#title", $elements[$delta])) {
            $elements[$delta]["#markup"] = $elements[$delta]["#title"];
            unset($elements[$delta]["#url"]);
            unset($elements[$delta]["#options"]);
            unset($elements[$delta]["#type"]);
          }
          if ($parent_type == "collection") {
            $string_value = (array_key_exists("#plain_text", $elements[$delta]) ?
              $elements[$delta]['#plain_text'] : $elements[$delta]["#title"]);
          } else {
            $string_value = (array_key_exists("#plain_text", $elements[$delta]) ?
              $elements[$delta]['#plain_text'] : $item_entity->id());
          }
          $elements[$delta]['#markup'] = $string_value;
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

}
