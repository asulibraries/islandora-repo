<?php

namespace Drupal\asu_admin_toolbox\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ViewerController will handle the renderView for the associated route.
 */
class SolrReindexController extends ControllerBase {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentRouteMatch = $container->get('current_route_match');
    $instance->messenger = $container->get('messenger');
    return $instance;
  }

  /**
   * This should reindex the underlying node, display message, and redirect.
   *
   * @return string
   *   Return a view.
   */
  public function reindexNodeRedirect($node) {
    $node = $this->currentRouteMatch->getParameter('node');
    if ($node) {
      $routeName = 'entity.node.canonical';
      $routeParameters = ['node' => $node->id()];
      $url = Url::fromRoute($routeName, $routeParameters);
      search_api_entity_update($node);
      $this->messenger->addMessage(t('The Solr reindex process has been called for @url.', ['@url' => render($node->toLink()->toRenderable())]));
      return new RedirectResponse($url->toString());
    }
    else {
      $url = Url::fromRoute('<front>');
      return new RedirectResponse($url->toString());
    }
  }

}
