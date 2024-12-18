<?php

namespace Drupal\asu_default_fields\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ASUDefaultFieldsSettingsForm.
 */
class ASUDefaultFieldsSettingsForm extends ConfigFormBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs new MimeMappingForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'asu_default_fields.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'asu_default_fields';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo possible refactor for loadMultiple to get all the existing configs at once or may not even need to actually load the taxo terms and just have the tids.
    $config = $this->config('asu_default_fields.settings');
    $form['disable_handle_generation'] = [
      '#type' => 'checkbox',
      '#title' => 'Disable all Handles generation on the site.',
      '#default_value' => $config->get('disable_handle_generation'),
    ];
    $form['original_file_taxonomy_term'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Original File Term'),
      '#description' => $this->t('The original file taxonomy term'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_media_use']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('original_file_taxonomy_term')) : '',
    ];
    $form['service_file_taxonomy_term'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Service File Term'),
      '#description' => $this->t('The service file taxonomy term'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_media_use']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('service_file_taxonomy_term')) : '',
    ];
    $form['thumbnail_taxonomy_term'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Thumbnail File Term'),
      '#description' => $this->t('The thumbnail file taxonomy term'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_media_use']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('thumbnail_taxonomy_term')) : '',
    ];
    $form['preservation_master_taxonomy_term'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Preservation Master File Term'),
      '#description' => $this->t('The preservation master file taxonomy term'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_media_use']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('preservation_master_taxonomy_term')) : '',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('asu_default_fields.settings')
      ->set('disable_handle_generation', $form_state->getValue('disable_handle_generation'))
      ->set('original_file_taxonomy_term', $form_state->getValue('original_file_taxonomy_term'))
      ->set('service_file_taxonomy_term', $form_state->getValue('service_file_taxonomy_term'))
      ->set('thumbnail_taxonomy_term', $form_state->getValue('thumbnail_taxonomy_term'))
      ->set('preservation_master_taxonomy_term', $form_state->getValue('preservation_master_taxonomy_term'))
      ->save();
  }

}
