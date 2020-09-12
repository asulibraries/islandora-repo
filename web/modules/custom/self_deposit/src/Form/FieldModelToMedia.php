<?php

namespace Drupal\self_deposit\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Helper; set some defaults and redirect to media ingest based on item model.
 *
 * Defaults set include display hints (of which we suppress the element's
 * display), instead of requiring that they be selected manually, as well as
 * selecting the "original use" option for the "media use".
 *
 * This file has been heavily copied from basic_ingest module by dgi
 */
class FieldModelToMedia {

  const REDIRECT = 'redirect_to_media';

  const NODE_COORDS = [
    'edit',
    'field_media_of',
    'widget',
    0,
    'target_id',
  ];
  const USE_COORDS = [
    'edit',
    'field_media_use',
    'widget',
  ];
  const ORIGINAL_FILE_URI = 'http://pcdm.org/use#OriginalFile';
  const MEDIA_TYPE = 'media_type';

  /**
   * Delegated for hook_form_alter().
   */
  public static function alter(array &$form, FormStateInterface $form_state) {
    $submit =& $form['actions']['submit']['#submit'];
    $submit[] = [static::class, 'submit'];

    $form[static::REDIRECT] = [
      '#type' => 'checkbox',
      '#title' => t('Add media'),
      '#default_value' => $form_state->getValue(static::REDIRECT, TRUE),
      '#weight' => 100,
    ];

    $media_types = [
      'document' => t('Document (txt, odf, pdf, xml, docx)'),
      'image' => t('Image (png, jpeg, jpg, gif)'),
      'audio' => t('Audio (aif, mp3, wav, aac)'),
      'video' => t('Video (mp4, mkv, avi, mov, dpx)'),
      'file' => t('File (any other file or type of files, including tif and jp2)'),
    ];

    $form[static::MEDIA_TYPE] = [
      '#type' => 'select',
      '#options' => $media_types,
      '#title' => t('File Type'),
      '#weight' => 100,
      '#required' => TRUE,
      '#empty_option' => t('- Select File Type -'),
      '#description' => t('Select the type of file you are planning to upload. Read more about recommended file formats and policies here.'),
    ];

    if (array_key_exists('field_member_of', $form)) {
      $config = \Drupal::config('self_deposit.selfdepositsettings');
      $node = $config->get('collection_for_deposits');
      if ($node) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($node);
      }
      $form_state->set('field_member_of', $node);
      $form['field_member_of']['widget'][0]['target_id']['#default_value'] = $node;
      $form['field_member_of']['widget']['entity_browser']['#default_value'] = $node;
      $form['field_member_of']['#access'] = FALSE;
    }

    if (array_key_exists('field_model', $form)) {
      $form['field_model']['#access'] = FALSE;
    }
  }

  /**
   * Form submission handler; do the redirects if selected.
   */
  public static function submit(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(static::REDIRECT) && $form_state->getValue(static::MEDIA_TYPE)) {
      $query_params = [];

      // Set the model from the config + media type selected.
      $config = \Drupal::config('self_deposit.selfdepositsettings');
      $id = $config->get($form_state->getValue(static::MEDIA_TYPE) . '_media_model');
      $node = $form_state->getFormObject()->getEntity();
      $node->set('field_model', $id);
      $node->save();

      // Make the media be ingested in the context of the node, by default.
      NestedArray::setValue(
        $query_params,
        static::NODE_COORDS,
        $form_state->getFormObject()->getEntity()->id()
      );

      // Make the media ingest select the "original use" term, by default.
      $original_use_id = static::getOriginalUseId();
      if ($original_use_id) {
        NestedArray::setValue(
          $query_params,
          static::USE_COORDS,
          $original_use_id
        );
      }

      NestedArray::setValue(
        $query_params,
        ['isSelfDeposit'],
        'true'
      );
      // Actually set the redirect.
      $form_state->setRedirect('entity.media.add_form', [
        'media_type' => $form_state->getValue(static::MEDIA_TYPE),
      ], [
        'query' => $query_params,
      ]);
    }
  }

  /**
   * Fetch the taxonomy term ID for the "original use" term.
   *
   * @return int|bool
   *   The ID of the first taxonomy term with the "original use" URI if it
   *   exists; otherwise, boolean FALSE.
   */
  protected static function getOriginalUseId() {
    $term_storage = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term');
    $original_use_term_results = $term_storage->getQuery()
      ->condition('vid', 'islandora_media_use')
      ->condition('field_external_uri', static::ORIGINAL_FILE_URI)
      ->execute();

    return reset($original_use_term_results);

  }

}
