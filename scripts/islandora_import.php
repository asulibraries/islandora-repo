<?php

/**
 * @file
 */

use Drupal\field\Entity\FieldStorageConfig;

/**
 *
 */
function import_entity($type, $id, &$import) {
  if (array_key_exists($id, $import['map'][$type] ?? [])) {
    print("Entity {$type}:{$id} found in map as {$import['map'][$type][$id]}.\n");
    return $import['map'][$type][$id];
  }
  print("Importing {$type}:{$id}.\n");
  // Pre-process the fields to resolve mapped references.
  if (!array_key_exists($id, $import[$type] ?? [])) {
    print("WARNING: No entity data found for {$type}:{$id}.\n");
    return NULL;
  }
  $fields = $import[$type][$id];
  // Paragraph fields need to be processed after the entity is created.
  // We will store a list of them for later processing.
  $paragraph_fields = [];
  foreach ($fields as $f => $values) {
    // Skip empty fields.
    if (empty($values)) {
      unset($fields[$f]);
      continue;
    }
    // print("Processing field {$f} for {$type}:{$id}.\n");
    // Base fields don't have field storage configs.
    if (!$field_storage = FieldStorageConfig::loadByName($type, $f)) {
      // Only user id needs to be resolved.
      if ($f == 'uid') {
        // Lookup the user by name.
        if ($user = current(\Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $values]))) {
          $fields[$f] = ['target_id' => $user->id()];
        }
        // Drop the userid if the user doesn't exist in this system.
        // It will default to the user running the import.
        else {
          print("WARNING: Could not find user {$values} for {$type}:{$id}\n");
          unset($fields[$f]);
        }
      }
      continue;
    }
    // Lookup/import new entity references.
    switch ($target_type = $field_storage->getSetting('target_type')) {
      case 'file':
        // @todo copy the file to the new location.
        print("WARNING: Skipping File `{$values[0]['uri']}` for now.\n");
        foreach ($values as $delta => $value) {
          $source_path = str_replace([
            'public://',
            'private://',
          ], [
            $import['settings']['public'] . DIRECTORY_SEPARATOR,
            $import['settings']['private'] . DIRECTORY_SEPARATOR,
          ], $value['uri']);
          // @todo copy the file to the new location.
          // @todo change path/filename for derivatives as the old ID in the name is no longer valid.
          $name = basename($source_path);
          if (!is_readable($source_path)) {
            print("WARNING: File {$source_path} is not readable.\n");
          } else {
            print("COPYING file {$source_path} to {$value['uri']}\n");
          }
          
        }
        break;

      case 'taxonomy_term':
      case 'media':
        $new_values = [];
        foreach ($values as $delta => $value) {
          print("Processing {$target_type} reference {$value['target_id']} in field {$f}.\n");
          // Skip self-references.
          if ($target_type == $type && $value['target_id'] == $id) {
            print("Skipping self-reference for {$type}:{$id} in field {$f}.\n");
            continue;
          }
          if ($new_target_id = import_entity($target_type, $value['target_id'], $import)) {
            $new_values[] = ['target_id' => $new_target_id];
          }
          else {
            print("WARNING: Could not import {$target_type}:{$value['target_id']} for {$type}:{$id}\n");
          }
        }
        $fields[$f] = $new_values;
        break;

      case 'node':
        // Node references tend to make circular references,
        // so we look them up and report missing ones.
        $new_values = [];
        foreach ($values as $delta => $value) {
          if (array_key_exists($value['target_id'], $import['map']['node'])) {
            $new_values[] = ['target_id' => $import['map']['node'][$value['target_id']]];
          }
          else {
            print("WARNING: Could not find node mapping {$value['target_id']} for {$type}:{$id} in field {$f}\n");
          }
        }
        $fields[$f] = $new_values;
        break;

      case 'paragraph':
        // Punt paragraphs until after node creation.
        $paragraph_fields[] = $f;
        break;
    }
  }
  // @todo create the entity.
  // In the meantime, just return a dummy id.
  $import['map'][$type][$id] = rand(1000, 9999);
  // Add paragraph fields.
  if (!empty($paragraph_fields)) {
    foreach ($paragraph_fields as $f) {
      foreach ($fields[$f] as $delta => $value) {
        $pid = import_entity('paragraph', $value['target_id'], $import);
        print("Add Paragraph `\$new_paragraph = Paragraph::load($pid);\$entity->get(\$f)->append(\$new_paragraph);` to {$type}:{$import['map'][$type][$id]}.\n");
      }
    }
  }
  // Load the node's media.
  print("Setting import map for {$type}:{$id} to {$import['map'][$type][$id]}.\n");
  if ($type == 'node') {
    print("Importing node's media.\n");
    foreach (array_keys($import['media']) as $mid) {
      if ($import['media'][$mid]['field_media_of'][0]['target_id'] == $id) {
        $new_m_id = import_entity('media', $mid, $import);
        if (!$new_m_id) {
          print("WARNING: Cound not import media '{$m['name'][0]['value']}' {$m['mid']}.\n");
        }
      }
    }
  }
  return $import['map'][$type][$id];
}

$path = $extra[0];

if (!is_readable($path)) {
  $message = "The path {$path} is not readable.";
  die($message);
}

$import = json_decode(file_get_contents($path), TRUE);
$import['map'] = ['node' => []];
foreach ($import['node'] as $nid => $fields) {
  $new_nid = import_entity('node', $nid, $import);
  print("Imported node '{$fields['title'][0]['value']}' {$nid} as {$new_nid}.\n");
}
// @todo print TSV of the import map.
