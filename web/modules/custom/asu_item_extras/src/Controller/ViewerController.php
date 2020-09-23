<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class ViewerController.
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
   * Render_view.
   *
   * @return string
   *   Return a view.
   */
  public function render_view($node) {
    $nid = $this->currentRouteMatch->getParameter('node');
    if ($nid) {
      $routeName = 'entity.node.canonical';
      $routeParameters = ['node' => $nid];
      $url = Url::fromRoute($routeName, $routeParameters);
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      $content_type = $node->bundle();
      if ($content_type == 'asu_repository_item') {
        $builder = $this->entityTypeManager->getViewBuilder('node');
        if ($node->hasField('field_model') && !$node->get('field_model')->isEmpty()) {
          $model_term = $node->get('field_model')->referencedEntities()[0];
          $model = $model_term->getName();
          if ($model == 'Digital Document') {
            $view_mode = 'pdfjs';
          }
          elseif ($model == 'Image') {
            $view_mode = 'open_seadragon';
          }
          else {
            return new RedirectResponse($url->toString());
          }
        }
        else {
          return new RedirectResponse($url->toString());
        }
        $build = $builder->view($node, $view_mode);
        return $build;
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
