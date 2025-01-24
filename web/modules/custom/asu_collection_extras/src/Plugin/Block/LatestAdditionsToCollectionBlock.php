<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\asu_islandora_utils\AsuUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Latest additions to collection' Block.
 *
 * @Block(
 *   id = "latest_additions_to_collection_block",
 *   admin_label = @Translation("Latest Additions To Collection"),
 *   category = @Translation("Views"),
 * )
 */
class LatestAdditionsToCollectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The currentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The AsuUtils definition.
   *
   * @var \Drupal\asu_islandora_utils\AsuUtils
   */
  protected $asuUtils;

  /**
   * Construct method.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The currentRouteMatch definition.
   * @param \Drupal\asu_islandora_utils\AsuUtils $asuUtils
   *   The ASU Utils service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    CurrentRouteMatch $currentRouteMatch,
    AsuUtils $asuUtils,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->asuUtils = $asuUtils;
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
      $container->get('current_route_match'),
      $container->get('asu_utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $collection_node = $this->currentRouteMatch->getParameter('node');
    $children_nids = $this->asuUtils->getNodeChildren($collection_node, TRUE, 4);
    if (empty($children_nids)) {
      return [];
    }

    $render_array = [
      '#cache' => ['max-age' => 0],
      'lib' => [
        '#attached' => [
          'library' => [
            'asu_collection_extras/style',
          ],
        ],
      ],
    ];
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $storage = $this->entityTypeManager->getStorage('node');
    $items_container = ['#type' => 'container', '#attributes' => ['class' => ['row']]];
    foreach ($children_nids as $nid) {
      $items_container[$nid] = $view_builder->view($storage->load($nid), 'collection_browse_teaser');
    }
    $render_array['node_list'] = $items_container;
    $render_array['see_more'] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'br',
        '#attributes' => ['class' => ['clearfloat']],
      ],
      [
        '#type' => 'container',
        [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          'link' => Link::fromTextAndUrl(
            'Explore all items',
            Url::fromUri(
              "base:/collections/{$collection_node->id()}/search",
              [
                'query' => ['search_api_fulltext' => ''],
                'attributes' => ['class' => ['btn', 'btn-maroon']],
              ]
            )
          )->toRenderable(),
        ],
      ],
    ];
    return $render_array;
  }

}
