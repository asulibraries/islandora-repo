<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\islandora_matomo\IslandoraMatomoService;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Provides a 'Downloads count' Block.
 *
 * @Block(
 *   id = "asu_item_downloads",
 *   admin_label = @Translation("Item downloads count"),
 *   category = @Translation("Views"),
 * )
 */
class ASUItemDownloads extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The islandoraMatomo definition.
   *
   * @var \Drupal\islandora_matomo\IslandoraMatomoService
   */
  protected $islandoraMatomo;

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
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
   * @param \Drupal\islandora_matomo\IslandoraMatomoService $islandoraMatomo
   *   The islandoraMatomo service.
   * @param Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   A drupal entity type manager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, IslandoraMatomoService $islandoraMatomo, EntityTypeManager $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->islandoraMatomo = $islandoraMatomo;
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
      $container->get('islandora_matomo.default'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The output of this block should be:
     *  - Download count for all media on the object
     */
    $block_config = BlockBase::getConfiguration();
    if (is_array($block_config) && array_key_exists('child_node_id', $block_config)) {
      $node_id = $block_config['child_node_id'];
    }
    else {
      // Since this block should be set to display on node/[nid] pages that are
      // "ASU Repository Item", the underlying node can be accessed via the
      // path. When this block appears on the items/{nid}/members view, each
      // node.id value is passed as a parameter.
      if ($this->routeMatch->getParameter('node')) {
        $node = $this->routeMatch->getParameter('node');
        $node_id = (is_string($node) ? $node : $node->id());
      }
    }
    if ($node_id) {
      $mids = $this->entityTypeManager->getStorage('media')->getQuery()
        ->condition('field_media_of', $node_id)
        ->execute();
      $download_count = 0;
      foreach ($mids as $mid) {
        $fid = $this->islandoraMatomo->getFileFromMedia($mid);
        $download_count += $this->islandoraMatomo->getDownloadsForFile(['fid' => $fid]);

      }
      return [
        '#cache' => ['max-age' => 0],
        '#markup' => Markup::create($download_count),
      ];
    }
    else {
      return [
        '#markup' => Markup::create("This page is not a node. Please restrict this block's configuration to display on nodes only."),
      ];
    }
  }

}
