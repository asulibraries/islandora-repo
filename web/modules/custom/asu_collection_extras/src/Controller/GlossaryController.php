<?php

namespace Drupal\asu_collection_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class GlossaryController.
 */
class GlossaryController extends ControllerBase {

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->loggerFactory = $container->get('logger.factory');
    $instance->requestStack = $container->get('request_stack');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * Getglossary.
   *
   * @return string
   *   Return Hello string.
   */
  public function getglossary($letter) {
    $args = [$letter];
    $view = Views::getView('collections');
    if (is_object($view)) {
      $view->setArguments($args);
      $view->setDisplay('page_1');
      $combine_value = $this->requestStack->getCurrentRequest()->query->get('combine');
      $view->setExposedInput(['combine' => $combine_value]);
      $view->preExecute();
      $view->execute();
      $content = $view->buildRenderable('page_1', $args);
      return $content;
    }
  }

}
