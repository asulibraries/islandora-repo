<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds the note value as a single string.
 *
 * @SearchApiProcessor(
 *   id = "note_value",
 *   label = @Translation("Note value"),
 *   description = @Translation("adds the note value as a single string"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class NoteValue extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Note value'),
        'description' => $this->t('A string that contains the note value'),
        'type' => 'string',
        'is_list' => TRUE,
        'processor_id' => $this->getPluginId(),
      ];
      $properties['note_value'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    if ($node->hasField('field_note_para')) {
      $vals = $node->field_note_para->getValue();
      foreach ($vals as $element) {
        $fields = $item->getFields(FALSE);
        $paragraph = Paragraph::load($element['target_id']);
        $note_value = $paragraph->field_note_text->value;
        $fields = $this->getFieldsHelper()->filterForPropertyPath($fields, NULL, 'note_value');
        foreach ($fields as $field) {
          $field->addValue($note_value);
        }
      }
    }
  }

}
