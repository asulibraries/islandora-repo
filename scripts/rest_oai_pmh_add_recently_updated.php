<?php

/**
 * @file
 * Add recent items to rest_oai_pmh cache.
 *
 * The liberal and ocnservative caching strategy for rest_oai_pmh flush the
 * cache and rebuild. This is unsustainable for our large repo. This script
 * grabs the first page of items and ensures they are cached.
 */

use Drupal\views\Views;

$db = \Drupal::database();
// If no view_displays were passed
// get a list of all view displays set for OAI-PMH.
$config = \Drupal::config('rest_oai_pmh.settings');
$view_displays = $config->get('view_displays') ?: [];
$node_storage = \Drupal::entityTypeManager()->getStorage('node');

foreach ($view_displays as $view_display) {
  [$view_id, $display_id] = explode(':', $view_display);

  // Grab the most recent item in the cache so we can skip the ones before it.
  $top_entity_result = $db->query("SELECT r.entity_id FROM rest_oai_pmh_record AS r JOIN rest_oai_pmh_member AS m ON r.entity_id = m.entity_id WHERE set_id = '$view_display' ORDER BY created DESC LIMIT 1")->fetchObject();
  if (!$top_entity_result) {
    continue;
  }

  // Load the View and apply the display ID.
  $view = Views::getView($view_id);
  $view->setDisplay($display_id);
  $view->getDisplay()->setOption('entity_reference_options', ['limit' => $view->getItemsPerPage()]);

  $members = $view->executeDisplay($display_id);
  foreach ($members as $id => $row) {
    if ($top_entity_result->entity_id >= $id) {
      continue;
    }
    // Init the variables used for the UPSERT database call
    // to add/update this RECORD.
    $merge_keys = [
      'entity_type',
      'entity_id',
    ];
    $merge_values = [
      'node',
      $id,
    ];
    // Load the entity, partly to ensure it exists
    // also to get the changed/created properties.
    $entity = $node_storage->load($id);
    if ($entity) {
      // Upsert the record into our cache table.
      $db->merge('rest_oai_pmh_record')
        ->keys($merge_keys, $merge_values)
        ->fields(
        [
          'created' => $entity->hasField('created') ? $entity->created->value : $created,
          'changed' => $entity->hasField('changed') ? $entity->changed->value : \Drupal::time()->requestTime(),
        ]
        )->execute();
      // Add this record to the respective set.
      $merge_keys[] = 'set_id';
      $merge_values[] = $view_display;
      $db->merge('rest_oai_pmh_member')
        ->keys($merge_keys, $merge_values)
        ->execute();

    }
  }
}
