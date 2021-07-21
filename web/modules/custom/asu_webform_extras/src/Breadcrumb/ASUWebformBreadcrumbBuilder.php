<?php

namespace Drupal\asu_webform_extras\Breadcrumb;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\asu_breadcrumbs\ASUBreadcrumbBuilder;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Template\Attribute;

/**
 * {@inheritdoc}
 */
class ASUWebformBreadcrumbBuilder extends ASUBreadcrumbBuilder {

  use StringTranslationTrait;

  /**
   * The current route's entity or plugin type.
   *
   * @var string
   */
  protected $type;

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
   * The core request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Drupal renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a ASUWebformBreadcrumbBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Storage to load nodes.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The core request object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\\Core\\Render\\Renderer $renderer
   *   Drupal core renderer.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, RequestStack $request, ConfigFactoryInterface $config_factory, Renderer $renderer) {
    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->request = $request;
    $this->config = $config_factory->get('asu_breadcrumbs.breadcrumbs');
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $current_route = $route_match->getRouteName();
    if ($current_route == 'entity.webform.canonical') {
      $this->type = 'webform_access';
    }

    return ($this->type) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $current_route = $route_match->getRouteName();
    if ($current_route == 'entity.webform.canonical') {
      $item_id = $this->request->getCurrentRequest()->query->get('item');
      $collection_id = $this->request->getCurrentRequest()->query->get('collection');
      $closest_node = ($item_id) ? $this->nodeStorage->load($item_id) :
        $this->nodeStorage->load($collection_id);
      if (is_object($closest_node)) {
        $chain = [];
        $this->walkMembership($closest_node, $chain);
        // Add membership chain to the breadcrumb.
        foreach ($chain as $chainlink) {
          $breadcrumb->addCacheableDependency($chainlink);
          $breadcrumb->addLink($chainlink->toLink());
        }
      }
      $breadcrumb->addLink(Link::createFromRoute('Feedback', '<none>'));
    }
    else {
      $breadcrumb->addLink(Link::createFromRoute($this->t('Administration'), 'system.admin'));
      $breadcrumb->addLink(Link::createFromRoute($this->t('Structure'), 'system.admin_structure'));
      $breadcrumb->addLink(Link::createFromRoute($this->t('Webforms'), 'entity.webform.collection'));
      $breadcrumb->addLink(Link::createFromRoute($this->t('Access'), 'entity.webform_access_group.collection'));
      switch ($this->type) {
        case 'webform_access_group':
          $breadcrumb->addLink(Link::createFromRoute($this->t('Groups'), 'entity.webform_access_group.collection'));
          break;

        case 'webform_access_type';
          $breadcrumb->addLink(Link::createFromRoute($this->t('Types'), 'entity.webform_access_type.collection'));
          break;
      }
      // This breadcrumb builder is based on a route parameter, and hence it
      // depends on the 'route' cache context.
      $breadcrumb->addCacheContexts(['route']);
    }

    return $breadcrumb;
  }

}
