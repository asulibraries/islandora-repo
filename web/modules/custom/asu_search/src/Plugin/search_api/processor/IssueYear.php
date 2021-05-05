<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\node\Entity\Node;
use Drupal\controlled_access_terms\EDTFUtils;

/**
 * Adds the item's creation year to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "etdf_created_year_only",
 *   label = @Translation("Issue Year"),
 *   description = @Translation("Adds the item's creation year to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class IssueYear extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('EDTF Creation Date Year'),
        'description' => $this->t('The year the item was created'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['etdf_created_year_only'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // find the URI of the entity being added to the search API
    // via the search API's item ID (e.g. entity:node/1:en)
    // $components = explode(':', $item->getId());
    // array_shift($components);
    // $uri = array_shift($components);
    // $components = explode('/', $uri);

    // if this is a node, see if we can find the mimetype of the original file.
    // if (count($components) == 2 && $components[0] === 'node') {
      // @todo is there a better way to get the node entity than loading it again?
      $node = $item->getOriginalObject()->getValue();


      // if we found a mimetype, add it to the index
      if ($node
        && $node->hasField('field_edtf_date_created')
        && !$node->field_edtf_date_created->isEmpty()) {

        $date = $node->field_edtf_date_created->value;
        if ($date != "nan") {
          $iso = EDTFUtils::iso8601Value($date);
          $components = explode('-', $iso);
          $year = array_shift($components);
          if (is_numeric($year)) {
            $fields = $item->getFields(FALSE);
            $fields = $this->getFieldsHelper()
              ->filterForPropertyPath($fields, NULL, 'etdf_created_year_only');
            foreach ($fields as $field) {
              $field->addValue($year);
            }
          }
        }
      }
    // }
  }
}
