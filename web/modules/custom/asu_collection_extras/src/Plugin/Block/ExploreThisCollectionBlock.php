<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Explore this Collection' Block.
 *
 * @Block(
 *   id = "explore_this_collection_block",
 *   admin_label = @Translation("Explore this collection"),
 *   category = @Translation("Views"),
 * )
 */
class ExploreThisCollectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The routeMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Construct method.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Form Builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $form_builder,
    RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $search_form = $this->formBuilder->getForm('Drupal\asu_collection_extras\Form\ExploreForm');
    return $search_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // With this when your node change your block will rebuild.
    if ($node = $this->routeMatch->getParameter('node')) {
      // If there is node add its cachetag.
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // If you depends on \Drupal::routeMatch().
    // You must set context of this block with 'route' context tag.
    // Every new route this block will rebuild.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
