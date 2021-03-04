<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * Construct method.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request_stack service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
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
      $container->get('request_stack')
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
    $node_url = Url::fromRoute('<current>', []);
    $altmetrics_section = $this->getAltmetricsSection($node_url);
    return [
      'altmetrics-section' => [
        '#type' => 'container',
        'section' => $altmetrics_section,
      ],
    ];
  }

  /**
   * This will get a block for the altmetrics embed for a given object.
   *
   * @param string $url
   *   The given object's url.
   *
   * @return array
   *   The build array to insert into the block build function.
   */
  private function getAltmetricsSection($url) {
    static $id_suffix;
    // Need to increment if there are multiple instances of this block.
    $id_suffix = !($id_suffix) ? '' : $id_suffix + 1;
    $doi = // "10.1038/nature.2014.14583";
    $handle = "https://hdl.handle.net/2286/R.I.28324.0";
    if ($doi) {
      // in the node "Typed identifier field" with the type of 'Digital object
      // identifier'.
      $altmetrics_embed = ' data-doi="' . $doi . '"';
    } elseif ($handle) {
      $altmetrics_embed = ' data-handle="' . $handle . '"';
    } else {
      $altmetrics_embed = '';
    } 
    return (($altmetrics_embed) ? [
      'altmetrics-container' => [
        '#type' => 'item',
        '#id' => 'altmetrics_box',
        'container' => [
          '#type' => 'container',
          'left-block' => [
            '#type' => 'item',
            '#prefix' => '<div class="row"><div class="col-md-2">',
            '#suffix' => '</div>',
            '#markup' => '<div data-badge-popover="right" data-badge-type="2"' .
              $altmetrics_embed . ' data-hide-no-mentions="true" class="altmetric-embed"></div>',
          ],
//          // Drupal requires javascript to be attached to the render elements.
//          'right-block' => [
//            '#type' => 'item',
//            '#attached' => [
//              'library' => [
//                'asu_item_extras/interact',
//              ],
//            ],
//          ],
        ],
      ],
    ] : []);
  }

}
