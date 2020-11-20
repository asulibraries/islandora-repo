<?php

namespace Drupal\asu_statistics;

/**
 * Interface RESTApiInterface.
 */
interface RESTApiInterface {

  /**
   * Get the Search API REST.
   */
  public function call_REST(int $collection_node_id, string $type);

}
