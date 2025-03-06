<?php

/**
 * @file
 * Transforms a repository item into a scholarly content item.
 */

use Kiwilan\Audio\Audio;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Database\Database;

/**
 * Utility function for purging all but the original OR service file.
 */
function find_keeper_media($node, $io) {
  $iu = \Drupal::service('islandora.utils');
  $service = NULL;
  $original = NULL;
  foreach ($iu->getMedia($node) as $m) {
    if ($m->field_media_use->entity->label() == 'Service File') {
      $service = $m;
    }
    elseif ($m->field_media_use->entity->label() == 'Original File') {
      $original = $m;
    }
    else {
      $io->writeln("\tDelete media {$m->label()} ({$m->id()})");
      $iu->deleteMediaAndFiles([$m]);
    }
  }
  if (!is_null($original) && !is_null($service)) {
    $io->writeln("\tDelete media {$service->label()} ({$service->id()})");
    $iu->deleteMediaAndFiles([$service]);
  }
  return (!is_null($original)) ? $original : $service;
}

// Analytics Service.
$as = \Drupal::service('asu_item_analytics.query');

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

  $n->set('type', 'performance');
  $n->save();
  $n = $ns->load($n->id());

  // Migrate child nodes into track paragraphs.
  $tracks = [];
  foreach ($ns->loadByProperties(['field_member_of' => $n->id()]) as $child) {
    // Program.
    if ($child->field_model?->entity?->label() == 'Digital Document') {
      $this->io()->writeln("We've got a document: {$child->label()}");
      $keeper = find_keeper_media($child, $this->io());
      $n->set('field_program', $keeper);
      $this->io()->writeln("Attaching program: {$keeper->label()}");
    }
    // Audio for the tracks.
    elseif ($child->field_model?->entity?->label() == 'Audio') {
      $this->io()->writeln("We've got a track: {$child->label()}");
      $track = Paragraph::create([
        'type' => 'track',
        'field_title' => strip_tags($child->label(), ['em', 'i', 'strong']),
        'field_linked_agent' => $child->get('field_linked_agent'),
      ]);

      // Engagement counts.
      $count = array_sum($as->entityMonthly($child) ?? []);
      if ($count > 0) {
        $track->set('field_plays', $count);
        $this->io()->writeln("\tEngagement count: {$count}");
      }

      // Add Audio Media reference.
      $keeper = find_keeper_media($child, $this->io());
      if ($keeper) {
        $this->io()->writeln("\tKeeping Audio {$keeper->label()}");
        $track->set('field_audio', $keeper);

        // Grab duration of mp3 for `field_duration`.
        $file = $keeper->get('field_media_audio_file')?->entity->getFileUri();
        $audio_info = Audio::read($file);
        $track->set('field_duration', ceil($audio_info->getDuration()));
        $this->io()->writeln("\tKeeping Audio {$keeper->label()} with duration {$track->field_duration->value}");
      }
      $track->save();
      $tracks[] = [
        'target_id' => $track->id(),
        'target_revision_id' => $track->getRevisionId(),
      ];
      $this->io()->writeln("\tDeleting component \"{$child->label()}\" ({$child->id()})");
      $child->delete();
    }
  }
  $n->set('field_tracks', $tracks);
  $n->save();
}
