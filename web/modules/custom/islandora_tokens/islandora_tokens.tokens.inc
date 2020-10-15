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
       'description' => t('Show the Date Created (EDTF) into YYYY/MM/DD format'),
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
                try {
                  $created_date = ($data['node']->hasField('field_edtf_date_created') ? $data['node']->get('field_edtf_date_created') : NULL);
                  if (!is_null($created_date)) {
                    $date_val = new DateTime($created_date->getValue('field_edtf_date_created')[0]['value']);
                    $replacements[$original] = $date_val->format('Y/m/d');
                  }
                } catch (Exception $e) {
                  // Date value could not be converted to a string
                  \Drupal::logger('islandora_tokens')->notice('Date value: @date could not be converted to a DateTime object, used token "@token_value".',
                      array(
                          '@date' => @$created_date->getValue('field_edtf_date_created')[0]['value'],
                          '@token_value' => $original,
                      ));
                }
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