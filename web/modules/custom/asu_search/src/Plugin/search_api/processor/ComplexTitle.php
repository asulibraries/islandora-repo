<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds the complex title as a single string.
 *
 * @SearchApiProcessor(
 *   id = "complex_title",
 *   label = @Translation("Complex Title"),
 *   description = @Translation("adds the complex title as a single string"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class ComplexTitle extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Complex Title'),
        'description' => $this->t('A string that combines parts of a title'),
        'type' => 'string',
        'is_list' => TRUE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['complex_title'] = new ProcessorProperty($definition);
      $definition = [
        'label' => $this->t('Main + Subtitle'),
        'description' => $this->t('A string that combines parts of a title'),
        'type' => 'string',
        'is_list' => TRUE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['main_sub_title'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    if ($node->hasField('field_title')) {
      $vals = $node->field_title->getValue();
      foreach ($vals as $element) {
        $fields = $item->getFields(FALSE);
        $paragraph = Paragraph::load($element['target_id']);
        $nonsort = $paragraph->field_nonsort->value;
        $main = $paragraph->field_main_title->value;
        $sub = $paragraph->field_subtitle->value;
        $nm = ($nonsort ? $nonsort . " " : "") .
        ($main ? $main : "[untitled]") .
        ($sub ? ": " . $sub : "");
        $main_sub = ($main ? $main : "[untitled]") .
        ($sub ? ": " . $sub : "");
        $fields = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'complex_title');
        foreach ($fields as $field) {
          $field->addValue($nm);
        }
        $fields2 = $item->getFields(FALSE);
        $fields2 = $this->getFieldsHelper()->filterForPropertyPath($fields2, NULL, 'main_sub_title');
        foreach ($fields2 as $field2) {
          $field2->addValue($main_sub);
        }
      }
    }
  }

}
