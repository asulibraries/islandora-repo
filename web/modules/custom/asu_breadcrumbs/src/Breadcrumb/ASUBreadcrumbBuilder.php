<?php
// Define the namespace for your class
namespace Drupal\asu_breadcrumbs\Breadcrumb;

// Use namespaces of classes that you need
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link ;

// Define your class and implement BreadcrumbBuilderInterface
class ASUBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    // You can put any logic here. You must return a BOOLEAN TRUE or FALSE.
    //-----[ BEGIN example ]-----
    // Get all parameters.
    $parameters = $attributes->getParameters()->all();

    // Determine if the current page is a node page
    if (isset($parameters['node']) && !empty($parameters['node'])) {
      return TRUE;
    }
    //-----[ END example ]-----

    // Still here? This does not apply.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    // Define a new object of type Breadcrumb
    $breadcrumb = new Breadcrumb();

    // You can put any logic here to build out your breadcrumb.
    //-----[ BEGIN example ]-----
    // Add a link to the homepage as our first crumb.
    $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));

    // Get the node for the current page
    $node = \Drupal::routeMatch()->getParameter('node');

    $bundle = $node->bundle();
    $route_name = $route_match->getRouteName();
    // Need to also include the canonical view of any node.
    $is_node_or_node_subpage = (($route_name == 'entity.node.canonical') ||
      ($route_name == 'asu_item_extras.full_metadata_view') ||
      ($route_name == 'asu_item_extras.complex_object_members') ||
      ($route_name == 'asu_item_extras.viewer_controller_render_view'));
    $is_collection_subpage = ($route_name == 'asu_statistics.collection_statistics_view');

    if ($is_node_or_node_subpage && ($bundle == 'asu_repository_item') || ($is_collection_subpage && $bundle == 'collection')) {
      $link = $this->_get_node_link();
      $breadcrumb->addLink($link);
    }

    // Don't forget to add cache control by a route.
    // Otherwise all pages will have the same breadcrumb.
    $breadcrumb->addCacheContexts(['route']);

    // Return object of type breadcrumb.
    return $breadcrumb;
  }

  private function _get_node_link() {
    $node = \Drupal::routeMatch()->getParameter('node');
    $options = ['absolute' => TRUE];
    $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $node->id()], $options);
    $first_title = $node->field_title[0];
    $view = ['type' => 'complex_title_formatter'];
    $first_title_view = $first_title->view($view);
    $title = \Drupal::service('renderer')->render($first_title_view);
    $link = Link::fromTextAndUrl($title, $url); // ->toRenderable();
    return $link;
  }
}