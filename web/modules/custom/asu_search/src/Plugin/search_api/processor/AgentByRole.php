<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds the item's linked agent separately by type.
 *
 * @SearchApiProcessor(
 *   id = "agent_by_type",
 *   label = @Translation("Agent by Role"),
 *   description = @Translation("adds the item's linked agent separately by type"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class AgentByRole extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL)
  {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Agent By Role'),
        'description' => $this->t('An agent by role'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['asu_agent_aut'] = new ProcessorProperty($definition);
      $properties['asu_agent_ths'] = new ProcessorProperty($definition);
      // thesis advisor
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    if ($node->hasField('field_linked_agent') && !$node->get('field_linked_agent')->isEmpty()) {
      $vals = $node->field_linked_agent->getValue();
      // \Drupal::logger('asu search')->info(print_r($vals, TRUE));
      foreach ($vals as $element) {
        $fields = $item->getFields(FALSE);
        $tid = $element['target_id'];
        $taxo_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
        if ($taxo_term) {
          // \Drupal::logger('asu search')->info(print_r($taxo_term, TRUE));
          $taxo_name = $taxo_term->name->value;
          // \Drupal::logger('asu search')->info('taxo name is ' . $taxo_name);
          $rel_type = $element['rel_type'];
          $mac_rel = strtolower($rel_type);
          $mac_rel = str_replace('relators:', '', $mac_rel);
          // \Drupal::logger('asu search')->info('rel type is ' . $rel_type);
          $fields = $this->getFieldsHelper()
            ->filterForPropertyPath($fields, NULL, 'asu_agent_' . $mac_rel);
          foreach ($fields as $field) {
            $field->addValue($taxo_name);
          }
        }
      }
    }
  }

}
