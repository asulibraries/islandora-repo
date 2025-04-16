<?php

namespace Drupal\asu_item_extras\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Time' formatter.
 *
 * @FieldFormatter(
 *   id = "human_time_duration",
 *   label = @Translation("Human Time Duration"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class HumanTimeDuration extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {

    $elements = [];
    foreach ($items as $delta => $item) {
	    $elements[$delta] = [
		    '#plain_text' => ltrim(sprintf('%02d:%02d:%02d', $item->value/3600, floor($item->value/60)%60, $item->value%60), "0:")
	    ];
    }

    return $elements;
  }


}
