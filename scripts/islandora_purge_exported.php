<?php

/**
 * @file
 * Purges the items previously exported by islandora_export.php.
 */

use Drupal\system\Entity\Action;
use Drupal\user\Entity\User;

// Drush's php:cli runs as annonymous, so we switch to a user with rights
// to use the islandora microservices. I used admin.
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo(User::load(1));

$delete = Action::load('delete_node_and_media');

if (!$delete) {
  return "The action 'delete_node_and_media' does not exist.";
}

$path = $extra[0];

if (!is_readable($path)) {
  return "The path {$path} is not readable.";
}

$import = json_decode(file_get_contents($path), TRUE);

$nids = array_keys($import['node'] ?? []);
$count = count($nids);

$answer = strtolower($this->io()->ask("Are you sure you want to purge $count nodes with their associated media and files? [a/y/N]", 'N'));

if ($answer == 'n' || $answer == '') {
  return;
}

$to_purge = [];
if ($answer == 'y') {
  $to_purge = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
}
elseif ($answer == 'a') {
  foreach ($nids as $nid) {
    $n = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if ($this->io()->confirm("Purge node \"{$n->label()}\" ({$nid}) and its associated media and files?", FALSE)) {
      $to_purge[] = $n;
    }
  }
}
$selected_count = count($to_purge);
$this->io()->writeln("Purging {$selected_count} nodes and their associated media and files.");
$delete->execute($nodes);

// All done, close out admin's session.
$switcher->switchBack();
