<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var \Drupal\Core\Entity\EntityTypeManager
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
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager definition.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer class.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManager $entityTypeManager,
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
    if (is_array($block_config) && array_key_exists('parent_node_id', $block_config)) {
      $parent_node_id_field_value = $block_config['parent_node_id'];
      $parent_node_id = $parent_node_id_field_value[0]['#plain_text'];
      $parent_node = $this->entityTypeManager->getStorage('node')->load($parent_node_id);
      // Look at the islandora_model value of the parent node and if it is a
      // "Complex Object".
      if ($parent_node) {
        $field_model_tid = $parent_node->get('field_model')->getString();
        $field_model_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($field_model_tid);
        $field_model = (isset($field_model_term) && is_object($field_model_term)) ?
          $field_model_term->getName() : '';
        if ($field_model == 'Complex Object') {
          // parent_link is calculated based on the parent_node values:
          $options = ['absolute' => TRUE];
          $parent_url = Url::fromRoute('entity.node.canonical', ['node' => $parent_node_id], $options);
          $first_title = $parent_node->field_title[0];
          $view = ['type' => 'complex_title_formatter'];
          $first_title_view = $first_title->view($view);
          $parent_title = $this->renderer->render($first_title_view);
          $link = Link::fromTextAndUrl($parent_title, $parent_url)->toRenderable();
          $rendered_link = render($link);
          $output = 'Part of ' . $rendered_link;
        }
      }
    }
    return [
      '#cache' => ['max-age' => 0],
      '#markup' => $output,
    ];
  }

}
