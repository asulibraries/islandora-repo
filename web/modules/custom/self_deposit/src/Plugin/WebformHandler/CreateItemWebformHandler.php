<?php

namespace Drupal\self_deposit\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Create a new repository item entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Create a repository item",
 *   label = @Translation("Create a repository item"),
 *   category = @Translation("Entity Creation"),
 *   description = @Translation("Creates a new repository item from Webform Submissions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class CreateItemWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    // Get an array of the values from the submission.
    $values = $webform_submission->getData();
    $type = $values['file_type'];
    if ($type == 'document') {
      $term = "Digital Document";
    }
    elseif ($type == 'image') {
      $term = "Image";
    }
    elseif ($type == 'video') {
      $term = 'Video';
    }
    elseif ($type == 'audio') {
      $term = "Audio";
    }
    elseif ($type == 'file') {
      $term = 'Binary';
    }
    else {
      $term = 'Binary';
    }
    $taxo_manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $taxo_terms = $taxo_manager->loadByProperties(['name' => $term]);
    $taxo_term = reset($taxo_terms);

    $copyright_term_arr = $taxo_manager->loadByProperties(['name' => 'In Copyright']);
    $copyright_term = reset($copyright_term_arr);

    $paragraph = Paragraph::create(['type' => 'complex_title', 'field_main_title' => $values['item_title']]);
    $paragraph->save();

    $node_args = [
      'type' => 'asu_repository_item',
      'langcode' => 'en',
      'created' => time(),
      'changed' => time(),
      'uid' => \Drupal::currentUser()->id(),
      'moderation_state' => 'draft',
      'field_title' => [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ],
      'field_rich_description' => [
        'value' => $values['item_description'],
        'format' => 'description_restricted_items',
      ],
      'field_embargo_release_date' => [
        $values['embargo_release_date'] . "T23:59:59",
      ],
      'field_reuse_permissions' => [
        ['target_id' => $values['reuse_permissions']],
      ],
      'field_copyright_statement' => [
        ['target_id' => $copyright_term->id()],
      ],
      'field_default_derivative_file_pe' => [
        ['target_id' => $values['file_permissions_select']],
      ],
      'field_default_original_file_perm' => [
        ['target_id' => $values['file_permissions_select']],
      ],
      'field_model' => [
        ['target_id' => $taxo_term->id()],
      ],
    ];

    $node = Node::create($node_args);

    $config = \Drupal::config('self_deposit.selfdepositsettings');
    if ($config->get('collection_for_deposits')) {
      $collection = $config->get('collection_for_deposits');
      \Drupal::logger('webform handler')->info('collection is ' . $collection);
      $node->field_member_of = [
        ['target_id' => $collection],
      ];
    }
    $node->save();
    $webform_submission->setElementData('item_node', $node->id());
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $values = $webform_submission->getData();
    \Drupal::logger('custom webform handler')->info(print_r($values, TRUE));

    $type = $values['file_type'];
    $file_id = NULL;
    if ($type == 'document') {
      $file_id = $values['document'];
      $field_name = 'field_media_document';
    }
    elseif ($type == 'image') {
      $file_id = $values['image'];
      $field_name = 'field_media_image';
    }
    elseif ($type == 'video') {
      $file_id = $values['video'];
      $field_name = 'field_media_video_file';
    }
    elseif ($type == 'audio') {
      $file_id = $values['audio'];
      $field_name = 'field_media_audio_file';
    }
    elseif ($type == 'file') {
      $file_id = $values['file'];
      $field_name = 'field_media_file';
    }
    else {
      $file_id = $values['file'];
      $field_name = 'field_media_file';
    }
    \Drupal::logger('custom webform handler')->info("media type is " . $type);
    \Drupal::logger('custom webform handler')->info("file_id is " . $file_id);

    $file = \Drupal::entityTypeManager()->getStorage('file')->load(intval($file_id));
    \Drupal::logger('custom webform handler')->info("loaded file is " . $file->id());

    $of_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'Original File']);
    $original_file = reset($of_terms);

    $media_args = [
      'bundle' => $type,
      'uid' => \Drupal::currentUser()->id(),
      'field_media_of' => [
        ['target_id' => $values['item_node']],
      ],
      'field_media_use' => [
        ['target_id' => $original_file->id()],
      ],
    ];

    if ($values['file_permissions']) {
      $media_args['field_access_terms'] = [
        ['target_id' => $values['file_permissions']],
      ];
    }
    $media_args[$field_name] = [
      ['target_id' => $file_id],
    ];
    $media = Media::create($media_args);
    $media->save();
  }

}
