<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'Part of {parent_complex_object:pid}' Block.
 *
 * @Block(
 *   id = "asu_search_item_is_part_of",
 *   admin_label = @Translation("Search Item is part of (complex object)"),
 *   category = @Translation("Views"),
 * )
 */
class ASUSearchItemIsPartOf extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager definition.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer class.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
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
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The output of this block should be:
     *  - "Part of {link to : parent complex object}"
     */
    $output = '';
    $block_config = BlockBase::getConfiguration();
    if (is_array($block_config) && array_key_exists('paragraph', $block_config)) {
      $para = $block_config['paragraph'];
      $parent_node_id = $block_config['parent_node_id'];
      $first_title_view = $para->view(['type' => 'complex_title_formatter']);
      $parent_title = $this->renderer->render($first_title_view);
      $parent_url = Url::fromRoute('entity.node.canonical', ['node' => $parent_node_id], ['absolute' => TRUE]);
      $link = Link::fromTextAndUrl($parent_title, $parent_url)->toRenderable();
      $rendered_link = render($link);
      $output = 'Part of ' . $rendered_link;
    }
    return [
      '#markup' => $output,
    ];
  }

}
