<?php

namespace Drupal\repo_bento_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\repo_bento_search\LegacyRepoApiService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Search results from Legacy Repository' Block.
 *
 * @Block(
 *   id = "bento_legacy_repo_results_block",
 *   admin_label = @Translation("Bento legacy repository search results block"),
 *   category = @Translation("Views"),
 * )
 */
class BentoLegacyRepo extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Legacy Repo API service.
   *
   * @var \Drupal\repo_bento_search\LegacyRepoApiService
   */
  protected $lrService;

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * Constructs a Legacy Repo block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\repo_bento_search\LegacyRepoApiService $lr_service
   *   Drupal core renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LegacyRepoApiService $lr_service,
    RequestStack $request_stack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('repo_bento_search.bentosettings');
    $this->lrService = $lr_service;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('repo_bento_search.legacy_repo'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the search parameter from the GET url.
    // the url parameter is q as in q=cat.
    $search_term = $this->requestStack->getCurrentRequest()->query->get('q');
    // Read the configuration to see how many results we need to display.
    $service_api_url = $this->config->get('legacy_repository_api_url');
    if (!trim(($service_api_url))) {
      $results_arr = [
        'results' => 'Legacy search not configured.',
        'count' => 0,
      ];
      $result_items = [];
      $service_url = '';
    }
    else {
      $parsed_url = parse_url($service_api_url);
      $service_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
      // $num_results not supported as a parameter for the legacy api searching,
      // but the value is passed through regardless.
      $num_results = $this->config->get('num_results') ?: 10;
      $results_json = ($search_term) ?
        $this->lrService->getSearchResults($search_term, $num_results) : '';
      $results_arr = json_decode($results_json, TRUE);
      if (is_null($results_arr)) {
        $result_items = [];
        $results_arr['count'] = 0;
      }
      else {
        // API does not allow for a "how many" parameter, remove extra items.
        if (count($results_arr['results']) > $num_results) {
          for ($p = count($results_arr['results']) - 1; $p >= $num_results; $p--) {
            unset($results_arr['results'][$p]);
          }
        }
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
      '#attributes' => ['class' => ['bento_box']],
      [
        '#theme' => 'legacyrepo_results',
        '#service_url' => $service_url,
        '#items' => $result_items,
        '#total_results_found' => $results_arr['count'],
        '#search_term' => $search_term,
      ],
    ];
  }

}
