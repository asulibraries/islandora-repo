<?php

use Drupal\views\Views;

/**
 * Implements hook_views_data_alter().
 */
function asu_content_change_digest_views_data_alter(array &$data) {
  \Drupal::logger('asu_content_change_digest')->notice(print_r($data,true));
  // does the key name here need to be "content_digest_change_views_filter" ??
  $data['node']['content_digest_change_filter'] = [
    'title' => t('Content Change Digest - no initial revisions filter'),
    'filter' => [
      'title' => t('No initial revisions filter'),
      'help' => t('Provides a custom filter for project nodes that are not the initial revision.'),
      'field' => 'nid',
      'id' => 'content_digest_change_views_filter',
    ],
  ];
}

