<?php

/**
 * @file
 */

/**
 * Implements hook_views_data.
 */
function asu_landing_site_views_data() {
  $data = [];
  $data['keep_item']['table']['group'] = t('KEEP Item');
  $data['keep_item']['table']['base'] = [
    'title' => t('KEEP Item'),
    'help' => t('KEEP item data provided by the KEEP API'),
    'query_id' => 'keep',
  ];
  $data['keep_item']['created'] = [
    'title' => t('Created'),
    'help' => t('Date item was created in the system'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];
  $data['keep_item']['changed'] = [
    'title' => t('Changed'),
    'help' => t('Date item was last updated in the system'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];
  $data['keep_item']['field_rich_description'] = [
    'title' => t('Description'),
    'help' => t('The description for the resource'),
    'field' => [
      'id' => 'markup',
    ],
  ];
  $data['keep_item']['nid'] = [
    'title' => t('ID'),
    'help' => t('The node id for the item'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['keep_item']['field_title'] = [
    'title' => t('Title'),
    'help' => t('The title of the item'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['keep_item']['url'] = [
    'title' => t('URL'),
    'help' => t('The URL for the item'),
    'field' => [
      'id' => 'url',
    ],
  ];
  $data['keep_item']['field_handle'] = [
    'title' => t('Handle'),
    'help' => t('The permanent link or handle for the item'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['keep_item']['field_model'] = [
    'title' => t('Model'),
    'help' => t('The model or type of resource from a systems perspective'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['keep_item']['thumbnail_url'] = [
    'title' => t('Thumbnail'),
    'help' => t('the link to the thumbnail'),
    'field' => [
      'id' => 'keep_thumbnail',
    ],
  ];
  return $data;
}
