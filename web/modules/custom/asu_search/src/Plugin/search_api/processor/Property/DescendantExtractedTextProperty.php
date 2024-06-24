<?php

namespace Drupal\asu_search\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a "descendant extracted text" property.
 *
 * @see \Drupal\as_search_\Plugin\search_api\processor\DescendantExtractedText
 */
class DescendantExtractedTextProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
	    'bundles' => [],
	    'media_use_term' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $configuration = $field->getConfiguration();

    // Build an option list of available node bundles.
    $options = [];
    foreach (\Drupal::service('entity_type.bundle.info')->getBundleInfo('node') as $bundle_id => $properties) {
      $options[$bundle_id] = $properties['label'];
    }

    $form['bundles'] = [
      '#title' => $this->t('Bundles'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $configuration['bundles'] ?? [],
    ];

    $form['media_use_term'] = [
      '#title' => $this->t('Extracted Text Term'),
      '#description' => $this->t('Select the Media Use term of the media for the extracted text. (Usually "Extracted Text".)'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_media_use']],
     ];

    return $form;
  }

}
