<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'About this collection' Sidebar Block.
 *
 * @Block(
 *   id = "about_this_collection_sidebar_block",
 *   admin_label = @Translation("About this collection sidebar"),
 *   category = @Translation("Views"),
 * )
 */
class AboutThisCollectionSidebarBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The requestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructor for About this Collection Block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, RequestStack $request_stack, AccountProxy $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->routeMatch->getParameter('node');
    if (!$node) {
      return [];
    }
    $output_links = [];

    // Add a link to get the Permalink for this node. Could this be a javascript
    // event that will send the current node's URL to the copy buffer?
    if ($node->hasField('field_handle') && !$node->get('field_handle')->isEmpty()) {
      $hdl = $node->get('field_handle')->value;
      $output_links['permalink'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['nav-link', 'title' => $hdl]],
        'link' => Link::fromTextAndUrl($this->t('Permalink'), Url::fromUri('https://hdl.handle.net/' . $hdl))->toRenderable(),
        'copy_button' => [
          '#type' => 'html_tag',
          '#tag' => 'i',
          '#attributes' => ['class' => "far fa-copy fa-lg copy_permalink_link mx-2", "title" => $hdl],
        ],

      ];
    }

    $children = asu_collection_extras_solr_get_collection_children($node);

    $items = $children['item_count'] ?? 0;
    if ($items > 0) {
      $output_links['items'] = [
        'link' => Link::fromTextAndUrl(
          [
            [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => number_format($items),
              '#attributes' => ['class' => 'stats-value'],
            ],
            ['#plain_text' => ' items'],
          ],
          Url::fromUri("{$this->requestStack->getCurrentRequest()->getSchemeAndHttpHost()}/collections/{$node->id()}/search/?search_api_fulltext=&no_pages=1&sort_by=main_sub_title")
        )->toRenderable(),
      ];
    }
    $islandora_models = $children['model_count'] ?? 0;
    if ($islandora_models > 0) {
      $output_links['resource_types'] = [
        'count' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $islandora_models,
          '#attributes' => ['class' => 'stats-value'],
        ],
        'text' => [
          '#plain_text' => ' resource types',
        ],
      ];
    }
    $time = $children['recent_change'] ?? NULL;
    if ($time) {
      $output_links['last_updated'] = [
        'text' => [
          '#plain_text' => 'Last updated ',
        ],
        'date' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => date('Y M', strtotime($time)),
          '#attributes' => ['class' => 'stats-value'],
        ],
      ];
    }

    if (empty($output_links)) {
      return [];
    }
    $build = ['#attached' => ['library' => ['asu_collection_extras/style']]];
    foreach ($output_links as $name => $render_array) {
      $build[$name] = array_merge($render_array, [
        '#type' => 'container',
        '#attributes' => ['class' => ['stats_border_box']],
      ]);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = $this->routeMatch->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // If you depends on \Drupal::routeMatch().
    // You must set context of this block with 'route' context tag.
    // Every new route this block will rebuild.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
