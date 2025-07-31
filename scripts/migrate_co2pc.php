<?php

/**
 * @file
 * Transforms complex objects into paged ones.
 */

use Drupal\Core\Database\Database;

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

$image_term = reset(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
  'name' => 'Image',
  'vid' => 'islandora_models',
]));
$paged_content_term = reset(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
  'name' => 'Paged Content',
  'vid' => 'islandora_models',
]));
$page_term = reset(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
  'name' => 'Page',
  'vid' => 'islandora_models',
]));

foreach ($nids as $nid) {
  $n = $ns->load($nid);
  if (!$n) {
    $this->io()->writeln("Skipping {$nid}; it couldn't be loaded.");
    continue;
  }
  if ($n->hasField('field_model') && !$n->get('field_model')->isEmpty() && $n->field_model->entity->label() != 'Complex Object') {
    $this->io()->writeln("Skipping non-complex-object \"{$n->label()}\" ({$n->id()})");
    continue;
  }
  // Skip items where the member has their own members.
  $query = $db->select('node__field_member_of', 'g');
  $query->join('node__field_member_of', 'i', 'i.entity_id = g.field_member_of_target_id');
  $query->condition('i.field_member_of_target_id', $n->id());
  $grandchild_count = $query->countQuery()->execute()->fetchField();
  if ($grandchild_count > 0) {
    $this->io()->writeln("Skipping \"{$n->label()}\" ({$n->id()}) which has {$grandchild_count} grandchild items.");
    continue;
  }
  // Skip items where one or more children are not an Image.
  $query = $db->select('node__field_member_of', 'i');
  $query->join('node__field_model', 'm', 'i.entity_id = m.entity_id');
  $query->condition('i.field_member_of_target_id', $n->id());
  $query->condition('m.field_model_target_id', $image_term->id(), '<>');
  $non_image_count = $query->countQuery()->execute()->fetchField();
  if ($non_image_count > 0) {
    $this->io()->writeln("Skipping \"{$n->label()}\" ({$n->id()}) which has {$non_image_count} non-image items.");
    continue;
  }
  // Change the item's `field_model` to 'Paged Content'.
  $this->io()->writeln("Updating \"{$n->label()}\" ({$n->id()}) to 'Paged Content'");
  $n->set('field_model', $paged_content_term);
  $n->save();

  // Change all the children's `field_model` to 'Page'.
  foreach ($ns->loadByProperties(['field_member_of' => $n->id()]) as $member) {
    $this->io()->writeln("\tupdating \"{$member->label()}\" ({$member->id()}) to 'Page'");
    $member->set('field_model', $page_term);
    $member->save();
  }

}
