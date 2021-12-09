<?php

/**
 * @file
 * Contains islandora_tokens.tokens.inc.
 *
 * This file provides islandora tokens.
 */

use Drupal\taxonomy\Entity\Term;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\controlled_access_terms\EDTFUtils;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function islandora_tokens_token_info() {
  $type = [
    'name' => t('Islandora Tokens'),
    'description' => t('Tokens for Islandora objects.'),
  ];
  $node['title'] = [
    'name' => t("Node Title"),
    'description' => t("The node's title"),
  ];
  $node['agent_author'] = [
    'name' => t("Author"),
    'description' => t("The node's Author linked agents"),
  ];
  $node['agent_contributor'] = [
    'name' => t("Contributors"),
    'description' => t("The node's Contributor linked agents"),
  ];
  $node['agent_publisher'] = [
    'name' => t("Publishers"),
    'description' => t("The node's Publishers linked agents"),
  ];
  $node['agent_creators'] = [
    'name' => t("Creators"),
    'description' => t("The node's Creators linked agents"),
  ];
  $node['publication_date'] = [
    'name' => t("Publication date"),
    'description' => t('Show the "Date Created" into YYYY/MM/DD format (handles EDTF format)'),
  ];
  $node['copyright_date'] = [
    'name' => t("Copyright date"),
    'description' => t('Show the "Copyright Date" into YYYY/MM/DD format (handles EDTF format)'),
  ];
  $node['pdf_url'] = [
    'name' => t("PDF Url"),
    'description' => t('URL to related media file if "Original file" is a PDF file'),
  ];
  return [
    'types' => ['islandoratokens' => $type],
    'tokens' => ['islandoratokens' => $node],
  ];
}

/**
 * Implements hook_tokens().
 */
function islandora_tokens_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'islandoratokens' && !empty($data['node'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'title':
          $replacements[$original] = $data['node']->getTitle();
          break;

        case 'agent_author':
          // For the Linked agent field, the Author/s are specified
          // by the relationship "aut" for Author.
          $replacements[$original] = _get_term_with_rel_type($data['node'], 'field_linked_agent', 'relators:aut', TRUE);
          break;

        case 'agent_contributor':
          // For the Linked agent field, the Contributor/s are specified
          // by the relationship "ctb".
          $replacements[$original] = _get_term_with_rel_type($data['node'], 'field_linked_agent', 'relators:ctb', TRUE);
          break;

        case 'agent_creators':
          // For the Linked agent field, Creators are any relationship value.
          $replacements[$original] = _get_term_with_rel_type($data['node'], 'field_linked_agent', '');
          break;

        case 'agent_publisher':
          // For the Linked agent field, Publishers are any relationship value.
          $replacements[$original] = _get_term_with_rel_type($data['node'], 'field_linked_agent', 'relators:pbl');
          break;

        case 'publication_date':
          $replacements[$original] = _normalize_date_format($data['node'], 'field_edtf_date_created', $original);
          break;

        case 'copyright_date':
          $replacements[$original] = _normalize_date_format($data['node'], 'field_edtf_copyright_date', $original);
          break;

        case 'pdf_url':
          $replacements[$original] = ' ' . _url_to_service_file_media_by_mimetype($data['node'], 'application/pdf');
          break;
      }
    }
  }
  return $replacements;
}

/**
 * Helper function to load values for a taxonomy term with a relationship type.
 *
 * @param object $node
 *   A core drupal node object.
 * @param string $field_name
 *   The name of the node's field to check for the specific relationship.
 * @param string $relation_type
 *   Optional value to check the rel_type of the taxonomy term against. When
 *   not provided, any terms returned for the field will match.
 * @param bool $remove_comma
 *   Will flip the string parts of a CSV.
 *
 * @return string
 *   The tokenized value for the given data.
 */
function _get_term_with_rel_type($node, $field_name, $relation_type = '', $remove_comma = FALSE) {
  $matches = [];
  $field = ($node->hasField($field_name) ? $node->get($field_name) : NULL);
  if (is_object($field)) {
    $tids = $field->getValue();
    foreach ($tids as $tid) {
      // Inspect the taxonomy term.
      if (
        is_array($tid) &&
        array_key_exists('target_id', $tid)
      ) {
        if ($relation_type && array_key_exists('rel_type', $tid)) {
          if ($tid['rel_type'] == $relation_type) {
            $term = Term::load($tid['target_id']);
            if ($term) {
              $matches[] = $term->getName();
            }
          }
        }
        else {
          $term = Term::load($tid['target_id']);
          if ($term) {
            $matches[] = $term->getName();
          }
        }
      }
    }
  }
  return implode("|", $matches);
}

