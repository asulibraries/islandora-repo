<?php

namespace Drupal\asu_item_extras\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Controller for Complex Object Members "Included in this item" view page.
 */
class ComplexObjectMembersController extends ControllerBase implements ContainerFactoryPluginInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a ComplexObjectMembersController object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A drupal entity type manager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, EntityTypeManager $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
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
      $field_model_tid = $parent_node->get('field_model')->getString();
      $field_model_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($field_model_tid);
      $field_model = (isset($field_model_term) && is_object($field_model_term)) ?
        $field_model_term->getName() : '';

      // Check that the model for this node is set to "Complex Object".
      if (($content_type == 'asu_repository_item') && $field_model == 'Complex Object') {
        $children = asu_item_extras_get_complex_object_child_nodes($node->id());
        foreach ($children as $child_obj) {
          if ($child_obj->entity_id) {
            $node = $this->entityTypeManager->getStorage('node')->load($child_obj->entity_id);
            $builder = $this->entityTypeManager->getViewBuilder('node');
            $build_output[] = $builder->view($node, 'complex_object_child_box');
          }
        }
      }
    }
    return [
      '#markup' => '<h2>' . $this->t('Included in this item') . '</h2>',
      '#cache' => ['max-age' => 0],
      'build_output' => $build_output,
    ];
  }

}
