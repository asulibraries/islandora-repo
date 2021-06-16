<?php

namespace Drupal\self_deposit\Plugin\WebformHandler;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\user\Entity\User;

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
    if (!isset($model)) {
      $media_type = 'file';
      $model = 'Binary';
      $field_name = 'field_media_file';
    }

    return array($model, $media_type, $field_name);
  }

  private function getOrCreateTerm($string, $vocab, $relator = NULL) {
    $taxo_manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $arr = $taxo_manager->loadByProperties(['name' => $string, 'vid' => $vocab]);
    if (count($arr) > 0) {
      $term = reset($arr);
    }
    else {
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

  private function createNode($webform_submission, $values, $title, $model, $copyright_term, $perm_term, $member_of) {
    $paragraph = Paragraph::create(
      ['type' => 'complex_title', 'field_main_title' => $title]
    );

    $paragraph->save();

    $keywords = [];
    foreach($values['keywords'] as $key) {
      $kterm = $this->getOrCreateTerm($key, 'subject');
      array_push($keywords, $kterm);
    }

    \Drupal::logger('barrett')->info(print_r($values['student_name'], TRUE));

    $contribs = [];
    if (array_key_exists('your_name', $values)) {
      array_push($contribs, $this->getOrCreateTerm($values['your_name'], 'person', 'relators:aut'));
    } else {
      array_push($contribs, $this->getOrCreateTerm($values['student_name']['last'] . ", " . $values['student_name']['first'], 'person', 'relators:aut'));
    }
    foreach($values['group_members'] as $gm) {
      // make group members as aut
      array_push($contribs, $this->getOrCreateTerm($gm['last'] . ", " . $gm['first'], 'person', 'relators:aut'));
    }

    foreach ($values['thesis_director'] as $td) {
      // make group members as ths
      array_push($contribs, $this->getOrCreateTerm($td['last'] . ", " . $td['first'], 'person', 'barrettrelators:ths'));
    }

    foreach ($values['committee_members'] as $cm) {
      // make group members as dgc
      array_push($contribs, $this->getOrCreateTerm($cm['last'] . ", " . $cm['first'], 'person', 'barrettrelators:dgc'));
    }

    foreach ($values['additional_contributors'] as $ac) {
      // make additional contribs as ctb
      array_push($contribs, $this->getOrCreateTerm($ac['last'] . ", " . $ac['first'], 'person', 'relators:ctb'));
    }

    array_push($contribs, $this->getOrCreateTerm('Barrett, The Honors College', 'corporate_body', 'relators:ctb'));

    $date_submitted = $webform_submission->getCreatedTime();
    $month = \Drupal::service('date.formatter')->format($date_submitted, 'custom', 'm');
    $year =
    \Drupal::service('date.formatter')->format($date_submitted, 'custom', 'Y');
    if ($month <= 7) {
      $spring_year = $year;
      $created_month = "05";
    }
    if ($month >= 8) {
      $created_month = "12";
      $fall_year = $year;
    }
    if (!isset($fall_year)) {
      $fall_year = $spring_year - 1;
    }
    if (!isset($spring_year)) {
      $spring_year = $fall_year + 1;
    }
    $series_val = "Academic Year " . $fall_year . "-" . $spring_year;
    $created_date_val = $year . "-" . $created_month;

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
      'field_linked_agent' => $contribs,
      // TODO add schools/colleges?
      'field_edtf_date_created' => [
        'value' => $created_date_val
      ],
      'field_series' => [
        'value' => $series_val
      ],
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
      ],
      'field_member_of' => [
        ['target_id' => $member_of]
      ]
    ];

    if (array_key_exists('embargo_release_date', $values)) {
      $embargo_vals = explode('T', $values['embargo_release_date']);
      $node_args['field_embargo_release_date'] = ['value' => $embargo_vals[0] . "T23:59:59"];
    }
    if (array_key_exists('language1', $values)) {
      $node_args['field_language'] = [['target_id' => $values['language1']]];
    }

    $node = Node::create($node_args);
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
    $config = \Drupal::config('self_deposit.selfdepositsettings');
    if ($config->get('barrett_collection_for_deposits')) {
      $member_of = $config->get('barrett_collection_for_deposits');
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
    }
    else {
      $node = $this->createNode($webform_submission, $values, $values['item_title'], $taxo_term, $copyright_term, $perm_term, $member_of);
      $this->createMedia($media_type, $field_name, $files[0], $node->id());
    }
    // create user, populate from student_asurite, student_id
    $user = user_load_by_name($values['student_asurite']);
    if ($user == NULL) {
      $user = User::create();
      $user->enforceIsNew();
      $user->setEmail($values['student_asurite'] . "@asu.edu");
      $user->setUsername($values['student_asurite']);
      $user->set('field_last_name', $values['student_name']['last']);
      $user->set('field_first_name', $values['student_name']['first']);
      $user->set('field_honors', TRUE);
      $user->set('field_emplid', $values['student_id']);
      $user->save();
    }
    $webform_submission->setOwnerId($user->id());

    $webform_submission->setElementData('item_node', $node->id());
  }
}
