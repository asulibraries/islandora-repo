<?php

/**
 * @file
 * Export a node and its related entities to JSON.
 *
 * Usage: drush scr islandora_export.php /path/to/export/dir nid.
 *
 * KEEP Collection 259 is a useful small test case.
 */

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Saves an entity to the context array.
 */
function export_entity($e, &$context) {
  if (array_key_exists($e->id(), $context[$e->getEntityTypeId()] ?? [])) {
    print("Skipping existing {$e->getEntityTypeId()}:{$e->id()}\n");
    return;
  }
  print("Processing {$e->getEntityTypeId()}:{$e->id()} {$e->label()}\n");

  // Export Fields.
  foreach ($e->getFieldDefinitions() as $f => $fd) {
    // Skip site-specific fields or empty.
    // NOTE: vid is used for the bundle for taxonomy terms,
    // so we keep it even though it is used as a version id for nodes.
    if (in_array($f, [
      'uuid',
      'metatag',
      'revision_id',
      'revision_timestamp',
      'revision_uid',
      'revision_log',
      'revision_default',
      'revision_translated_affected',
      'revision_created',
      'menu_link',
      'content_translation_source',
      'content_translation_outdated',
      'revision_translation_affected',
      'path',
      'thumbnail',
    ]) || $e->get($f)->isEmpty()) {
      continue;
    }
    $values = $e->get($f)->getValue();
    // Base fields and non-entity reference fields can be exported directly.
    if ($fd instanceof FieldConfig && $e->get($f) instanceof EntityReferenceFieldItemListInterface) {
      if (!$field_storage = FieldStorageConfig::loadByName($e->getEntityTypeId(), $f)) {
        print("WARNING:Field storage for {$e->getEntityTypeId()}:{$f} not found for {$e->id()}\n");
        continue;
      }
      switch ($target_type = $field_storage->getSetting('target_type')) {
        case 'file':
          foreach ($e->get($f) as $delta => $ref) {
            $values[$delta] = [
              'type' => $target_type,
              'uri' => $ref->entity->uri->value,
            ];
          }
          break;

        case 'taxonomy_term':
        case 'media':
        case 'paragraph':
          foreach ($e->get($f) as $delta => $ref) {
            if (!$ref->entity) {
              print("WARNING:Could not find entity {$target_type}:{$ref->target_id} for {$e->getEntityTypeId()}:{$e->id()}:{$f}\n");
              continue;
            }
            export_entity($ref->entity, $context);
          }
          break;

        case 'node':
          // Don't recurse nodes, they are handled separately.
          break;

        default:
          print("WARNING:Can't map {$e->get($f)->entity?->getEntityTypeId()} in {$f} for {$e->id()} yet.\n");
      }
    }
    // With the exception of users.
    if ($f == 'uid') {
      $values = $e->get($f)->entity->getAccountName() ?? '';
    }
    // And the taxonomy term vocabulary.
    if ($f == 'vid' && $e->getEntityTypeId() == 'taxonomy_term') {
      $values = $e->get($f)->getValue();
    }
    $context[$e->getEntityTypeId()][$e->id()][$f] = $values;
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

$context = [];
foreach (['public', 'private'] as $scheme) {
  if ($real_path = \Drupal::service('file_system')->realpath("{$scheme}://")) {
    $context['settings'][$scheme] = $real_path;
  }
  else {
    $this->io()->warning("Could not find real path for {$scheme}://");
    $context['settings'][$scheme] = '';
  }
}
$context['node'] = [];
export_entity($source, $context);
file_put_contents($path . DIRECTORY_SEPARATOR . "{$nid}.json", json_encode($context, JSON_PRETTY_PRINT));
