<?php

/**
 * @file
 * BentoLegacyRepo
 */
namespace Drupal\repo_bento_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search results from Legacy Repository' Block.
 *
 * @Block(
 *   id = "bento_legacy_repo_results_block",
 *   admin_label = @Translation("Bento legacy repository search results block"),
 *   category = @Translation("Views"),
 * )
 */
class BentoLegacyRepo extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the search parameter from the GET url.
    // the url parameter is q as in q=cat
    $search_term = \Drupal::request()->query->get('q');
    // Read the configuration to see how many results we need to display.
    $config = \Drupal::config('repo_bento_search.bentosettings');
    $service_api_url = $config->get('legacy_repository_api_url');
    if (!trim(($service_api_url))) {
      $results_arr = [
        'results' => 'Legacy search not configured.',
        'count' => 0
      ];
      $result_items = [];
      $service_url = '';
    }
    else {
      $parsed_url = parse_url($service_api_url);
      $service_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
      // $num_results not supported as a parameter for the legacy api searching,
      // but the value is passed through regardless.
      $num_results = $config->get('num_results') ?: 10;
      $results_json = ($search_term) ?
        \Drupal::service('repo_bento_search.legacy_repo')->getSearchResults($search_term, $num_results) : '';
      $results_arr = json_decode($results_json, true);
      if (is_null($results_arr)) {
        $result_items = [];
        $results_arr['count'] = 0;
      }
      else {
        $result_items = (array_key_exists('results', $results_arr) && is_array($results_arr['results'])) ?
            $results_arr['results'] : [];
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
        '#theme' => 'legacyrepo_results',
        '#service_url' => $service_url,
        '#items' => $result_items,
        '#total_results_found' => $results_arr['count'],
        '#search_term' => $search_term
      ],
//      '#markup' =>
//        "Search term: <b>" . $search_term . "</b>" .
//        "<pre>" . print_r($results_arr, true) . "</pre>",
    ];
  }

}