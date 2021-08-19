<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Cache\Cache;

/**
 * Provides an 'Altmetrics' Block.
 *
 * @Block(
 *   id = "asu_altmetrics",
 *   admin_label = @Translation("ASU Altmetrics block"),
 *   category = @Translation("Views"),
 * )
 */
class ASUAltmetrics extends BlockBase implements ContainerFactoryPluginInterface {

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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * Initializes the block and set dependency injection variables.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The parent class object.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return mixed
   *   The initialized form object.
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
     * The links within this block should be:
     *  - Citing this image
     *  - Responsibilities of use
     *  - Licensing and Permissions
     *  - Linking and Embedding
     *  - Copies and Reproductions
     */
    // Since this block should be set to display on node/[nid] pages that are
    // "ASU Repository Item", or possibly "Collection", the underlying
    // node can be accessed via the path.
    if ($this->currentRouteMatch->getParameter('node')) {
      $node = $this->currentRouteMatch->getParameter('node');
    }
    if (!isset($node)) {
      return [];
    }
    if (!is_object($node)) {
      $node = $this->entityTypeManager->getStorage('node')->load($node);
    }
    $doi_val = "";
    $typed_idents = $node->field_typed_identifier;
    foreach ($typed_idents as $typed_ident) {
      if (!$doi_val) {
        $typed_target_id = $typed_ident->get("target_id")->getCastedValue();
        $paragraph = $this->entityTypeManager->getStorage('paragraph')->load($typed_target_id);
        $typed_ident_target_id = $paragraph->field_identifier_type->target_id;
        if ($typed_ident_target_id) {
          $typed_ident_type = $this->entityTypeManager->getStorage('taxonomy_term')->load($typed_ident_target_id)->get('field_identifier_predicate')->value;
          if ($typed_ident_type == 'doi') {
            $doi_val = $paragraph->get('field_identifier_value')->value;
          }
        }
      }
    }
    $handle = $node->field_handle->value;
    if ($doi_val) {
      $altmetrics_embed = ' data-doi="' . $doi_val . '"';
    }
    elseif ($handle) {
      if (strstr($handle, "://")) {
        // We only want the ID part of the handle value.
        $urlparts = parse_url($handle);
        $handle = (array_key_exists('path', $urlparts) ?
          $urlparts['path'] : "");
        $handle = ltrim($handle, "/");
      }
      $altmetrics_embed = ' data-handle="' . $handle . '"';
    }
    else {
      $altmetrics_embed = '';
    }
    return (($altmetrics_embed) ?
      [
        '#type' => 'container',
        'altmetrics-container' => [
          '#type' => 'item',
          '#id' => 'altmetrics_box',
          'container' => [
            '#type' => 'container',
            'left-block' => [
              '#type' => 'item',
              '#markup' => '<div data-badge-popover="right" data-badge-type="2"' .
              $altmetrics_embed . ' data-hide-no-mentions="true" class="altmetric-embed"></div>',
            ],
              // Need the javascript to be attached to the render elements.
            'right-block' => [
              '#type' => 'item',
              '#attached' => [
                'library' => [
                  'asu_item_extras/altmetrics',
                ],
              ],
            ],
          ],
        ],
      ] : []);
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($this->currentRouteMatch->getParameter('node')) {
      $node = $this->currentRouteMatch->getParameter('node');
    }
    if (!is_object($node)) {
      $node = $this->entityTypeManager->getStorage('node')->load($node);
    }
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
