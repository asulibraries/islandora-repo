<?php

namespace Drupal\asu_brand\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Base class for ASU Brand blocks.
 *
 */
abstract class AsuBrandBlockBase extends BlockBase {

  /**
   * Load external file from URL.
   *
   * @param $uri
   * @return bool|string
   */
  protected function fetchExternalMarkUp($url) {
    $data = '';

    try {
      $response = \Drupal::httpClient()->get($url, array('headers' => array('Accept' => 'text/plain')));
      if ($response->getStatusCode() == 200) {
        $data = (string)$response->getBody();
      }
    }
    catch (\Exception $e) {
      // TODO: Log error message
    }

    return $data;
  }

}
