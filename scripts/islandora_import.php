<?php

/**
 * @file
 * Islandora import script.
 */

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Updates an existing handle for a node.
 */
function update_handle($n) {
  if ($n->hasField('field_handle') && $hdl = $n->get('field_handle')->value) {
    $path = substr(parse_url($hdl)['path'], 1);
    $config = \Drupal::config('hdl.settings');
    $host = \Drupal::request()->getSchemeAndHttpHost();
    $url = $host . $n->toUrl()->toString();
    $admin_handle = $config->get('hdl_admin_handle');
    $handle_admin_index = $config->get('hdl_admin_index');
    $endpoint_url = $config->get('hdl_handle_api_endpoint');
    $permissions = $config->get('hdl_handle_permissions');
    $password = $config->get('hdl_handle_basic_auth_password');
    $handle_json = [
      [
        'index' => 1,
        'type' => "URL",
        'data' => [
          'format' => "string",
          'value' => $url,
        ],
      ],
      [
        'index' => 100,
        'type' => 'HS_ADMIN',
        'data' => [
          'format' => 'admin',
          'value' => [
            'handle' => $admin_handle,
            'index' => $handle_admin_index,
            'permissions' => $permissions,
          ],
        ],
      ],
    ];

    $client = \Drupal::httpClient();
    try {
      $request = $client->request('PUT', $endpoint_url . $path . "?overwrite=true", [
        'json' => $handle_json,
        'auth' => [
          $handle_admin_index . '%3A' . $admin_handle, $password,
        ],
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
      ]);
      \Drupal::logger('persistent identifiers')->info(print_r($request, TRUE));
      print("Updated handle {$hdl} to {$url}.\n");
      return;
    }
    catch (ClientException $e) {
      \Drupal::logger('persistent identifiers')->error(print_r($e, TRUE));
    }
    catch (ConnectionException $e) {
      \Drupal::logger('persistent identifiers')->erorr(print_r($e, TRUE));
    }
    print("FAILED TO UPDATE HANDLE {$hdl} to {$url}.\n");
  }
}

