<?php

namespace Drupal\asu_collection_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
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
    $collection_node = $this->currentRouteMatch->getParameter('node');
    $children_nids = asu_collection_extras_get_collection_children($collection_node, TRUE, 4);

    $rendered_nodes = $this->renderNodes($children_nids);

    $return = [
      '#cache' => ['max-age' => 0],
      '#markup' =>
      ((count($children_nids) > 0) ?
        $rendered_nodes :
        ""),
      'lib' => [
        '#attached' => [
          'library' => [
            'asu_collection_extras/style',
          ],
        ],
      ],
    ];
    return $return;
  }

  /**
   * Renders the nodes to provide content for the template.
   *
   * @param array $nids
   *   An array of node nid values.
   *
   * @return string
   *   The rendered HTML markup for the nodes as needed for the template.
   */
  private function renderNodes(array $nids) {
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $storage = $this->entityTypeManager->getStorage('node');
    $output = [];
    foreach ($nids as $nid) {
      $node = $storage->load($nid);
      $build = $view_builder->view($node, 'collection_browse_teaser');
      $output[] = render($build);
    }
    return '<div class="card-deck">' . implode('', $output) . "</div>";
  }

}
