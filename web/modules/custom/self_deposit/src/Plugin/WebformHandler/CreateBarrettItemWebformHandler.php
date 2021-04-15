<?php

namespace Drupal\self_deposit\Plugin\WebformHandler;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Create a new repository item entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Create a Barrett repository item",
 *   label = @Translation("Create a Barrett repository item"),
 *   category = @Translation("Entity Creation"),
 *   description = @Translation("Creates a new Barrett repository item from Webform Submissions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class CreateBarrettItemWebformHandler extends WebformHandlerBase {

  private function getModel($mime, $filename) {
    $filename = strtolower($filename);
    if (str_contains($mime, 'image') || str_contains($filename, ".jpg") || str_contains($filename, ".jpeg") || str_contains($filename, ".png")) {
      $model = 'Image';
      $media_type = 'image';
      $field_name = 'field_media_image';
      if (str_contains($filename, ".tif") || str_contains($filename, ".tiff")) {
        $media_type = 'file';
        $field_name = 'field_media_file';
      }
    }
    if (str_contains($filename, ".pdf") || str_contains($filename, ".doc") || str_contains($filename, ".docx")) {
      $model = 'Digital Document';
      $media_type = 'document';
      $field_name = 'field_media_document';
    }
    if (str_contains($mime, 'audio')) {
      $model = 'Audio';
      $media_type = 'audio';
      $field_name = 'field_media_audio_file';
    }
    if (str_contains($mime, 'video')) {
      $model = 'Video';
      $media_type = 'video';
      $field_name = 'field_media_video_file';
    }
    if (!$model) {
      $media_type = 'file';
      $model = 'Binary';
      $field_name = 'field_media_file';
    }

    return array($model, $media_type, $field_name);
  }

  private function createNode($values, $title, $model, $copyright_term, $perm_term, $member_of) {
    $paragraph = Paragraph::create(
      ['type' => 'complex_title', 'field_main_title' => $values['item_title']]
    );

    $paragraph->save();


    // TODO verify that we have all of the fields set based on the legacy barrett app
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
      // 'field_reuse_permissions' => [
      //   ['target_id' => $values['reuse_permissions']],
      // ],
      // keywords?
      'field_copyright_statement' => [
        ['target_id' => $copyright_term->id()],
      ],
      'field_default_derivative_file_pe' => [
        ['target_id' => $perm_term->id()],
      ],
      'field_default_original_file_perm' => [
        ['target_id' => $perm_term->id()],
      ],
      'field_model' => [
        ['target_id' => $model->id()],
      ],
      'field_extent' => [
        ['value' => $values['number_of_pages']]
      ]
    ];

    $node = Node::create($node_args);

    // $config = \Drupal::config('self_deposit.selfdepositsettings');
    // if ($config->get('collection_for_deposits')) {
    //   $collection = $config->get('collection_for_deposits');
    //   \Drupal::logger('webform handler')->info('collection is ' . $collection);
    $node->field_member_of = [
      ['target_id' => $member_of],
    ];
    // }
    $node->save();

    return $node;
  }

  private function createMedia($media_type, $field_name, $file_id, $nid) {
    $of_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'Original File']);
    $original_file = reset($of_terms);

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

    $taxo_manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $perm_term_arr = $taxo_manager->loadByProperties(['name' => 'ASU Only']);
    $perm_term = reset($perm_term_arr);

    $media_args['field_access_terms'] = [
      ['target_id' => $perm_term->id()],
    ];
    $media_args[$field_name] = [
      ['target_id' => $file_id],
    ];
    $media = Media::create($media_args);
    $media->save();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    // Get an array of the values from the submission.
    $values = $webform_submission->getData();
    // $type = $values['file_type'];
    $files = $values['file'];

    // TODO need to do some handling here for if there are multiple files. If so we'd need to create a complex object with children
    if (count($files) > 1) {
      $model = 'Complex Object';
      $child_files = [];
      foreach ($files as $file_id) {
        $file = \Drupal::entityTypeManager()->getStorage('file')->load(intval($file_id));
        $mime = $file->getMimeType();
        $filename = $file->getFilename();
        list($fmodel, $fmedia_type, $ffield_name) = $this->getModel($mime, $filename);
        $child_files[$file_id] = [
          'model' => $fmodel,
          'media_type' => $fmedia_type,
          'field_name' => $ffield_name,
          'file_name' => $filename
        ];
      }
    }
    else {
      $file = \Drupal::entityTypeManager()->getStorage('file')->load(intval($files[0]));
      $mime = $file->getMimeType();
      $filename = $file->getFilename();
      list($model, $media_type, $field_name) = $this->getModel($mime, $filename);
    }

    $term = $model;
    $taxo_manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $taxo_terms = $taxo_manager->loadByProperties(['name' => $term]);
    $taxo_term = reset($taxo_terms);

    $copyright_term_arr = $taxo_manager->loadByProperties(['name' => 'In Copyright']);
    $copyright_term = reset($copyright_term_arr);

    $perm_term_arr = $taxo_manager->loadByProperties(['name' => 'ASU Only']);
    $perm_term = reset($perm_term_arr);
    $member_of = 312;
    if ($model == 'Complex Object') {
      $node = $this->createNode($values, $values['item_title'], $taxo_term, $copyright_term, $perm_term, $member_of);
      foreach ($child_files as $cfkey => $cfvalues) {
        $fmember_of = $node->id();
        $ftaxo_terms = $taxo_manager->loadByProperties(['name' => $cfvalues['model']]);
        $ftaxo_term = reset($ftaxo_terms);
        $child_node = $this->createNode($values, $cfvalues['file_name'], $ftaxo_term, $copyright_term, $perm_term, $fmember_of);
        $this->createMedia($cfvalues['media_type'], $cfvalues['field_name'], $cfkey, $child_node->id());
      }
    }
    else {
      $node = $this->createNode($values, $values['item_title'], $taxo_term, $copyright_term, $perm_term, $member_of);
      $this->createMedia($media_type, $field_name, $files[0], $node->id());
    }

    $webform_submission->setElementData('item_node', $node->id());
  }
}