/**
 * Imports the provided entity.
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

  // Check if taxonomy entities exist.
  if ($type == 'taxonomy_term' &&
  $term = current(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
    'name' => $fields['name'][0]['value'],
    'vid' => $fields['vid'][0]['target_id'],
  ]))) {
    print("Found term '{$term->label()}' {$term->id()} for {$type}:{$id} '{$fields['name'][0]['value']}'.\n");
    $import['map'][$type][$id] = $term->id();
    return $import['map'][$type][$id];
  }

  // Paragraph fields need to be processed after the entity is created.
  // We will store a list of them for later processing.
  $paragraph_fields = [];
  foreach ($fields as $f => $values) {
    // Skip empty fields.
    if (empty($values)) {
      unset($fields[$f]);
      continue;
    }

    // Shift name from array to just being the value.
    if (in_array($f, ['name', 'title'])) {
      $fields[$f] = $fields[$f][0]['value'];
      continue;
    }
    // Shift bundle from reference to just being the value.
    if ($f == 'bundle') {
      $fields[$f] = $fields[$f][0]['target_id'];
      continue;
    }
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
        // Copy the file to the new location.
        foreach ($values as $delta => $value) {
          $source_path = str_replace([
            'public://',
            'private://',
          ], [
            $import['settings']['public'] . DIRECTORY_SEPARATOR,
            $import['settings']['private'] . DIRECTORY_SEPARATOR,
          ], $value['uri']);

          // Change path/filename for derivatives as
          // the old ID is no longer valid.
          $dest_path = $value['uri'];
          if (array_key_exists('field_media_of', $fields) && array_key_exists($fields['field_media_of'][0]['target_id'], $import['map']['node'])) {
            $new_nid = $import['map']['node'][$fields['field_media_of'][0]['target_id']];
            $dest_path = str_replace("/{$fields['field_media_of'][0]['target_id']}-", "/{$new_nid}-", $dest_path);
          }
          if (!is_readable($source_path)) {
            print("WARNING: File {$source_path} is not readable.\n");
          }
          else {
            try {
              \Drupal::service('file_system')->prepareDirectory(dirname($dest_path), FileSystemInterface::CREATE_DIRECTORY);
              \Drupal::service('file_system')->copy($source_path, $dest_path, FileExists::Replace);
              if (!file_exists($dest_path)) {
                print("ERROR: FAILED TO COPY {$source_path} to {$dest_path}\n");
              }
              else {
                $file = \Drupal::entityTypeManager()->getStorage('file')->create([
                  'filename' => basename($dest_path),
                  'uri' => $dest_path,
                  'status' => 1,
                ]);
                $file->save();
                $fields[$f][$delta] = [
                  'target_id' => $file->id(),
                ];
                print("COPIED file {$file->id()} {$source_path} to {$dest_path}\n");
              }
            }
            catch (FileExistsException $e) {
              print("ERROR: FAILED TO COPY {$source_path} to {$dest_path} EXISTS\n");
            }
            catch (FileException $e) {
              print("ERROR: FAILED TO COPY {$source_path} to {$dest_path}\n");
            }
          }

        }
        break;

      case 'taxonomy_term':
      case 'media':
        $new_values = [];
        foreach ($values as $value) {
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
        // We need media to resolve media of references.
        if ($type == 'media' && $f == 'field_media_of') {
          $fields[$f] = import_entity('node', $values[0]['target_id'], $import);
        }

        // Node references tend to make circular references,
        // so we look them up and report missing ones.
        $new_values = [];
        foreach ($values as $value) {
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
  // Create the entity.
  // Unset site-specific fields.
  // Type ids.
  unset($fields[substr($type, 0, 1) . 'id']);
  if ($type == 'paragraph') {
    unset($fields['id']);
    unset($fields['parent_id']);
    unset($fields['revision_id']);
  }
  // Version id.
  if ($type != 'taxonomy_term') {
    unset($fields['vid']);
  }

  $entity = \Drupal::entityTypeManager()->getStorage($type)->create(array_filter($fields, function ($k) use ($paragraph_fields) {
    return !in_array($k, $paragraph_fields);
  }, ARRAY_FILTER_USE_KEY));
  $entity->save();
  $import['map'][$type][$id] = $entity->id();
  print("Setting import map for {$type}:{$id} to {$import['map'][$type][$id]}.\n");

  // Add paragraph fields.
  if (!empty($paragraph_fields)) {
    foreach ($paragraph_fields as $f) {
      $paragraph_references = [];
      foreach ($fields[$f] as $value) {
        $pid = import_entity('paragraph', $value['target_id'], $import);
        $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($pid);
        $paragraph_references[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
      $entity->set($f, $paragraph_references);
    }
    $entity->save();
  }

  // Load the node's media.
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
    $entity->setPublished(TRUE);
    $entity->set('moderation_state', 'published');
    $entity->save();
    update_handle($entity);
  }
  return $import['map'][$type][$id];
}

$path = $extra[0];

if (!is_readable($path)) {
  $message = "The path {$path} is not readable.";
  die($message);
}

$import = json_decode(file_get_contents($path), TRUE);

if (!is_readable($import['settings']['public'] ?? '') || !is_readable($import['settings']['private'] ?? '')) {
  die("The public or private path in the import settings is not readable.");
}

$import['map'] = ['node' => []];
foreach ($import['node'] as $nid => $fields) {
  $new_nid = import_entity('node', $nid, $import);
  print("Imported node '{$fields['title'][0]['value']}' {$nid} as {$new_nid}.\n");
}
// Print TSV of the import map.
$export_path = dirname($path) . DIRECTORY_SEPARATOR . basename($path, '.json') . '_import_map.tsv';
$fp = fopen($export_path, 'w');
fputcsv($fp, ['type', 'old_id', 'new_id'], "\t");
foreach ($import['map'] as $type => $map) {
  foreach ($map as $old_id => $new_id) {
    fputcsv($fp, [$type, $old_id, $new_id], "\t");
  }
}
fclose($fp);
print("Exported import map to {$export_path}.\n");
