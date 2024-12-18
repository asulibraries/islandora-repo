<?php

/**
 * @file
 * Transforms a repository item into a scholarly content item.
 */

use Drupal\Core\Database\Database;

/**
 * Utility function for purging all but the original file.
 *
 * KEEP-dev is having issues with generating new thumbnails, so
 * we'll just grab the existing one.
 */
function return_original_purge_others($node, $io) {
  $iu = \Drupal::service('islandora.utils');
  $keeper = NULL;
  $thumbnail = NULL;
  foreach ($iu->getMedia($node) as $m) {
    if ($m->field_media_use->entity->label() !== 'Original File') {
      $io->writeln("\tDelete media {$m->label()} ({$m->id()})");
      foreach (array_map(fn($field) => substr($field, stripos($field, '.') + 1), $iu->getReferencingFields('media', 'file')) as $field_name) {
        if ($m->hasField($field_name) && !$m->{$field_name}->isEmpty() && $file = $m->{$field_name}->entity) {
          if ($m->field_media_use->entity->label() == 'Thumbnail Image') {
            $io->writeln("\tGrabbing Thumbnail File {$file->label()} ({$file->getFileUri()})");
            $thumbnail = [['target_id' => $file->id()]];
          }
          else {
            $io->writeln("\tDelete File {$file->label()} ({$file->getFileUri()})");
            $file->delete();
          }
        }
      }
      $m->delete();
    }
    else {
      $io->writeln("\tKeep media {$m->label()} ({$m->id()})");
      $keeper = $m;
    }
  }
  if ($keeper) {
    $keeper->set('thumbnail', $thumbnail);
    $keeper->save();
    return $keeper;
  }
  return NULL;
}

// Identify the item or collection to migrate.
$nid = $extra[0];
$limit = (array_key_exists(1, $extra)) ? $extra[1] : -1;
$ns = \Drupal::entityTypeManager()->getStorage('node');
if (!$source = $ns->load($nid)) {
  $this->io()->error("Could not load item $nid");
  return;
}
$nids = [];
$db = Database::getConnection();
if ($source->bundle() == 'collection') {
  $query = $db->select('node__field_member_of', 'mo')
    ->condition('mo.bundle', 'asu_repository_item')
    ->condition('mo.field_member_of_target_id', $source->id());
  if ($limit > 0) {
    $query->range(0, $limit);
  }
  $query->addField('mo', 'entity_id');
  $nids = $query->execute()->fetchCol();
  $count = count($nids);
  $this->io()->writeln("Processing $count nodes in {$source->label()}");
}
else {
  $nids = [$nid];
}


foreach ($nids as $nid) {
  $n = $ns->load($nid);
  if (!$n) {
    $this->io()->writeln("Skipping {$nid}; it couldn't be loaded.");
    continue;
  }
  if ($n->bundle() !== 'asu_repository_item') {
    $this->io()->writeln("Skipping {$n->bundle()} \"{$n->label()}\" ({$n->id()})");
    continue;
  }
  $this->io()->writeln("Processing \"{$n->label()}\" ({$n->id()})");

  $n->set('type', 'scholarly_work');
  $n->save();

  $n = $ns->load($n->id());
  // Reverse the media→node directionality and pull them from children
  // on complex objects.
  $work_products = [];
  $work_products[] = return_original_purge_others($n, $this->io());
  // @todo move analytics counts for child(ren) to the parent.
  // We need to do it during migration before we lose track of child items.
  foreach ($ns->loadByProperties(['field_member_of' => $n->id()]) as $child) {
    // The media name is usually the name of the item whereas
    // the child component is the filename, which is what we want.
    $keeper = return_original_purge_others($child, $this->io());
    if ($keeper) {
      $keeper->set('name', $child->label())->save();
      $work_products[] = $keeper;
    }
    $this->io()->writeln("\tDeleting component \"{$child->label()}\" ({$child->id()})");
    $child->delete();
  }
  $n->set('field_work_products', array_map(fn($m) => ['target_id' => $m->id()], array_filter($work_products)));
  $n->save();
}
