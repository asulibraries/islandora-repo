<?php

namespace Drupal\self_deposit\Plugin\WebformHandler;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
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
    if (!isset($model)) {
      $media_type = 'file';
      $model = 'Binary';
      $field_name = 'field_media_file';
    }

    return array($model, $media_type, $field_name);
  }

  private function getOrCreateTerm($string, $vocab, $relator = NULL)
  {
    $taxo_manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $arr = $taxo_manager->loadByProperties(['name' => $string, 'vid' => $vocab]);
    if (count($arr) > 0) {
      $term = reset($arr);
    } else {
      $term = Term::create([
        'name' => $string,
        'vid' => $vocab,
        'langcode' => 'en',
      ]);
      $term->save();
    }
    $term_arr = ['target_id' => $term->id()];
    if ($relator) {
      $term_arr['rel_type'] = $relator;
    }
    return $term_arr;
  }

  private function createNode($webform_submission, $values, $title, $model, $copyright_term, $perm_term, $member_of)
  {
    $paragraph = Paragraph::create(
      ['type' => 'complex_title', 'field_main_title' => $title]
    );

    $paragraph->save();

    $keywords = [];
    foreach ($values['keywords'] as $key) {
      $kterm = $this->getOrCreateTerm($key, 'subject');
      array_push($keywords, $kterm);
    }

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
      'field_reuse_permissions' => [
        ['target_id' => $values['reuse_permissions']],
      ],
      'field_subjects' => $keywords,
      'field_copyright_statement' => [
        ['target_id' => $copyright_term->id()],
      ],
      'field_default_derivative_file_pe' => [
        ['target_id' => $perm_term->id()],
      ],
      'field_default_original_file_perm' => [
        ['target_id' => $perm_term->id()],
      ],
      'field_embargo_release_date' => [
        $values['embargo_release_date'] . "T23:59:59",
      ],
      'field_model' => [
        ['target_id' => $model->id()],
      ],
      'field_member_of' => [
        ['target_id' => $member_of]
      ]
    ];

    $node = Node::create($node_args);
    $node->save();

    return $node;
  }

  private function createMedia($media_type, $field_name, $file_id, $nid)
  {
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
    $files = $values['file'];

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
    } else {
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

    $perm_term_arr =
    $taxo_manager->loadByProperties(['name' => $values['file_permissions_select']]);
    $perm_term = reset($perm_term_arr);

    $config = \Drupal::config('self_deposit.selfdepositsettings');
    if ($config->get('collection_for_deposits')) {
      $member_of = $config->get('collection_for_deposits');
    }

    if ($model == 'Complex Object') {
      $node = $this->createNode($webform_submission, $values, $values['item_title'], $taxo_term, $copyright_term, $perm_term, $member_of);
      foreach ($child_files as $cfkey => $cfvalues) {
        $fmember_of = $node->id();
        $ftaxo_terms = $taxo_manager->loadByProperties(['name' => $cfvalues['model']]);
        $ftaxo_term = reset($ftaxo_terms);
        $child_node = $this->createNode($webform_submission, $values, $cfvalues['file_name'], $ftaxo_term, $copyright_term, $perm_term, $fmember_of);
        $this->createMedia($cfvalues['media_type'], $cfvalues['field_name'], $cfkey, $child_node->id());
      }
    } else {
      $node = $this->createNode($webform_submission, $values, $values['item_title'], $taxo_term, $copyright_term, $perm_term, $member_of);
      $this->createMedia($media_type, $field_name, $files[0], $node->id());
    }

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
