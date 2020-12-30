<?php

namespace Drupal\asu_admin_toolbox\Plugin\Block;

use Drupal\media\Entity\Media;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
// use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides an 'Admin toolbox' Block.
 *
 * @Block(
 *   id = "admin_toolbox",
 *   admin_label = @Translation("Admin toolbox"),
 *   category = @Translation("Views"),
 * )
 */
class AdminToolboxBlock extends BlockBase implements ContainerFactoryPluginInterface {
// class AdminToolboxBlock extends BlockBase implements TrustedCallbackInterface, ContainerFactoryPluginInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

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
    /*
     * The sections in this block should be statistics on the children of the
     * collection using the "Is Member Of" relationship -- not just the "Is
     * Primary Member Of" and these should link to a canned-search for the
     * specific statistic when possible:
     *
     *  - # of child items
     *  - # of resource types
     *  - # of titles

     *  - usage
     *  - collection created
     *  - collection last updated (by looking at all of the children)
     *
     * When needing to check a node's content type:
     *    $is_collection = ($node->bundle() == 'collection');
     */
    // Since this block should be set to display on node/[nid] pages that are
    // either "Repository Item", "ASU Repository Item", or "Collection",
    // the underlying node can be accessed via the path.
    $collection_node = $this->routeMatch->getParameter('node');
    if ($collection_node) {
      $collection_created = $collection_node->get('revision_timestamp')->getString();
    } else {
      $collection_created = 0;
    }

    $output_links = [];
    // Add item link.
    $current_user = $this->currentUser;
    $use_can_add_child = \Drupal\asu_admin_toolbox\Access\AddChildToGroupController::access($current_user);
    if ($use_can_add_child) {
      // This link is a little bit tricky... it needs to have a fragment like
      // this for example, where the value 10 is the collection id() value.
      // node/add/asu_repository_item?edit[field_member_of][widget][0][target_id]=10
      $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() .
        '/node/add/asu_repository_item?edit[field_member_of][widget][0][target_id]=' .
        $collection_node->id());
      $link = Link::fromTextAndUrl(t('Add'), $url);
      $link = $link->toRenderable();
      $link_glyph = Link::fromTextAndUrl(t('<i class="fas fa-chart-bar"></i> '), $url)->toRenderable();
      $output_links[] = render($link);
    }

    // Edit link.

    
    // Statistics link.
    $view_statistics = $collection_node->access('update', $this->currentUser);
    if ($view_statistics) {
      $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/collections/' . $nid . '/statistics');
      $link = Link::fromTextAndUrl($this->t('Statistics'), $url);
      $link = $link->toRenderable();
      $link_glyph = Link::fromTextAndUrl(t('<i class="fas fa-chart-bar"></i> '), $url)->toRenderable();
      $output_links[] = render($link_glyph) . render($link);
    }
    return [
      '#markup' => (count($output_links) > 0) ?
      "<div class='pseudo_block'><h2>Admin toolbox</h2><nav><ul class=''><li>" . implode("<hr>", $output_links) . "</li></ul></nav></div>" :
      "",
      '#attached' => [
        'library' => [
          'asu_admin_toolbox/style',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags()
  {
    if ($node = $this->routeMatch->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    } else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts()
  {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
