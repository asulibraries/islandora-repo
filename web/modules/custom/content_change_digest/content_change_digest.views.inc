<?php

/**
 * @file
 */

/**
 * Implements hook_views_data_alter().
 */
// Function content_change_digest_views_data_alter(array &$data) {
//   \Drupal::logger('content_change_digest')->notice("in the data alter hook");
//   // does the key name here need to be "content_digest_change_views_filter" ??
//   $data['node_field_revision']['content_digest_change_filter'] = [
//     'title' => t('Content Change Digest - no initial revisions filter'),
//     'filter' => [
//       'title' => t('No initial revisions filter'),
//       'help' => t('Provides a custom filter for project nodes that are not the initial revision.'),
//       'field' => 'nid',
//       'id' => 'content_change_digest_content_digest_change_views_filter',
//     ],
//   ];
//   // \Drupal::logger('content_change_digest')->notice(print_r($data,true));
// }.

/**
 * Implements hook_views_data().
 */
function content_change_digest_views_data() {
  // \Drupal::logger('content_change_digest')->info('in views_data hook');
  $data = [];
  /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type) {
    // Add the diff_from and diff_to fields to every revisionable entity type.
    if ($entity_type->isRevisionable()) {
      $revision_base_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
      $data[$revision_base_table]['content_digest_change_filter'] = [
        'title' => t('Content Change Digest - no initial revisions filter'),
        'filter' => [
          'title' => t('No initial revisions filter'),
          'help' => t('Provides a custom filter for project nodes that are not the initial revision.'),
          'field' => 'nid',
          'id' => 'content_change_digest_content_digest_change_views_filter',
        ],
      ];
      // \Drupal::logger('content_change_digest')->notice(print_r($data[$revision_base_table]['content_digest_change_filter'],true));
    }
  }

  return $data;
}
