<?php

namespace Drupal\asu_deposit_methods\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings for the ASU deposit functionality.
 */
class DepositSettings extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'asu_deposit_methods.depositsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('asu_deposit_methods.depositsettings');

    $form['sheet_music_default_genre'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Genre for Sheet Music Form'),
      '#description' => $this->t('The default genre to apply to node forms in the Sheet Music form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['genre']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('sheet_music_default_genre')) : '',
    ];

    $form['sheet_music_default_copyright'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Copyright Statement for Sheet Music Form'),
      '#description' => $this->t('The default copyright statement to apply to node forms in the Sheet Music form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['copyright_statements']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('sheet_music_default_copyright')) : '',
    ];

    $form['sheet_music_default_reuse'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Reuse Permissions for Sheet Music Form'),
      '#description' => $this->t('The default reuse permissions to apply to node forms in the Sheet Music form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['reuse_permissions']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('sheet_music_default_reuse')) : '',
    ];

    $form['sheet_music_default_model'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Model for Sheet Music Form'),
      '#description' => $this->t('The default model to apply to node forms in the Sheet Music form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['islandora_models']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('sheet_music_default_model')) : '',
    ];

    $form['sheet_music_default_identifier_type'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Identifier Type for Sheet Music Form'),
      '#description' => $this->t('The default identifier type to apply to node forms in the Sheet Music form'),
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => ['target_bundles' => ['identifier_types']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('taxonomy_term')->load($config->get('sheet_music_default_identifier_type')) : '',
    ];

    $form['sheet_music_default_collection'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Default Collection for Sheet Music Form'),
      '#description' => $this->t('The default collection to apply to node forms in the Sheet Music form'),
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => ['collection']],
      '#default_value' => $config ? $this->entityTypeManager->getStorage('node')->load($config->get('sheet_music_default_collection')) : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('asu_deposit_methods.depositsettings')
      ->set('sheet_music_default_genre', $form_state->getValue('sheet_music_default_genre'))
      ->set('sheet_music_default_copyright', $form_state->getValue('sheet_music_default_copyright'))
      ->set('sheet_music_default_reuse', $form_state->getValue('sheet_music_default_reuse'))
      ->set('sheet_music_default_model', $form_state->getValue('sheet_music_default_model'))
      ->set('sheet_music_default_identifier_type', $form_state->getValue('sheet_music_default_identifier_type'))
      ->set('sheet_music_default_collection', $form_state->getValue('sheet_music_default_collection'))
      ->save();
  }

}
