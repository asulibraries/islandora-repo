<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

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
        '#type' => 'container',
        '#id' => 'iiif_box',
        '#attributes' => ['class' => ['row']],
        'left-block' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-md-2']],
          '#markup' => '            <a class="icon-link" href="https://iiif.io/technical-details/" target="_blank">
                <img class="img" src="' .
          $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . "/" .
          \Drupal::service('extension.list.module')->getPath("asu_item_extras") . '/images/IIIF-logo-colored-text.svg" alt="IIIF logo"></a>',
        ],
          // Drupal requires javascript to be attached to the render elements.
        'right-block' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['col-md-9', 'offset-md-1']],
          'iiif-link-field' => [
            '#type' => 'textfield',
            '#title' => $this->t('Item IIIF Manifest URL'),
            '#id' => 'iiif_editbox' . $id_suffix,
            '#value' => str_replace('/items', '/node', $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . $url->toString()) . '/manifest',
          ],
          // We attempted a link type but it wouldn't render, so markup instead.
          'copy-button' => [
            '#markup' => '<a id="copy_manifest_link" class="btn btn-maroon btn-md">Copy link</a>',
          ],
        ],
      ],
    ];
  }

}
