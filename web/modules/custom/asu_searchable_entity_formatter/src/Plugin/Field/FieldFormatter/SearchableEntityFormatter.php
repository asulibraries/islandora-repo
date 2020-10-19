<?php

namespace Drupal\asu_searchable_entity_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'searchable_entity_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "searchable_entity_formatter",
 *   label = @Translation("Searchable entity formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SearchableEntityFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
            'search_link' => 'search?f[0]',
            'search_var' => 'all_subjects',
            'search_term' => FALSE,
        ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['search_link'] = [
        '#title' => t('Search base path'),
        '#type' => 'textfield',
        '#required' => true,
        '#default_value' => $this->getSetting('search_link'),
    ];
    $elements['search_var'] = [
        '#title' => t('Search variable'),
        '#type' => 'textfield',
        '#required' => true,
        '#default_value' => $this->getSetting('search_var'),
    ];
    $elements['search_term'] = [
        '#title' => t('Use label as search term'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('search_term'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Search path: @search_link', ['@search_link' => $this->getSetting('search_link')]);
    $summary[] = t('Variable: @search_var', ['@search_var' => $this->getSetting('search_var')]);
    $summary[] = $this->getSetting('search_term') ? t('Use label as search term') : t('Use ID as search term');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $label = $entity->label();
      $search_link = $this->getSetting('search_link');
      $search_var = $this->getSetting('search_var');
      $search_term = $this->getSetting('search_term');

      if ($search_term == TRUE) {
        $search = "/" . $search_link . "=" . $search_var . ":" . $label;
      }
      else {
        $tid = $entity->id();
        $search = "/" . $search_link . "=" . $search_var . ":" . $tid;
      }
      $markup = '<a href="' . $search . '">' . $label . '</a>';

      $elements[$delta] = [
          '#type' => 'link',
          '#title' => $label,
          '#markup' => $markup,
      ];

      $elements[$delta]['#cache']['tags'] = $entity
          ->getCacheTags();
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }

}
