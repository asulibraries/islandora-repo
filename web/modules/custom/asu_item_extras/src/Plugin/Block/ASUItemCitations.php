<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Citations, Rights and Reuse' Block.
 *
 * @Block(
 *   id = "asu_item_citations",
 *   admin_label = @Translation("Citations, rights and reuse"),
 *   category = @Translation("Views"),
 * )
 */
class ASUItemCitations extends BlockBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The currentRouteMatch definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentRouteMatch $currentRouteMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * Initializes the block and set dependency injection variables.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The parent class object.
   *
   * @return mixed
   *   The initialized block object.
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->requestStack = $container->get('request_stack');
    return $instance;
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
    $url_string = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . $node_url->toString();
    $output_links = [];
    $url = Url::fromUri($url_string . '/citation/#citing');
    $link = Link::fromTextAndUrl($this->t('Citing this image'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#responsibilities');
    $link = Link::fromTextAndUrl($this->t('Responsibilities of use'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#licensing');
    $link = Link::fromTextAndUrl($this->t('Licensing and Permissions'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#linking');
    $link = Link::fromTextAndUrl($this->t('Linking and Embedding'), $url)->toRenderable();
    $output_links[] = render($link);
    $url = Url::fromUri($url_string . '/citation/#copies');
    $link = Link::fromTextAndUrl($this->t('Copies and Reproductions'), $url)->toRenderable();
    $output_links[] = render($link);
    $render_this = [
      '#markup' =>
      ((count($output_links) > 0) ?
          "<nav><ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul></nav>" :
          ""),
    ];
    return [
      'citations-container' => [
        '#type' => 'item',
        '#id' => 'citations_box',
        'container' => [
          '#type' => 'container',
          'the-items' => [
            '#type' => 'item',
            'render_this' => $render_this,
          ],
        ],
      ],
    ];
  }

}
