<?php

namespace Drupal\self_deposit\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Helper; redirect to the given node when ingesting media belonging to a node.
 */
class PerfArchiveChild {

  const NODE_COORDS = [
    'field_member_of',
    0,
    'target_id',
  ];

  /**
   * Delegated hook_form_alter().
   */
  public static function alter(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('self_deposit.selfdepositsettings');
    $form['#title'] = t("Add Performance Archive Child Item");
    // Prepopulate member_of.
    $parent_from_url = \Drupal::routeMatch()->getParameter('parent');
    if (!is_object($parent_from_url) && $parent_from_url) {
      $parent_from_url = \Drupal::entityTypeManager()->getStorage('node')->load($parent_from_url);
    }
    $form['field_member_of']['widget'][0]['target_id']['#default_value'] = $parent_from_url;

    // Prepopulate complex_object_child.
    $form['field_complex_object_child']['widget']['value']['#default_value'] = 1;
    // Prepopulate weight.
    $weight_param = \Drupal::request()->query->get('weight');
    if ($weight_param) {
      $form['field_weight']['widget'][0]['value']['#default_value'] = $weight_param;
    }
    else {
      $form['field_weight']['widget'][0]['value']['#default_value'] = 1;
    }

    if ($config->get('perf_archive_default_collection')) {
      $default_collection = \Drupal::entityTypeManager()->getStorage('node')->load($config->get('perf_archive_default_collection'));
      $of_perms = $default_collection->get('field_default_original_file_perm')->entity;
      $sf_perms = $default_collection->get('field_default_derivative_file_pe')->entity;
      $form['field_default_derivative_file_pe']['widget'][0]['target_id']['#default_value'] = $sf_perms;
      $form['field_default_original_file_perm']['widget'][0]['target_id']['#default_value'] = $of_perms;
    }

    $form['moderation_state']['widget'][0]['state']['#default_value'] = 'published';

    // Add file.
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => t('File'),
      '#upload_location' => "private://perf_archive",
      '#upload_validators' => [
        'file_validate_extensions' => ["txt rtf doc docx ppt pptx xls xlsx pdf odf odg odp ods odt fodt fods fodp fodg key numbers pages tiff tif jp2 xml jpf mp3 wav aac aif aiff mid flac m4a mp4"],
      ],
    ];

    $form['actions']['submit']['#submit'][] = [static::class, 'createFiles'];

    $prev_submit_action = $form['actions']['submit']['#submit'];
    // The default submit redirects to the parent node.
    $form['actions']['submit']['#submit'][] = [static::class, 'submit'];
    $form['actions']['submit']['#value'] = t('Submit and return to item');

    // This would redirect to the same form and increment the weight.
    $form['actions']['submit_add_more'] = [
      '#type' => 'submit',
      '#access' => TRUE,
      '#button_type' => 'secondary',
      '#weight' => 6,
      '#value' => t('Submit and add more'),
      '#submit' => $prev_submit_action,
      '#attributes' => ['class' => ['btn-secondary', 'btn-gold']],
    ];
    $form['actions']['submit_add_more']['#submit'][] = [static::class, 'submitAddMore'];
  }

  /**
   * Form submission handler.
   */
  public static function submit(array &$form, FormStateInterface $form_state) {
    $node_id = $form_state->getValue(static::NODE_COORDS);
    if ($node_id) {
      $form_state->setRedirect('entity.node.canonical', [
        'node' => $node_id,
      ]);
    }
  }

  /**
   * Form submission handler.
   */
  public static function submitAddMore(array &$form, FormStateInterface $form_state) {
    $node_id = $form_state->getValue(static::NODE_COORDS);
    if ($node_id) {
      $existing_weight = $form_state->getValue('field_weight', 0, 'value');
      $new_weight = $existing_weight[0]['value'] + 1;
      $form_state->setRedirect('self_deposit.perf_archive.add_child', [
        'node_type' => 'asu_repository_item',
        'parent' => $node_id,
      ], [
        'query' => ['weight' => $new_weight],
      ]);
    }
  }

  /**
   * Form submission handler.
   */
  public static function createFiles(array &$form, FormStateInterface $form_state) {
    // Add submit handler to process file/media?
    $form_file = $form_state->getValue('file', 0);
    if (isset($form_file[0]) && !empty($form_file[0])) {
      $file = File::load($form_file[0]);
      $file->setPermanent();
      $file->save();
      $file_id = $file->id();
      $of_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'Original File']);
      $original_file = reset($of_terms);
      $mime = $file->get('filemime')->getValue()[0]['value'];
      $filename = strtolower($file->getFilename());
      if (str_contains($mime, 'image') || str_contains($filename, ".jpg") || str_contains($filename, ".jpeg") || str_contains($filename, ".png")) {
        $media_type = 'image';
        $field_name = 'field_media_image';
        if (str_contains($filename, ".tif") || str_contains($filename, ".tiff")) {
          $media_type = 'file';
          $field_name = 'field_media_file';
        }
      }
      if (str_contains($filename, ".pdf") || str_contains($filename, ".doc") || str_contains($filename, ".docx")) {
        $media_type = 'document';
        $field_name = 'field_media_document';
      }
      if (str_contains($mime, 'audio')) {
        $media_type = 'audio';
        $field_name = 'field_media_audio_file';
      }
      if (str_contains($mime, 'video')) {
        $media_type = 'video';
        $field_name = 'field_media_video_file';
      }
      if (!isset($media_type)) {
        $media_type = 'file';
        $field_name = 'field_media_file';
      }
      $nid = $form_state->getValue('nid');
      $media_args = [
        'bundle' => $media_type,
        'uid' => \Drupal::currentUser()->id(),
        'field_media_of' => [
          ['target_id' => $nid],
        ],
        'field_media_use' => [
          ['target_id' => $original_file->id()],
        ],
      ];
      $media_args[$field_name] = [
        ['target_id' => $file_id],
      ];
      $media = Media::create($media_args);
      $media->save();
    }
  }

}
