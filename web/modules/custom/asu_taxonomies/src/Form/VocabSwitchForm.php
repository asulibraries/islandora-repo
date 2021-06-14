<?php

namespace Drupal\asu_taxonomies\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VocabSwitchForm.
 */
class VocabSwitchForm extends FormBase {

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
  public function getFormId() {
    return 'vocab_switch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['term_to_change'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Term to change'),
      '#target_type' => 'taxonomy_term',
      '#description' => $this->t('Select the term you would like to change'),
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'autocomplete_taxonomy_terms',
          'display_name' => 'entity_reference_2',
          'arguments' => []
        ],
      ],
      '#weight' => '0',
    ];
    $form['destination_vocabulary'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Destination Vocabulary'),
      '#description' => $this->t('The vocabulary you would like to change this term TO'),
      '#target_type' => 'taxonomy_vocabulary',
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
   $term_to_change = $form_state->getValue('term_to_change');
   $destination_vocab = $form_state->getValue('destination_vocabulary');
   $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($term_to_change);
   $term->vid->setValue($destination_vocab);
   $term->save();
   $this->messenger()->addMessage('saved ' . $term->getName() . ' to ' . $destination_vocab);
  }

}
