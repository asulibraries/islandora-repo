<?php

namespace Drupal\repo_bento_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\repo_bento_search\DataverseApiService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Search results from dataverse' Block.
 *
 * @Block(
 *   id = "bento_dataverse_results_block",
 *   admin_label = @Translation("Bento Dataverse search results block"),
 *   category = @Translation("Views"),
 * )
 */
class BentoDataverse extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Dataverse service.
   *
   * @var \Drupal\repo_bento_search\DataverseApiService
   */
  protected $dvService;

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * Constructs a Dataverse block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\repo_bento_search\DataverseApiService $dv_service
   *   Drupal core renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    DataverseApiService $dv_service,
    RequestStack $request_stack
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('repo_bento_search.bentosettings');
    $this->dvService = $dv_service;
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
      $container->get('repo_bento_search.dataverse'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Read the configuration to see how many results we need to display.
    $service_api_url = $this->config->get('dataverse_api_url');
    $search_term = $this->requestStack->getCurrentRequest()->query->get('q');
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
      $num_results = $this->config->get('num_results') ?: 10;
      // Get the search parameter from the GET url.
      // the url parameter is q as in q=cat.
      $results_json = ($search_term) ?
        $this->dvService->getSearchResults($search_term, $num_results) : '';
      $results_arr = json_decode($results_json, TRUE);
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
      '#attributes' => ['class' => ['bento_box']],
      [
        '#theme' => 'dataverse_results',
        '#service_url' => $service_url,
        '#items' => $result_items,
        '#total_results_found' => $results_arr['data']['total_count'],
        '#search_term' => $search_term,
      ],
    ];
  }

}