/**
 * Helper function to normalize a date value that could be an EDTF date value.
 *
 * @param object $node
 *   A core drupal node object.
 * @param string $field_name
 *   The name of the node's field to check for the specific relationship.
 * @param string $original
 *   This is the token value that is being processed - for logging purposes.
 *
 * @return string
 *   The tokenized value for the given data.
 *
 * @throws Exception
 */
function _normalize_date_format($node, $field_name, $original) {
  $retval = '';
  try {
    $created_date = ($node->hasField($field_name) ? $node->get($field_name) : NULL);
    if (!is_null($created_date)) {
      if (
        is_array($created_date->getValue($field_name)) &&
        array_key_exists(0, $created_date->getValue($field_name)) &&
        is_array($created_date->getValue($field_name)[0]) &&
        array_key_exists('value', $created_date->getValue($field_name)[0])
      ) {
        $v = $created_date->getValue($field_name)[0]['value'];
        if (strlen($v) < 6) {
          throw new Exception('Date does not have enough digits.');
        }
        $date_val = new DateTime($v);
        $retval = $date_val->format('Y/m/d');
      }
    }
  }
  catch (Exception $e) {
    if (strpos($field_name, 'edtf') != FALSE) {
      $date = $node->$field_name->value;
      $iso = EDTFUtils::iso8601Value($date);
      $components = explode('-', $iso);
      $year = array_shift($components);
      if (strpos($year, 'T') != FALSE) {
        $year_parts = explode('T', $year);
        $year = $year_parts[0];
      }
      if (is_numeric($year)) {
        return $year;
      }
    }
    // Date value could not be converted to a string.
    \Drupal::logger('islandora_tokens')->notice(
      'During token generation, a date ' .
        'value: @date could not be converted to a DateTime object, used token "@token_value".',
      [
        '@date' => @$created_date->getValue($field_name)[0]['value'],
        '@token_value' => $original,
      ]
    );
  }
  return $retval;
}

/**
 * Gets Original File PDF file URL.
 *
 * @param object $node
 *   A core drupal node object.
 * @param string $mime_type
 *   The name of the node's field to check for the specific relationship.
 *
 * @return string
 *   The tokenized value for the given data.
 */
function _url_to_service_file_media_by_mimetype($node, $mime_type) {
  // making a huge assumption that since we're looking for a PDF that its a document model
  if ($node->get('field_model')->entity == NULL || $node->get('field_model')->entity->getName() != "Digital Document") {
    return;
  }
  $default_config = \Drupal::config('asu_default_fields.settings');
  $origfile_term = $default_config->get('original_file_taxonomy_term');
  $origfile_media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties([
    'field_media_use' => ['target_id' => $origfile_term],
    'field_media_of' => ['target_id' => $node->id()]
  ]);
  if (count($origfile_media) > 0) {
    $origfile_media = reset($origfile_media);
  } else {
    $origfile_media = NULL;
  }
  // Get the media file's mime_type value.
  if (is_object($origfile_media)) {
    $origfile_mime_type = ($origfile_media->hasField('field_mime_type')) ?
      $origfile_media->get('field_mime_type')->getValue() : NULL;
    $origfile_mime_type = (is_array($origfile_mime_type) &&
      array_key_exists(0, $origfile_mime_type) &&
      is_array($origfile_mime_type[0]) &&
      array_key_exists('value', $origfile_mime_type[0])) ?
      $origfile_mime_type[0]['value'] : '';
    // Compare the media file's mime_type to the given value.
    if ($origfile_mime_type == $mime_type) {
      $bundle = $origfile_media->bundle();
      // Since this is Islandora and we assume the Original File is a
      // Document type... but doing it dynamically.
      $file = $origfile_media->get('field_media_' . $bundle)->entity;
      if (!is_null($file)) {
        $url = file_create_url($file->getFileUri());
        return $url;
      }
    }
  }
}
