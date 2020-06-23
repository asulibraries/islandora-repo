<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Item top section' Block.
 *
 * @Block(
 *   id = "asu_item_top_section",
 *   admin_label = @Translation("Item top section"),
 *   category = @Translation("Views"),
 * )
 */
class ASUItemTopSection extends BlockBase {

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
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    } else {
      $nid = 0;
    }
    $node_url = Url::fromRoute('<current>', array());
    $url_string = \Drupal::request()->getSchemeAndHttpHost() . $node_url->toString();
    $output_links = array();
    $url = Url::fromUri($url_string . '/citation/#citing');
    $link = Link::fromTextAndUrl(t('Citing this image'), $url)->toRenderable();
    $output_links[] = render($link);
    $islandora_utils = \Drupal::service('islandora.utils');
    $servicefile_term = $islandora_utils->getTermForUri('http://pcdm.org/use#ServiceFile');
    $servicefile_media = $islandora_utils->getMediaWithTerm($node, $servicefile_term);
    $servicefile = \Drupal::entityTypeManager()->getViewBuilder('media')->view($servicefile_media, 'source');
    return [
      'top-container' => [
        '#type' => 'item',
        '#id' => 'top_box',
        '#suffix' => '<br class="clearfloat" />',
        'container' => [
          '#type' => 'container',
          'left-block' => [
            '#type' => 'item',
            '#prefix' => '<div class="float_l_49">',
            '#suffix' => '</div>',
            'servicefile' => [
              '#type' => 'item',
              '#prefix' => '<div class="image_view_overlay">',
                // <a href="/node/' . $nid . '/openseadragon_view">
              '#suffix' => '<a href="/node/' . $nid . '/openseadragon_view"><span class="expand_grip">&nbsp;</span><span class="expand"></span></a></div>',
              $servicefile,
            ],
          ],
          'right-block' => [
            '#type' => 'item',
            '#prefix' => '<div class="float_l_49">',
            '#suffix' => '</div>',
          ],
        ]
      ]
    ];
  }
}
