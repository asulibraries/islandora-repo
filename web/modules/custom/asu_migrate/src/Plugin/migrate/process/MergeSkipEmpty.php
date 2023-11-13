<?php

namespace Drupal\asu_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\Merge;

/**
 * This plugin merges arrays together.
 *
 * @MigrateProcessPlugin(
 *   id = "merge_skip_empty"
 * )
 *
 * Use to merge several fields into one. In the following example, imagine a D7
 * node with a field_collections field and an image field that migrations were
 * written for to make paragraph entities in D8. We would like to add those
 * paragraph entities to the 'paragraphs_field'. Consider the following:
 *
 *  source:
 *    plugin: d7_node
 *  process:
 *    temp_body:
 *      plugin: iterator
 *      source: field_section
 *      process:
 *        target_id:
 *          plugin: migration_lookup
 *          migration: field_collection_field_section_to_paragraph
 *          source: value
 *    temp_images:
 *      plugin: iterator
 *      source: field_image
 *      process:
 *        target_id:
 *          plugin: migration_lookup
 *          migration: image_entities_to_paragraph
 *          source: fid
 *    paragraphs_field:
 *      plugin: merge
 *      source:
 *        - '@temp_body'
 *        - '@temp_images'
 *  destination:
 *    plugin: 'entity:node'
 */
class MergeSkipEmpty extends Merge {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): array {
    if (!is_array($value)) {
      throw new MigrateException(sprintf('Merge process failed for destination property (%s): input is not an array.', $destination_property));
    }
    // Remove empty values.
    $value = array_filter($value);
    $new_value = [];
    foreach ($value as $i => $item) {
      if ($item == NULL) {
        continue;
      }
      elseif (!is_array($item)) {
        throw new MigrateException(sprintf('Merge process failed for destination property (%s): index (%s) in the source value is not an array that can be merged.', $destination_property, $i));
      }
      $new_value[] = $item;
    }
    if ($new_value != NULL) {
      $array = array_merge(...$new_value);
      return $array;
    }
    else {
      return NULL;
    }

  }

}
