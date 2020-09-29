<?php

/**
 * @file
 * BentoThisI8
 */
namespace Drupal\repo_bento_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search results from this Islandora 8' Block.
 *
 * @Block(
 *   id = "bento_this_i8_results_block",
 *   admin_label = @Translation("Bento this Islandora 8 search results block"),
 *   category = @Translation("Views"),
 * )
 */
class BentoThisI8 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Read the configuration to see how many results we need to display.
    $config = \Drupal::config('repo_bento_search.bentosettings');
    $service_api_url = $config->get('this_i8_api_url');
    $search_term = \Drupal::request()->query->get('q');
    if (!trim(($service_api_url))) {
      $total_results_found = 0;
      $result_items = [];
    } else {
      $num_results = $config->get('num_results') ?: 10;
      // Get the search parameter from the GET url.
      // the url parameter is q as in q=cat
      $results_json = ($search_term) ?
        \Drupal::service('repo_bento_search.this_i8')->getSearchResults($search_term) : '';
      $results_arr = json_decode($results_json, true);
      $result_items = (array_key_exists('search_results', $results_arr) &&
        is_array($results_arr['search_results'])) ?
          $results_arr['search_results'] : [];
      $total_results_found = $results_arr['pager']['count'];
    }
    return [
      '#cache' => ['max-age' => 0],
      'lib' => [
        '#attached' => [
          'library' => [
            'repo_bento_search/style',
          ],
        ],
      ],
      '#attributes' => [
        'class' => array(0 => 'bento_box'),
      ],
      [
        '#theme' => 'this_i8_results',
        '#service_url' => $service_url,
        '#items' => $result_items,
        '#total_results_found' => $total_results_found,
        '#search_term' => $search_term
      ],
//      '#markup' =>
//        "Search term: <b>" . $search_term . "</b>" .
//        "<pre>" . print_r($results_json, true) . "</pre>",
    ];
  }

}