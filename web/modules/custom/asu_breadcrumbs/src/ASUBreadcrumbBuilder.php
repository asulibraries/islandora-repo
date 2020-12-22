<?php

namespace Drupal\asu_breadcrumbs;

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Define your class and implement BreadcrumbBuilderInterface.
 */
class ASUBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Storage to load nodes.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Drupal renderer.
   *
   * @var \Drupal\service
   */
  protected $renderer;

  /**
   * Core drupal routeMatch.
   *
   * @var \\Drupal\Core\Routing\CurrentRouteMatch
   */
  // protected $routeMatch;

  /**
   * Constructs a breadcrumb builder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Storage to load nodes.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, Drupal\Core\Render\RendererInterface $renderer) {
    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->config = $config_factory->get('asu_breadcrumbs.breadcrumbs');
    $this->renderer = $renderer;
  }
 
  /**
   * {@inheritdoc}
   */
//  public static function create(ContainerInterface $container) {
//    $instance = parent::create($container);
//    $instance->renderer = $container->get('renderer');
//    // $instance->routeMatch = $container->get('routeMatch');
//    return $instance;
//  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    // Using getRawParameters for consistency (always gives a
    // node ID string) because getParameters sometimes returns
    // a node ID string and sometimes returns a node object.
    $nid = $attributes->getRawParameters()->get('node');
    if (!empty($nid)) {
      $node = $this->nodeStorage->load($nid);
      return (!empty($node) && $node->hasField($this->config->get('referenceField')));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $nid = $route_match->getRawParameters()->get('node');
    $node = $this->nodeStorage->load($nid);
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    $route_name = $route_match->getRouteName();

    $chain = [];
    $this->walkMembership($node, $chain);

    if (!$this->config->get('includeSelf')) {
      array_pop($chain);
    }

    $breadcrumb->addCacheableDependency($node);

    // Add membership chain to the breadcrumb.
    foreach ($chain as $chainlink) {
      $breadcrumb->addCacheableDependency($chainlink);
      $breadcrumb->addLink($chainlink->toLink());
    }
    // Get the node for the current page.
    if (is_object($node)) {
      $bundle = $node->bundle();
      $route_name = $route_match->getRouteName();
      // Need to also include the canonical view of any node.
      $is_node_or_node_subpage = (
        ($route_name == 'asu_item_extras.full_metadata_view') ||
        ($route_name == 'asu_item_extras.complex_object_members') ||
        ($route_name == 'asu_item_extras.viewer_controller_render_view'));
      $is_collection_subpage =
        ($route_name == 'asu_statistics.collection_statistics_view');
      if ($is_node_or_node_subpage && ($bundle == 'asu_repository_item') ||
        ($is_collection_subpage && $bundle == 'collection')) {
        $link = $this->getNodeLink($route_match);
        $breadcrumb->addLink($link);
      }
    }
    $breadcrumb->addCacheContexts(['route']);
    return $breadcrumb;
  }

  /**
   * Follows chain of field_member_of links.
   *
   * We pass crumbs by reference to enable checking for looped chains.
   */
  protected function walkMembership(EntityInterface $entity, &$crumbs) {
    // Avoid infinate loops, return if we've seen this before.
    foreach ($crumbs as $crumb) {
      if ($crumb->uuid == $entity->uuid) {
        return;
      }
    }

    // Add this item onto the pile.
    array_unshift($crumbs, $entity);

    if ($this->config->get('maxDepth') > 0 && count($crumbs) >= $this->config->get('maxDepth')) {
      return;
    }

    // Find the next in the chain, if there are any.
    if ($entity->hasField($this->config->get('referenceField')) &&
      !$entity->get($this->config->get('referenceField'))->isEmpty() &&
      $entity->get($this->config->get('referenceField'))->entity instanceof EntityInterface) {
      $this->walkMembership($entity->get($this->config->get('referenceField'))->entity, $crumbs);
    }
  }

  /**
   * Helper function to get a link for the current node.
   *
   * @return Drupal\Core\Link
   *   The link to the current node.
   */
  private function getNodeLink(Drupal\Core\Routing\RouteMatchInterface $route_match) {
    $node = $route_match->getParameter('node');
    // No ... $node = $this->routeMatch->getParameter('node');.
    if (is_object($node)) {
      $options = ['absolute' => TRUE];
      $url = Url::fromRoute('entity.node.canonical', ['node' => $node->id()], $options);
      $first_title = $node->field_title[0];
      $view = ['type' => 'complex_title_formatter'];
      $first_title_view = $first_title->view($view);
      $title = $this->renderer->render($first_title_view);
      $link = Link::fromTextAndUrl($title, $url);
      return $link;
    }
  }

}
