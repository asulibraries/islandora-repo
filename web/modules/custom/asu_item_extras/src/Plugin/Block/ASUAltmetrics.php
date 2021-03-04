<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;

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
   * Construct method.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $plugin_definition
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
    $current_route = \Drupal::routeMatch();
    if ($current_route->getParameter('node')) {
      $node = $current_route->getParameter('node');
    }
    if (!is_object($node)) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node);
    }
    if (!$node) {
      return [];
    }
    $doi_val = "";
    $typed_idents = $node->field_typed_identifier;
    foreach ($typed_idents as $typed_ident) {
      if (!$doi_val) {
        $typed_target_id = $typed_ident->get("target_id")->getCastedValue();
        $paragraph = Paragraph::load($typed_target_id);
        $typed_ident_target_id = $paragraph->field_identifier_type->target_id;
        $typed_ident_type = Term::load($typed_ident_target_id)->get('field_identifier_predicate')->value;
        if ($typed_ident_type == 'doi') {
          $doi_val = $paragraph->get('field_identifier_value')->value;
        }
      }
    }
    $handle = $node->field_handle->value;
    if ($doi_val) {
      $altmetrics_embed = ' data-doi="' . $doi_val . '"';
    } elseif ($handle) {
      $altmetrics_embed = ' data-handle="' . $handle . '"';
    } else {
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
      ] : []);;
  }

}
