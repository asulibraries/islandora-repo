<?php

/**
 * @file
 * Contains asu_islandora_tokens.tokens.inc.
 *
 * This file provides islandora tokens.
 */

//use Drupal\taxonomy\Entity\Term;
//use Drupal\media\Entity\Media;
//use Drupal\file\Entity\File;
use Drupal\controlled_access_terms\EDTFUtils;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function asu_islandora_tokens_token_info() {
  $type = [
    'name' => t('Islandora Tokens'),
    'description' => t('Tokens for Islandora objects.'),
  ];
// Move this field to our own baby asu_islandora_tokens module.
  $node['copyright_date'] = [
    'name' => t("Copyright date"),
    'description' => t('Show the "Copyright Date" into YYYY/MM/DD format (handles EDTF format)'),
  ];
  return [
    'types' => ['islandoratokens' => $type],
    'tokens' => ['islandoratokens' => $node],
  ];
}

/**
 * Implements hook_tokens().
 */
function asu_islandora_tokens_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'islandoratokens' && !empty($data['node'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'copyright_date':
          $replacements[$original] = asu_islandora_tokens_normalize_date_format($data['node'], 'field_edtf_copyright_date', $original);
          break;
      }
    }
  }
  return $replacements;
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
function asu_islandora_tokens_normalize_date_format($node, $field_name, $original) {
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
    \Drupal::logger('asu_islandora_tokens')->notice(
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
