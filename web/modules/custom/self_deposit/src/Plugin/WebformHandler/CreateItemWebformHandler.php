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

    $model_tid = NULL;
    switch ($values['file_type']) {
      case 'document':
        $term = "Digital Document";
      case 'image':
        $term = "Image";
      case 'audio':
        $term = "Audio";
      case 'video':
        $term = 'Video';
      case 'file':
        $term = 'Binary';
      default:
        $term = 'Binary';
    }
    $taxo_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $term]);
    $taxo_term = reset($taxo_terms);

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
      'field_copyright_statement' => [
        ['target_id' => $values['copyright_statement']],
      ],
      'field_reuse_permissions' => [
        ['target_id' => $values['reuse_permissions']],
      ],
      'field_default_derivative_file_pe' => [
        ['target_id' => $values['file_permissions']],
      ],
      'field_default_original_file_perm' => [
        ['target_id' => $values['file_permissions']],
      ],
      'field_model' => [
        ['target_id' => $taxo_term->id()],
      ],
    ];

    $node = Node::create($node_args);
    $node->save();
    $webform_submission->setElementData('item_node', $node->id());
  }

}
