<?php

/**
 * @file
 * BentoSecondI8
 */
namespace Drupal\repo_bento_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search results from secondary Islandora 8' Block.
 *
 * @Block(
 *   id = "bento_second_i8_results_block",
 *   admin_label = @Translation("Bento secondary Islandora 8 search results block"),
 *   category = @Translation("Views"),
 * )
 */
class BentoSecondI8 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Read the configuration to see how many results we need to display.
    $config = \Drupal::config('repo_bento_search.bentosettings');
    $service_api_url = $config->get('second_i8_api_url');
    $search_term = \Drupal::request()->query->get('q');
    if (!trim(($service_api_url))) {
      $total_results_found = 0;
      $result_items = [];
      $service_url = '';
    }
    else {
      $num_results = $config->get('num_results') ?: 10;
      // Get the search parameter from the GET url.
      // the url parameter is q as in q=cat
      $parsed_url = parse_url($service_api_url);
      $service_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
      $results_json = ($search_term) ?
        \Drupal::service('repo_bento_search.second_i8')->getSearchResults($search_term) : '';
      $results_arr = json_decode($results_json, true);
      if (is_null($results_arr)) {
        $result_items = [];
        $total_results_found = 0;
      }
      else {
        $total_results_found = $results_arr['pager']['count'];
        if (count($results_arr['search_results']) > $num_results) {
          for ($p = count($results_arr['search_results']) - 1; $p >= $num_results; $p--) {
            unset($results_arr['search_results'][$p]);
          }
        }
        // Since this API does not allow for a "how many" parameter, remove extra items.
        $result_items = (array_key_exists('search_results', $results_arr) &&
          is_array($results_arr['search_results'])) ?
            $results_arr['search_results'] : [];
      }
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
      '#attributes' => ['class' => ['bento_box']],
      [
        '#theme' => 'second_i8_results',
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
