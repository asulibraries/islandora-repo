<?php

/**
 * @file
 * Incrementally maintain the REST OAI-PMH record cache.
 *
 * Run with Drush, for example:
 *   OAI_DRY_RUN=1 OAI_BATCH_LIMIT=1000 drush --uri=https://example.org scr /path/to/this/file
 *
 * Environment variables:
 *   OAI_DRY_RUN=1       Report what would happen without writing.
 *   OAI_BATCH_LIMIT=N   Maximum stale cached records to update per run.
 *   OAI_RECENT_LIMIT=N  Recent eligible View members to inspect per View.
 */

use Drupal\views\Views;

$dry_run = filter_var(getenv('OAI_DRY_RUN') ?: FALSE, FILTER_VALIDATE_BOOLEAN);
$batch_limit = (int) (getenv('OAI_BATCH_LIMIT') ?: 1000);
$batch_limit = max(1, min($batch_limit, 10000));
$recent_limit = (int) (getenv('OAI_RECENT_LIMIT') ?: 500);
$recent_limit = max(1, min($recent_limit, 5000));

$database = \Drupal::database();
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$site_host = \Drupal::request()->getHost() ?: 'unknown-site';
$lock_name = 'asu_rest_oai_pmh_recent_update:' . $site_host;
$lock = \Drupal::lock();

if (!$lock->acquire($lock_name, 900.0)) {
  print "Another OAI cache update is already running for {$site_host}; skipping.\n";
  return;
}

