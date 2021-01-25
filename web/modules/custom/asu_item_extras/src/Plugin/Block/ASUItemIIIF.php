<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'International Image Interoperability Framework' Block.
 *
 * @Block(
 *   id = "asu_item_iiif",
 *   admin_label = @Translation("International Image Interoperability Framework"),
 *   category = @Translation("Views"),
 * )
 */
class ASUItemIIIF extends BlockBase implements ContainerFactoryPluginInterface {

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
    $iiif_section = $this->getIiifSection($node_url);
    return [
      'iiif-section' => [
        '#type' => 'container',
        'section' => $iiif_section,
      ],
    ];
  }

  /**
   * This will get a block for IIIF manifest for a given object.
   *
   * @param string $url
   *   The given object's url.
   *
   * @return array
   *   The build array to insert into the block build function.
   */
  private function getIiifSection($url) {
    static $id_suffix;
    // Need to increment if there are multiple instances of this block.
    $id_suffix = !($id_suffix) ? '' : $id_suffix + 1;
    return [
      'iiif-container' => [
        '#type' => 'item',
        '#id' => 'iiif_box',
        'container' => [
          '#type' => 'container',
          'left-block' => [
            '#type' => 'item',
            '#prefix' => '<div class="row"><div class="col-md-2">',
            '#suffix' => '</div>',
            '#markup' => '            <a class="icon-link" href="https://iiif.io/technical-details/" target="_blank">
                <img class="img" src="' .
            $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . "/" .
            drupal_get_path("module", "asu_item_extras") . '/images/IIIF-logo-colored-text.svg">
              </a>',
          ],
          // Drupal requires javascript to be attached to the render elements.
          'right-block' => [
            '#type' => 'item',
            '#attached' => [
              'library' => [
                'asu_item_extras/interact',
              ],
            ],
            'input-box' => [
              '#type' => 'textfield',
              '#id' => 'iiif_editbox' . $id_suffix,
              '#value' => $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . $url->toString() . '/manifest',
            ],
            '#prefix' => '<div class="col-md-6 offset-md-1"><p>We support the <a href="https://iiif.io/technical-details/" target="_blank">IIIF</a> Presentation API</p><div class="row no-gutters"><div class="col-9">',
            '#suffix' => '<!-- Unnamed (Rectangle) -->
            </div>
            <div class="col">
              <a id="copy_manifest_link" class="btn btn-primary copy_button">Copy link</a>
            </div>
            </div>
            </div>
          </div>',
          ],
        ],
      ],
    ];
  }

}
