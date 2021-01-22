<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Included in this item' Block.
 *
 * @Block(
 *   id = "asu_included_in_item",
 *   admin_label = @Translation("Included in this item"),
 *   category = @Translation("Views"),
 * )
 */
class ASUIncludedInItem extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The currentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Construct method.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The currentRouteMatch definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entityTypeManager,
    CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * Does the initialization of the block setting dependency injection vars.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The parent class object.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The output of this block should be:
     *  - the first three child nodes to any complex object
     */
    if ($this->currentRouteMatch->getParameter('node')) {
      $node = $this->currentRouteMatch->getParameter('node');
      $build_output = [];
      $node_id = (is_object($node)) ? $node->id() : $node;
      $children = asu_item_extras_get_complex_object_child_nodes($node_id, 3);
      foreach ($children as $child_obj) {
        if ($child_obj->entity_id) {
          $node = $this->entityTypeManager->getStorage('node')->load($child_obj->entity_id);
          $build_output[] = $this->entityTypeManager->getViewBuilder('node')->view($node, 'complex_object_child_box');
        }
      }
      return [
        '#cache' => ['max-age' => 0],
        'build_output' => $build_output,
      ];
    }
    else {
      return [
        '#markup' => Markup::create("This page is not a node. Please restrict this block's configuration to display on nodes only."),
      ];
    }
  }

}