try {
  // Repair records already present in the OAI cache whose node timestamps are
  // newer. This addresses updates to any cached node, not only high/new NIDs.
  $stale_query = $database->select('rest_oai_pmh_record', 'r');
  $stale_query->innerJoin(
    'node_field_data',
    'n',
    'n.nid = r.entity_id AND n.default_langcode = 1'
  );
  $stale_query->condition('r.entity_type', 'node');
  $stale_query->where('n.changed > r.changed');

  $stale_total = (int) (clone $stale_query)
    ->countQuery()
    ->execute()
    ->fetchField();

  $stale_query->addField('r', 'entity_id');
  $stale_query->addField('n', 'created', 'node_created');
  $stale_query->addField('n', 'changed', 'node_changed');
  $stale_query->orderBy('n.changed', 'DESC');
  $stale_query->range(0, $batch_limit);
  $stale_rows = $stale_query->execute()->fetchAll();

  printf(
    "%s: %d stale cached record(s); this run will %s %d.\n",
    $site_host,
    $stale_total,
    $dry_run ? 'inspect' : 'update',
    count($stale_rows)
  );

  $updated = 0;
  if (!$dry_run && $stale_rows) {
    $transaction = $database->startTransaction();
    try {
      foreach ($stale_rows as $row) {
        $updated += $database->update('rest_oai_pmh_record')
          ->fields([
            'created' => (int) $row->node_created,
            'changed' => (int) $row->node_changed,
          ])
          ->condition('entity_type', 'node')
          ->condition('entity_id', (int) $row->entity_id)
          ->execute();
      }
    }
    catch (\Throwable $exception) {
      $transaction->rollBack();
      throw $exception;
    }
    unset($transaction);
  }

  if (!$dry_run) {
    printf("Updated %d stale cached record(s).\n", $updated);
  }

  // Continue discovering recent/new members through the configured OAI Entity
  // Reference Views. Never bulk-add all Drupal nodes: the Views define which
  // nodes are actually eligible for each OAI set.
  $view_displays = \Drupal::config('rest_oai_pmh.settings')->get('view_displays') ?: [];
  $recent_seen = 0;
  $recent_record_changes = 0;
  $recent_member_changes = 0;
  $invalid_ids = 0;

  foreach ($view_displays as $view_display) {
    if (!is_string($view_display) || !str_contains($view_display, ':')) {
      throw new \RuntimeException('Invalid OAI View display configuration.');
    }

    [$view_id, $display_id] = explode(':', $view_display, 2);
    $view = Views::getView($view_id);
    if (!$view) {
      throw new \RuntimeException("OAI View '{$view_id}' does not exist.");
    }

    $view->setDisplay($display_id);
    $view->setItemsPerPage($recent_limit);
    $view->getDisplay()->setOption('entity_reference_options', ['limit' => $recent_limit]);
    $members = $view->executeDisplay($display_id);

    if (!is_array($members)) {
      throw new \RuntimeException("OAI View display '{$view_display}' returned an unexpected result.");
    }

    // Entity Reference displays return an array keyed by entity ID.
    $member_ids = [];
    foreach (array_keys($members) as $member_id) {
      if (is_numeric($member_id) && (int) $member_id > 0) {
        $member_ids[] = (int) $member_id;
      }
      else {
        $invalid_ids++;
      }
    }

    $member_ids = array_values(array_unique($member_ids));
    $entities = $node_storage->loadMultiple($member_ids);
    printf(
      "%s: found %d recent node(s) in the first View page.\n",
      $view_display,
      count($entities)
    );

    $existing_records = [];
    $existing_members = [];
    if ($member_ids) {
      $record_query = $database->select('rest_oai_pmh_record', 'r');
      $record_query->fields('r', ['entity_id', 'created', 'changed']);
      $record_query->condition('r.entity_type', 'node');
      $record_query->condition('r.entity_id', $member_ids, 'IN');
      foreach ($record_query->execute() as $record) {
        $existing_records[(int) $record->entity_id] = $record;
      }

      $member_query = $database->select('rest_oai_pmh_member', 'm');
      $member_query->addField('m', 'entity_id');
      $member_query->condition('m.entity_type', 'node');
      $member_query->condition('m.set_id', $view_display);
      $member_query->condition('m.entity_id', $member_ids, 'IN');
      $existing_members = array_fill_keys(
        array_map('intval', $member_query->execute()->fetchCol()),
        TRUE
      );
    }

    foreach ($entities as $entity) {
      $recent_seen++;
      $entity_id = (int) $entity->id();
      $created = $entity->hasField('created')
        ? (int) $entity->get('created')->value
        : \Drupal::time()->getRequestTime();
      $changed = $entity->hasField('changed')
        ? (int) $entity->get('changed')->value
        : \Drupal::time()->getRequestTime();

      $cached = $existing_records[$entity_id] ?? NULL;
      $record_needs_change = !$cached
        || (int) $cached->created !== $created
        || (int) $cached->changed !== $changed;

      if ($record_needs_change) {
        $recent_record_changes++;
        if (!$dry_run) {
          $database->merge('rest_oai_pmh_record')
            ->keys(
              ['entity_type', 'entity_id'],
              ['node', $entity_id]
            )
            ->fields([
              'created' => $created,
              'changed' => $changed,
            ])
            ->execute();
        }
      }

      if (!isset($existing_members[$entity_id])) {
        $recent_member_changes++;
        if (!$dry_run) {
          $database->merge('rest_oai_pmh_member')
            ->keys(
              ['entity_type', 'entity_id', 'set_id'],
              ['node', $entity_id, $view_display]
            )
            ->execute();
        }
      }
    }

    $view->destroy();
  }

  printf(
    "%s complete: dry_run=%s, stale_total=%d, stale_updated=%d, recent_seen=%d, recent_record_changes=%d, recent_member_changes=%d, invalid_view_ids=%d.\n",
    $site_host,
    $dry_run ? 'yes' : 'no',
    $stale_total,
    $updated,
    $recent_seen,
    $recent_record_changes,
    $recent_member_changes,
    $invalid_ids
  );
}
catch (\Throwable $exception) {
  \Drupal::logger('rest_oai_pmh_recent_update')->error(
    'OAI cache update failed: @message',
    ['@message' => $exception->getMessage()]
  );
  fwrite(STDERR, "OAI cache update failed: {$exception->getMessage()}\n");
  exit(1);
}
finally {
  $lock->release($lock_name);
}

