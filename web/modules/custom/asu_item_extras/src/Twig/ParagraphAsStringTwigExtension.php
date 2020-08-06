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
    $paragraphAsStringArr = [];

    $paragraph = $complex_title['#paragraph'];

    $nonsort = $paragraph->get('field_nonsort')->getString();
    $rest_of_title = $paragraph->get('field_rest_of_title')->getString();
    $subtitle = $paragraph->get('field_subtitle')->getString();
    $supplied = $paragraph->get('field_supplied')->getString();
    $paragraphAsStringArr[] = ($nonsort ? $nonsort . " " : "") .
      ($rest_of_title ? $rest_of_title : "[untitled]") .
      ($subtitle ? ": " . $subtitle : "");
    return implode("\n" , $paragraphAsStringArr);
  }
}