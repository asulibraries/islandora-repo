<?php

namespace Drupal\asu_search\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a "node type" property.
 *
 * @see \Drupal\as_search_\Plugin\search_api\processor\DescendantExtractedText
 */
class NodeTypeProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'bundles' => [],
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
    return $form;
  }

}
