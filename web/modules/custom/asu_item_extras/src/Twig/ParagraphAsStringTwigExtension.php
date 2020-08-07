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
    $sn = $complex_title['field_nonsort'];
    if (array_key_exists('#items', $sn)) {
      $nonsort = $sn['#items'][0]->getValue()['value'];
    }
    $sr = $complex_title['field_rest_of_title'];
    if (array_key_exists('#items', $sr)) {
      $rest_of_title = $sr['#items'][0]->getValue()['value'];
    }
    $ss = $complex_title['field_subtitle'];
    if (array_key_exists('#items', $ss)) {
      $subtitle = $ss['#items'][0]->getValue()['value'];
    }
    $st = $complex_title['field_supplied'];
    if (array_key_exists('#items', $st)) {
      $supplied = $st['#items'][0]->getValue()['value'];
    }
    return ($nonsort ? $nonsort . " " : "") .
      ($rest_of_title ? $rest_of_title : "[untitled]") .
      ($subtitle ? ": " . $subtitle : "");
  }
}
