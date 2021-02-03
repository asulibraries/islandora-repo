<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ViewerController will handle the renderView for the associated route.
 */
class ViewerController extends ControllerBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentRouteMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * This will potentially do different things for various islandora models.
   *
   * @return string
   *   Return a view.
   */
  public function renderView($node) {
    $node = $this->currentRouteMatch->getParameter('node');
    if ($node) {
      $routeName = 'entity.node.canonical';
      $routeParameters = ['node' => $node->id()];
      $url = Url::fromRoute($routeName, $routeParameters);
      $content_type = $node->bundle();
      if ($content_type == 'asu_repository_item') {
        $builder = $this->entityTypeManager->getViewBuilder('node');
        if ($node->hasField('field_model') && !$node->get('field_model')->isEmpty()) {
          $model_term = $node->get('field_model')->referencedEntities()[0];
          $model = $model_term->getName();
          if ($model == 'Digital Document') {
            $view_mode = 'pdfjs';
            return $builder->view($node, $view_mode);
          }
          elseif ($model == 'Image') {
            $view_mode = 'open_seadragon';
            return $builder->view($node, $view_mode);
          }
          else {
            return new RedirectResponse($url->toString());
          }
        }
        else {
          return new RedirectResponse($url->toString());
        }
      }
      else {
        return new RedirectResponse($url->toString());
      }
    }
    else {
      $url = Url::fromRoute('<front>');
      return new RedirectResponse($url->toString());
    }

  }

}
