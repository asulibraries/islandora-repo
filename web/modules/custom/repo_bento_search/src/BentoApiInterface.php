<?php

namespace Drupal\repo_bento_search;

/**
 * Interface BentoApiInterface.
 */
interface BentoApiInterface {

    public function getSearchResults(string $term, int $limit = 10);

}
