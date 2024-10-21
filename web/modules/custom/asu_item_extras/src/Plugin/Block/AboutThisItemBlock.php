<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'About this item' Block.
 *
 * @Block(
 *   id = "about_this_item_block",
 *   admin_label = @Translation("About this item"),
 *   category = @Translation("Views"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class AboutThisItemBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The routeMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor for About this Collection Block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('request_stack'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    /*
     * The title of the block could be dependant on the underlying Islandora
     * Model used. In liey of that, the title should just be "About this item".
     *
     * The links within this block should be:
     *  - Overview
     *  - Permalink
     */

    // Since this block should be set to display on node/[nid] pages that are
    // either "ASU Repository Item" or "Collection",
    // the underlying node can be accessed via the path.
    $node = $this->getContextValue('node');
    if (!isset($node)) {
      return [];
    }

    $output_links = [];

    // Add a link for the "Overview" of this node.
    $variables['nodeid'] = $nid;
    $url = Url::fromUri($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . '/items/' . $nid, ['attributes' => ['class' => 'nav-link']]);
    if ($link = Link::fromTextAndUrl($this->t('Overview'), $url)) {
      $output_links[] = $link->toRenderable();
    }

    // Add a link to get the Permalink for this node.
    if ($node->hasField('field_handle') && $node->get('field_handle')->value != NULL) {
      $hdl = $node->get('field_handle')->value;
      $output_links[] = [
        '#type' => 'container',
        '#attributes' => ['class' => 'permalink_button'],
        'link' => [
          '#type' => 'html_tag',
          '#tag' => 'a',
          '#attributes' => [
            'class' => [
              'btn', 'btn-maroon', 'btn-md', 'copy_permalink_link',
            ],
            'title' => $hdl,
          ],
          'icon' => [
            '#type' => 'html_tag',
            '#tag' => 'i',
            '#attributes' => [
              'class' => ['far', 'fa-copy', 'fa-lg', 'copy_permalink_link'],
              'title' => $hdl,
            ],
          ],
          'text' => [
            '#plain_text' => 'Copy permalink',
          ],
        ],
      ];
    }

    if (!empty($output_links)) {
      $output_links['#attached'] = [
        'library' => [
          'asu_item_extras/interact',
        ],
      ];
    }
    return $output_links;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $node = $this->getContextValue('node');
    if (!isset($node)) {
      return parent::getCacheTags();
    }
    else {
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
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
