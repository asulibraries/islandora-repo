<?php

/**
 * @file
 * Views include.
 */

/**
 * Implements hook_views_data_alter().
 *
 * Adds a Download Count pseudo-field.
 */
function asu_item_extras_views_data_alter(array &$data) {
  $data['node']['download_count'] = [
    'title' => t('Download Count'),
    'group' => t('Content'),
    'field' => [
      'title' => t('Download Count'),
      'help' => t('Returns download counts from asu_item_extras.'),
      'id' => 'download_count',
    ],
  ];
}
