<?php

namespace Drupal\self_deposit\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters webform routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.webform_submission.canonical')) {
      $route->setDefault('_controller', '\Drupal\self_deposit\Controller\SelfDepositViewController::view');
    }
  }

}