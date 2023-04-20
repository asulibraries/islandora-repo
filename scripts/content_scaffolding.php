<?php

/**
 * @file
 * Script to load content needed for basic functions.
 */

use Drupal\user\Entity\User;
use Drupal\Core\File\FileSystemInterface;

// Need to load as admin (with fcrepo role) so we can talk to fedora.
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(User::load(1));

$tm = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
$nm = \Drupal::entityTypeManager()->getStorage('node');
$pm = \Drupal::entityTypeManager()->getStorage('paragraph');

// Access Terms.
$access_terms = [
  [
    'vid' => 'islandora_access',
    'name' => 'ASU Only',
  ],
  [
    'vid' => 'islandora_access',
    'name' => 'Private',
  ],
  [
    'vid' => 'islandora_access',
    'name' => 'Public',
  ],
];

foreach ($access_terms as $t) {
  if (count($tm->loadByProperties($t)) < 1) {
    $term = $tm->create($t);
    $term->enforceIsNew();
    $term->save();
  }
}

// Default Fields Settings.
$default_fields_config = \Drupal::configFactory()->getEditable('asu_default_fields.settings');
$default_fields_terms = [
  [
    'vid' => 'islandora_media_use',
    'name' => 'Original File',
    'setting' => 'original_file_taxonomy_term',
  ],
  [
    'vid' => 'islandora_media_use',
    'name' => 'Service File',
    'setting' => 'service_file_taxonomy_term',
  ],
  [
    'vid' => 'islandora_media_use',
    'name' => 'Thumbnail Image',
    'setting' => 'thumbnail_taxonomy_term',
  ],
  [
    'vid' => 'islandora_media_use',
    'name' => 'Preservation Master File',
    'setting' => 'preservation_master_taxonomy_term',
  ],
];
foreach ($default_fields_terms as $t) {
  $term = reset($tm->loadByProperties(['vid' => $t['vid'], 'name' => $t['name']]));
  if (!$term) {
    $term = $tm->create($t);
    $term->enforceIsNew();
    $term->save();
  }
  $default_fields_config->set($t['setting'], $term->id());
}

$default_fields_config->save();

// Self Deposit Content & Settings.
$self_deposit_config = \Drupal::configFactory()->getEditable('self_deposit.selfdepositsettings');
$collections = [
  [
    'type' => 'collection',
    'title' => 'Collection for self deposits',
    'setting' => 'collection_for_deposits',
  ],
  [
    'type' => 'collection',
    'title' => 'Barrett, The Honors College Thesis/Creative Project Collection',
    'setting' => 'barrett_collection_for_deposits',
  ],
  [
    'type' => 'collection',
    'title' => 'ASU School of Music Performance Archive',
    'setting' => 'perf_archive_default_collection',
  ],
];
foreach ($collections as $c) {
  $collection = reset($nm->loadByProperties([
    'type' => $c['type'],
    'title' => $c['title'],
  ]));
  if (!$collection) {
    $paragraph = $pm->create(
      ['type' => 'complex_title', 'field_main_title' => $c['title']]
    );
    $paragraph->save();
    $c['field_title'] = [[
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ],
    ];
    $collection = $nm->create($c);
    $collection->enforceIsNew();
    $collection->save();
  }
  $self_deposit_config->set($c['setting'], $collection->id());
}

$deposit_terms = [
  [
    'vid' => 'islandora_models',
    'name' => 'Audio',
    'setting' => 'audio_media_model',
  ],
  [
    'vid' => 'islandora_models',
    'name' => 'Image',
    'setting' => 'image_media_model',
  ],
  [
    'vid' => 'islandora_models',
    'name' => 'Video',
    'setting' => 'video_media_model',
  ],
  [
    'vid' => 'islandora_models',
    'name' => 'Binary',
    'setting' => 'file_media_model',
  ],
  [
    'vid' => 'genre',
    'name' => 'Digital Document',
    'setting' => 'document_media_model',
  ],
  [
    'vid' => 'copyright_statements',
    'name' => 'Musical performances',
    'setting' => 'perf_archive_default_genre',
  ],
  [
    'vid' => 'reuse_permissions',
    'name' => 'In Copyright',
    'setting' => 'perf_archive_default_copyright',
  ],
  [
    'vid' => 'reuse_permissions',
    'name' => 'All Rights Reserved',
    'setting' => 'perf_archive_default_reuse',
  ],
  [
    'vid' => 'islandora_models',
    'name' => 'Complex Object',
    'setting' => 'perf_archive_default_model',
  ],
  [
    'vid' => 'identifier_types',
    'name' => 'Locally defined identifier',
    'setting' => 'perf_archive_default_identifier_type',
  ],
];
foreach ($deposit_terms as $t) {
  $term = reset($tm->loadByProperties(['vid' => $t['vid'], 'name' => $t['name']]));
  if (!$term) {
    $term = $tm->create($t);
    $term->enforceIsNew();
    $term->save();
  }
  $self_deposit_config->set($t['setting'], $term->id());
}

$self_deposit_config->save();

$file_system = \Drupal::service('file_system');
foreach (['fedora://c160', 'fedora://c130'] as $directory) {
  $file_system->prepareDirectory($directory, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
}

// All done, close out admin's session.
$switcher->switchBack();
