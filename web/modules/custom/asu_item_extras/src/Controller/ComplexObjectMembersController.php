<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Views;

/**
 * Controller for Complex Object Members "Included in this item" view page.
 */
class ComplexObjectMembersController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a ComplexObjectMembersController object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  /**
   * Builds content for the asu_item_extras controllers.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   (optional) The user account.
   *
   * @return array
   *   The render array.
   */
  public function buildContent(UserInterface $user = NULL) {
    $node = $this->routeMatch->getParameter('node');
    $build_output = [];
    if ($node) {
      // What is the type for this node?
      $content_type = $node->getType();

      // What is the model for this node?
      $field_model_term = $node->get('field_model')->entity;
      $field_model = (isset($field_model_term) && is_object($field_model_term)) ?
        $field_model_term->getName() : '';

      // Check that the model for this node is set to "Complex Object".
      if (($content_type == 'asu_repository_item') && $field_model == 'Complex Object') {
        $view = Views::getView('included_in_complex_object');
        $args = [$node->id];
        if (is_object($view)) {
          $view->setArguments($args);
          $view->setDisplay('all_included_items');
          $view->preExecute();
          $view->execute();
          $build_output[0] = $view->buildRenderable('all_included_items', $args);
        }
      }
    }
    return [
      '#markup' => '<h2>' . $this->t('Included in this item') . '</h2><div class="row">',
      'build_output' => $build_output,
      '#suffix' => '</div>',
    ];
  }

}
