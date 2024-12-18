<?php

/**
 * @file
 * Process OAI Queue.
 *
 * Rebuilding the oai index sometimes fails on rebuild.
 * This script will process the outstanding queue items.
 */

$queue = \Drupal::service('queue')->get('rest_oai_pmh_views_cache_cron');

while ($item = $queue->claimItem()) {
  print("{$item->data['set_id']} {$item->data['offset']}\n");
  rest_oai_pmh_process_queue($item);
}
