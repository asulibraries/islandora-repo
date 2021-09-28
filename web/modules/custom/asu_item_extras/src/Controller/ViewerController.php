<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatch;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * IslandoraUtils class.
   */
  protected $islandoraUtils;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentRouteMatch = $container->get('current_route_match');
    $instance->islandoraUtils = $container->get('islandora.utils');
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
          $origfile_term = $this->islandoraUtils->getTermForUri('http://pcdm.org/use#OriginalFile');
          $origfile = $this->islandoraUtils->getMediaWithTerm($node, $origfile_term);
          if ($model == 'Digital Document') {
            if (!is_null($origfile)) {
              $view_mode = 'pdfjs';
              return $builder->view($node, $view_mode);
            }
            else {
              \Drupal::messenger()->addMessage("There is no media to preview. You have been redirected to this item's overview page.");
              return new RedirectResponse($url->toString());
            }
          }
          elseif ($model == 'Image') {
            if (!is_null($origfile)) {
              $view_mode = 'open_seadragon';
              return $builder->view($node, $view_mode);
            }
            else {
              \Drupal::messenger()->addMessage("There is no media to preview. You have been redirected to this item's overview page.");
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
        return new RedirectResponse($url->toString());
      }
    }
    else {
      $url = Url::fromRoute('<front>');
      return new RedirectResponse($url->toString());
    }

  }

  /**
   * Checks if the user can access the Original File Media.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   The current routing match.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, RouteMatch $route_match) {
    if ($route_match->getParameters()->has('node')) {
      $node = $route_match->getParameter('node');
      if (!$node instanceof NodeInterface) {
        $node = Node::load($node);
      }
      if (in_array('administrator', $account->getRoles())) {
        return AccessResult::allowed();
      }
      // TODO - this may be too restrictive?
      if ($node->access('view', $account)) {
        // user can at least view the node
        // can user view the media though?
        $islandora_utils = \Drupal::service('islandora.utils');
        $origfile_term = $islandora_utils->getTermForUri('http://pcdm.org/use#OriginalFile');
        $origfile = $islandora_utils->getMediaWithTerm($node, $origfile_term);

        if (!is_null($origfile) && $origfile->access('view', $account)) {
          // User can access media
          $date = new \DateTime();
          $today = $date->format("c");
          if ( $node->hasField('field_embargo_release_date') && $node->get('field_embargo_release_date') && $node->get('field_embargo_release_date')->value >= $today) {
            return AccessResult::forbidden();
          }
          return AccessResult::allowed();
        }
        elseif (is_null($origfile)) {
          // Must allow in order for the redirect in renderView above to work.
          return AccessResult::allowed();
        } else {
          return AccessResult::forbidden();
        }
      }
    }
    return AccessResult::forbidden();
  }

}
