<?php

namespace Drupal\repo_bento_search;

/**
 * Interface BentoApiInterface.
 */
interface BentoApiInterface {

  /**
   * Get the Search Results from the API source.
   */
  public function getSearchResults(string $term, int $limit = 10);

}
