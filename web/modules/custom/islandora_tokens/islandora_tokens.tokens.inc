<?php

use Drupal\taxonomy\Entity\Term;

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
    'description' => t('The node\'s title'),
  ];
  $node['agent_author'] = [
    'name' => t("Author"),
    'description' => t('The node\'s Author linked agent'),
  ];
  $node['agent_creators'] = [
    'name' => t("Creators"),
    'description' => t('The node\'s Creators linked agent'),
  ];
  $node['publication_date'] = [
    'name' => t("Publication date"),
    'description' => t('Show the "Date Created" into YYYY/MM/DD format (handles EDTF format)'),
  ];
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
function islandora_tokens_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
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
           $replacements[$original] = _get_term_with_rel_type($data['node'], 'field_linked_agent', 'relators:aut');
         break;
         case 'agent_creators':
            // For the Linked agent field, Creators are any relationship value.
           $replacements[$original] = _get_term_with_rel_type($data['node'], 'field_linked_agent', '');
         break;
         case 'publication_date':
           $replacements[$original] = _normalize_date_format($data['node'], 'field_edtf_date_created', $original);
         break;
         case 'copyright_date':
           $replacements[$original] = _normalize_date_format($data['node'], 'field_edtf_copyright_date', $original);
         break;
      }
    }
  }
  return $replacements;
}

/**
 * Helper function to load the values for a node's taxonomy term that have
 * a specified relationship type.
 *
 * @param object $node
 *  A core drupal node object.
 * @param string $field_name
 *  The name of the node's field to check for the specific relationship.
 * @param string $relation_type
 *  Optional value to check the rel_type of the taxonomy term against. When
 * not provided, any terms returned for the field will match.
 * @return string
 *  The tokenized value for the given data.
 */
function _get_term_with_rel_type($node, $field_name, $relation_type = '') {
  $matches = [];
  $field = ($node->hasField($field_name) ? $node->get($field_name) : NULL);
  if (is_object($field)) {
    $tids = $field->getValue();
    foreach ($tids as $tid) {
      // Inspect the taxonomy term.
      if (is_array($tid) &&
        array_key_exists('target_id', $tid)) {
        if ($relation_type && array_key_exists('rel_type', $tid)) {
          if ($tid['rel_type'] == $relation_type) {
            $matches[] = Term::load($tid['target_id'])->getName();
          }
        }
        else {
          $matches[] = Term::load($tid['target_id'])->getName();
        }
      }
    }
  }
  return implode(", ", $matches);
}

/**
 * Helper function to normalize a date value that could potentially be an EDTF
 * formatted date value.
 *
 * @param object $node
 *  A core drupal node object.
 * @param string $field_name
 *  The name of the node's field to check for the specific relationship.
 * @param string $original
 *  This is the token value that is being processed - for logging purposes.
 * @return string
 *  The tokenized value for the given data.
 * @throws Exception
 */
function _normalize_date_format($node, $field_name, $original) {
  $retval = '';
  try {
    $created_date = ($node->hasField($field_name) ? $node->get($field_name) : NULL);
    if (!is_null($created_date)) {
      $v = $created_date->getValue($field_name);
      $xx = (array_key_exists('value', $created_date->getValue($field_name)[0])) ? "r" : "f";
      if (is_array($created_date->getValue($field_name)) &&
          array_key_exists(0, $created_date->getValue($field_name)) &&
          is_array($created_date->getValue($field_name)[0]) &&
          array_key_exists('value', $created_date->getValue($field_name)[0])) {
        $v = $created_date->getValue($field_name)[0]['value'];
        if (strlen($v) < 6) {
          throw new Exception('Date does not have enough digits.');
        }
        $date_val = new DateTime($v);
        $retval = $date_val->format('Y/m/d');
      }
    }
  } catch (Exception $e) {
    // Date value could not be converted to a string
    \Drupal::logger('islandora_tokens')->notice('During token generation, a date ' .
      'value: @date could not be converted to a DateTime object, used token "@token_value".',
      array(
          '@date' => @$created_date->getValue($field_name)[0]['value'],
          '@token_value' => $original,
      ));
  }
  return $retval;
}
