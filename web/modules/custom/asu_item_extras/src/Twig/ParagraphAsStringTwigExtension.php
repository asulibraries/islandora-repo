<?php

namespace Drupal\asu_item_extras\Twig;

class ParagraphAsStringTwigExtension extends \Twig_Extension {
  /**
   * Gets filters
   *
   * @return array
   */

  public function getFilters() {
    return array(
      new \Twig_SimpleFilter('paragraphAsString', array($this, 'paragraphAsString')),
    );
  }

  public function getName() {
    return 'asu_item_extras';
  }

  function paragraphAsString($complex_title) {
    \Drupal::messenger()->addMessage("in the custom paragraph title formatter");
    // @todo - this might not handle well if the values are blank but this works if they are present

    $nonsort = $complex_title['field_nonsort']['#items'][0]->getValue()['value'];
    $rest_of_title = $complex_title['field_rest_of_title']['#items'][0]->getValue()['value'];
    $subtitle = $complex_title['field_subtitle']['#items'][0]->getValue()['value'];
    $supplied = $complex_title['field_supplied']['#items'][0]->getValue()['value'];
    return ($nonsort ? $nonsort . " " : "") .
      ($rest_of_title ? $rest_of_title : "[untitled]") .
      ($subtitle ? ": " . $subtitle : "");
  }
}
