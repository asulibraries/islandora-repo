<?php

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;

/**
 *
 */
function export_entity($e, &$context) {
  print("Processing {$e->id()} {$e->label()}\n");
  $key = "{$e->getEntityTypeId()}-{$e->id()}";
  // Export Fields.
  foreach ($e->getFieldDefinitions() as $f => $fd) {
    if (in_array($f, ['vid', 'uuid'])) {
      continue;
    }
    $values = $e->get($f)->getValue();
    if ($e->get($f) instanceof EntityReferenceFieldItemListInterface) {
      // Save away taxonomy reference.
      if ($e->get($f)->entity && $e->get($f)->entity?->getEntityTypeId() == 'taxonomy_term') {
        foreach ($e->get($f) as $ref) {
          if (array_key_exists($ref->target_id, $context['terms'])) {
            continue;
          }
          $context['terms'][$ref->target_id] = [$ref->entity->vid, $ref->entity->label(), \Drupal::service('islandora.utils')->getUriForTerm($ref->entity)];
        }
      }
      // Process file references.
      if ($e->get($f)->entity?->getEntityTypeId() == 'file') {
        foreach ($e->get($f) as $delta => $ref) {
          $values[$delta] = $ref->entity->uri->value;
        }
      }
      // TODO: process paragraphs.
      else {
        \Drupal::logger('export')->warning("Can't map {$e->get($f)->entity?->getEntityTypeId()} in {$f} for {$e->id()} yet.");
      }
    }
    $context[$key][$f] = $values;
  }
  if ($e->getEntityTypeId() == 'node') {
    // Recurse Media.
    foreach (\Drupal::service('islandora.utils')->getMedia($e) as $m) {
      export_entity($m, $context);
    }

    // Recurse Members.
    foreach (\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_member_of' => $e->id()]) as $m) {
      export_entity($m, $context);
    }
  }
}

$path = $extra[0];

if (!is_dir($path) || !is_writable($path)) {
  $message = "The path {$path} is either not a directory or not writable.";
  $this->io()->error($message);
  die($message);
}

$nid = $extra[1];
$ns = \Drupal::entityTypeManager()->getStorage('node');
if (!$source = $ns->load($nid)) {
  $this->io()->error("Could not load item $nid");
  die("");
}

$context = ['items' => [], 'terms' => []];
export_entity($source, $context);
file_put_contents($path . DIRECTORY_SEPARATOR . "{$nid}.json", json_encode($context, JSON_PRETTY_PRINT));