<?php

namespace Drupal\asu_admin_toolbox\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\group\GroupMembershipLoaderInterface;

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

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

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
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        RouteMatchInterface $route_match,
        RequestStack $request_stack,
        AccountProxy $current_user,
        GroupMembershipLoaderInterface $group_membership_loader
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->groupMembershipLoader = $group_membership_loader;
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
          $container->get('current_user'),
          $container->get('group.membership_loader')
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
    $node = $this->routeMatch->getParameter('node');
    $is_collection = ($node->bundle() == 'collection');
    $canUpdate = $node->access('update', $this->currentUser);
    $output_links = [];
    // Add item link.
    $use_can_add_child = $is_collection && $this->canAddChild();
    if ($use_can_add_child) {
      // This link is a little bit tricky... it needs to have a fragment like
      // this for example, where the value 10 is the collection id() value.
      // node/add/asu_repository_item?edit[field_member_of][widget][0][target_id]=10.
      $url = Url::fromUri(
            $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() .
            '/node/add/asu_repository_item?edit[field_member_of][widget][0][target_id]=' .
            $node->id()
        );
      $link = Link::fromTextAndUrl(t('Add item'), $url);
      $link = $link->toRenderable();
      $link_glyph = Link::fromTextAndUrl(t('<i class="fas fa-plus-circle"></i>'), $url)->toRenderable();
      $output_links[] = render($link) . " &nbsp;" . render($link_glyph);
    }

    if ($canUpdate) {
      // Edit link check edit-any-asu-repository-item-content or
      // edit-own-asu-repository-item-content permissions.
      $url = Url::fromUri(
            $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() .
            '/node/' . $node->id() . '/edit'
        );
      $link = Link::fromTextAndUrl(t('Edit'), $url);
      $link = $link->toRenderable();
      $link_glyph = Link::fromTextAndUrl(t('<i class="fas fa-pencil-alt"></i>'), $url)->toRenderable();
      $output_links[] = render($link) . " &nbsp;" . render($link_glyph);

      if ($is_collection) {
        // Statistics link.
        $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/collections/' . $node->id() . '/statistics');
        $link = Link::fromTextAndUrl($this->t('Statistics'), $url);
        $link = $link->toRenderable();
        $link_glyph = Link::fromTextAndUrl(t('<i class="fas fa-chart-bar"></i>'), $url)->toRenderable();
        $output_links[] = render($link) . " &nbsp;" . render($link_glyph);
      }
    }
    return [
      '#markup' => (count($output_links) > 0) ?
      "<div class='pseudo_block'><h2>Admin toolbox</h2><nav><ul><li>" . implode("<hr>", $output_links) . "</li></ul></nav></div>" :
      "",
      '#attached' => [
        'library' => [
          'asu_admin_toolbox/style',
        ],
      ],
    ];
  }

  /**
   * Checks whether the current user can add a child to the collection.
   *
   * @return bool
   *   Returns whether the user can add a child to the object.
   */
  public function canAddChild() {
    $grps = $this->groupMembershipLoader->loadByUser($this->currentUser);
    $access = FALSE;
    $plugin_id = 'group_node:collection';
    foreach ($grps as $grp) {
      if ($grp) {
        $access |= ($grp->hasPermission("edit $plugin_id entity", $this->currentUser));
      }
    }
    // ($access) ? AccessResult::allowed() : AccessResult::forbidden();
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($node = $this->routeMatch->getParameter('node')) {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
