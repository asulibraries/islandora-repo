<?php

/**
 * @file
 * BentoDataverse
 */
namespace Drupal\repo_bento_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search results from dataverse' Block.
 *
 * @Block(
 *   id = "bento_dataverse_results_block",
 *   admin_label = @Translation("Bento Dataverse search results block"),
 *   category = @Translation("Views"),
 * )
 */
class BentoDataverse extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Read the configuration to see how many results we need to display.
    $config = \Drupal::config('repo_bento_search.bentosettings');
    $service_api_url = $config->get('dataverse_api_url');
    $search_term = \Drupal::request()->query->get('q');
    if (!trim(($service_api_url))) {
      $results_arr = [
        'results' => 'Dataverse search not configured.',
      ];
      $service_url = '';
      $result_items = [];
      $results_arr['data']['total_count'] = 0;
      $results_arr['data']['items'] = [];
    }
    else {
      $parsed_url = parse_url($service_api_url);
      $service_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
      $num_results = $config->get('num_results') ?: 10;
      // Get the search parameter from the GET url.
      // the url parameter is q as in q=cat
      $results_json = ($search_term) ?
        \Drupal::service('repo_bento_search.dataverse')->getSearchResults($search_term, $num_results) : '';
      $results_arr = json_decode($results_json, true);
      if (is_null($results_arr)) {
        $result_items = [];
        $results_arr['data'] = 0;
      }
      else {
        $result_items = (array_key_exists('data', $results_arr) &&
          array_key_exists('items', $results_arr['data']) && is_array($results_arr['data']['items'])) ?
            $results_arr['data']['items'] : [];
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
      '#attributes' => [
        'class' => array(0 => 'bento_box'),
      ],
      [
        '#theme' => 'dataverse_results',
        '#service_url' => $service_url,
        '#items' => $result_items,
        '#total_results_found' => $results_arr['data']['total_count'],
        '#search_term' => $search_term
      ],
//      '#markup' =>
//        "Search term: <b>" . $search_term . "</b>" .
//        "<pre>" . print_r($results_arr, true) . "</pre>",
    ];
  }

}