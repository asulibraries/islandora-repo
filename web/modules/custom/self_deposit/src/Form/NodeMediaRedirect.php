<?php

namespace Drupal\self_deposit\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Helper; redirect to the given node when ingesting media belonging to a node.
 */
class NodeMediaRedirect {

  const NODE_COORDS = [
    'field_media_of',
    0,
    'target_id',
  ];

  /**
   * Delegated hook_form_alter().
   */
  public static function alter(array &$form, FormStateInterface &$form_state) {
    $current_request = \Drupal::request()->getRequestUri();
    $urlsplit = explode("?", $current_request);
    if (count($urlsplit) > 0) {
      $query_params = urldecode(array_values($urlsplit)[1]);
      $params = explode("&", $query_params);
      foreach ($params as $par) {
        if (strpos($par, 'field_media_of')) {
          $field_media_of = explode("=", $par)[1];
        }
        if (strpos($par, 'field_media_use')) {
          $field_media_use = explode("=", $par)[1];
        }
      }
    }
    if (isset($field_media_use)) {
      $form['field_media_use']['widget']['#default_value'] = [$field_media_use];
      $form['field_media_use']['#access'] = FALSE;
    }
    if (isset($field_media_of)) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($field_media_of);
      $form_state->set('field_media_of', $node);
      $form['field_media_of']['widget'][0]['target_id']['#default_value'] = $node;
      $form['field_media_of']['widget']['entity_browser']['#default_value'] = $node;
      $form['field_media_of']['#access'] = FALSE;
    }

    $form['field_access_terms']['#access'] = FALSE;
    $form['status']['#access'] = FALSE;
    $form['field_original_name']['#access'] = FALSE;
    $form['revision_log_message']['#access'] = FALSE;
    $form['author']['#access'] = FALSE;
    $form['actions']['submit']['#submit'][] = [static::class, 'submit'];
  }

  /**
   * Form submission handler.
   */
  public static function submit(array &$form, FormStateInterface &$form_state) {
    $self_deposit = \Drupal::request()->query->get('isSelfDeposit');
    if ($self_deposit) {
      \Drupal::messenger()->addStatus('Thank you for submitting your item to the ASU Digital Repository. The staff will contact you to discuss your submission shortly.');
      $form_state->setRedirect('<front>');
    }
    else {
      $node_id = $form_state->getValue(static::NODE_COORDS);
      if ($node_id) {
        $form_state->setRedirect('entity.node.canonical', [
          'node' => $node_id,
        ]);
      }
    }
  }

}
