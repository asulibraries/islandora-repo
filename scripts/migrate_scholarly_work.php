<?php

/**
 * @file
 * Transforms a repository item into a scholarly content item.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;

/**
 * Utility function for purging all but the original file.
 */
function return_original_purge_others($node, $io) {
  $iu = \Drupal::service('islandora.utils');
  $keeper = NULL;
  foreach ($iu->getMedia($node) as $m) {
    if ($m->field_media_use->entity->label() !== 'Original File') {
      $io->writeln("\tDelete media {$m->label()} ({$m->id()})");
      foreach (array_map(fn($field) => substr($field, stripos($field, '.') + 1), $iu->getReferencingFields('media', 'file')) as $field_name) {
        if ($m->hasField($field_name) && !$m->{$field_name}->isEmpty() && $file = $m->{$field_name}->entity) {
          $io->writeln("\tDelete File {$file->label()} ({$file->getFileUri()})");
          $file->delete();
        }
      }
      $m->delete();
    }
    else {
      $io->writeln("\tKeep media {$m->label()} ({$m->id()})");
      $keeper = $m;
    }
  }
  return $keeper;
}

$nid = $extra[0];
$ns = \Drupal::entityTypeManager()->getStorage('node');
if (!$source = $ns->load($nid)) {
  $this->io()->error("Could not load item $nid");
  return;
}
$nodes = [];
if ($source->bundle() == 'collection') {
  $members = $ns->loadByProperties(['type' => 'asu_repository_item', 'field_member_of' => $source->id()]);
  $count = (count($extra) > 1) ? $extra[1] : count($members);
  $total = count($members);
  $nodes = array_slice($members, 0, $count);
  $this->io()->writeln("Processing $count of $total nodes in {$source->label()}");
}
else {
  $nodes = [$source];
}

$db = Database::getConnection();

$fields_to_clear = [
  'field_subjects_name',
  'field_name_title_subject',
  'field_date_digitized',
  'field_frequency',
  'field_issuance',
  'field_complex_object_child',
  'field_model',
  'field_coordinates',
  'field_place_of_publication_code',
  'field_default_derivative_file_pe',
  'field_default_original_file_perm',
  'field_description_source',
  'field_cataloging_standards',
];
$fields_to_fix = [
  'field_additional_memberships',
  'field_complex_subject',
  'field_copyright_statement',
  'field_display_hints',
  'field_edition',
  'field_edtf_copyright_date',
  'field_edtf_date_created',
  'field_embargo_release_date',
  'field_extent',
  'field_genre',
  'field_geographic_subject',
  'field_handle',
  'field_history',
  'field_internal_note',
  'field_keyword',
  'field_language',
  'field_level_of_coding',
  'field_linked_agent',
  'field_member_of',
  'field_note_para',
  'field_oai_set',
  'field_open_access',
  'field_peer_reviewed',
  'field_pid',
  'field_place_published',
  'field_preferred_citation',
  'field_preservation_state',
  'field_related_item',
  'field_resource_type',
  'field_reuse_permissions',
  'field_rich_description',
  'field_series',
  'field_source',
  'field_statement_responsibility',
  'field_subject',
  'field_table_of_contents',
  'field_temporal_subject',
  'field_title',
  'field_title_subject',
  'field_typed_identifier',
  'field_weight',
];

foreach ($nodes as $n) {
  if ($n->bundle() !== 'asu_repository_item') {
    $this->io()->writeln("Skipping {$n->bundle()} \"{$n->label()}\" ({$n->id()})");
    continue;
  }
  $this->io()->writeln("Processing \"{$n->label()}\" ({$n->id()})");

  // Clear fields we don't use in the new model.
  foreach ($fields_to_clear as $field) {
    $db->delete('node__' . $field)
      ->condition('entity_id', $n->id())
      ->execute();
  }

  // Cribbing from convert_bundles the database changes.
  // We should probably do this in batches for efficiency's sake,
  // but I hesitate since moving media becomes a separate step that
  // would be delayed for a potentially significant period of time
  // and possibly orphan some items if we have a failure mid-way.
  // Change the base table.
  $db->update('node')
    ->fields(['type' => 'scholarly_work'])
    ->condition('nid', $n->id())
    ->execute();
  $db->update('node_field_data')
    ->fields(['type' => 'scholarly_work'])
    ->condition('nid', $n->id())
    ->execute();

  // Change each field bundle reference.
  foreach ($fields_to_fix as $field) {
    $db->update('node__' . $field)
      ->fields(['bundle' => 'scholarly_work'])
      ->condition('entity_id', $n->id())
      ->execute();
  }

  // Reverse the media→node directionality and pull them from children
  // on complex objects.
  $work_products = [];
  $work_products[] = return_original_purge_others($n, $this->io());
  foreach ($ns->loadByProperties(['field_member_of' => $n->id()]) as $child) {
    $work_products[] = return_original_purge_others($child, $this->io());
    $this->io()->writeln("\tDeleting component \"{$child->label()}\" ({$child->id()})");
    $child->delete();
  }
  foreach (array_map(fn($m) => $m->id(), $work_products) as $delta => $mid) {
    $db->insert('node__field_work_products')->fields([
      'bundle' => 'scholarly_work',
      'entity_id' => $n->id(),
      'revision_id' => $n->getRevisionId(),
      'delta' => $delta,
      'langcode' => 'en',
      'field_work_products_target_id' => $mid,
    ])->execute();
  }
  Cache::invalidateTags($n->getCacheTags());
}
drupal_flush_all_caches();
