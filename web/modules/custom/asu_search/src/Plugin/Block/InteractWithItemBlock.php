<?php

namespace Drupal\asu_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides an 'Interact with item' Block.
 *
 * @Block(
 *   id = "interact_with_item_block",
 *   admin_label = @Translation("Interact with this item"),
 *   category = @Translation("Views"),
 * )
 */
class InteractWithItemBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /*
     * The title of the block could be dependant on the underlying Islandora
     * Model used. In liey of that, the title should just be "About this item".
     *
     * The links within this block should be:
     *  - view full metadata
     *  Print and share sub-block
     *  - Permalink
     *  Citations, Rights and Reuse sub-block
     *  International Image Interoperability Framework lookup
     *
     */
    // Since this block should be set to display on node/[nid] pages that are
    // either "Repository Item", "ASU Repository Item", or "Collection",
    // the underlying node can be accessed via the path.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node) {
      $nid = $node->id();
    } else {
      $nid = 0;
    }
    $output_links = array();
    // Add a link for the "Overview" of this node.
    $variables['nodeid'] = $nid;
    $node_url = Url::fromRoute('<current>', array());
    $link = Link::fromTextAndUrl(t('Overview'), $node_url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    // Add a link to the "View full metadata" anchor for this node.
    $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/node/' . $nid . '/full-metadata');
    $link = Link::fromTextAndUrl(t('View full metadata'), $url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    // Add a link to get the Permalink for this node. Could this be a javascript
    // event that will send the current node's URL to the copy buffer?
    $url = Url::fromUri(\Drupal::request()->getSchemeAndHttpHost() . '/node/' . $nid);
    $link = Link::fromTextAndUrl(t('Permalink'), $url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    $iiif_section = $this->get_IIIF_section($node_url);
    $citations_section = $this->get_citations_section($node_url);
    $print_and_share_section = $this->get_print_and_share_section($node_url);
    return [
        'unordered-list' => [
          '#type' => 'item',
          '#markup' =>
            ((count($output_links) > 0) ?
              "<ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul>" :
              "")
        ],
        $citations_section,
        $print_and_share_section,
        'iiif-section' => [
          '#type' => 'container',
          $iiif_section
        ]
      ];
  }

  private function get_IIIF_section($url) {
    return [
      'iiif-container' => [
        '#type' => 'item',
        '#title' => 'International Image Interoperability Framework',
        'container' => [
          '#type' => 'container',
          'left-block' => [
            '#type' => 'item',
            '#markup' => '          <div class="float_l_18 image">
              <a href="https://iiif.io/technical-details/" target="_blank">
                <img class="img " src="' .
                \Drupal::request()->getSchemeAndHttpHost() . "/" .
                drupal_get_path("module", "asu_search") . '/images/iiif_logo.png">
              </a>
            </div>',
          ],
          'right-block' => [
            '#type' => 'item',
            'input-box' => [
              '#type' => 'textfield',
              '#value' => \Drupal::request()->getSchemeAndHttpHost() . '/' . $url->toString() . '/manifest',
            ],
            '#prefix' => '<div class="float_l_80"><p><span>We support the </span><a href="https://iiif.io/technical-details/" target="_blank">IIIF</a><span> Presentation API</span></p>',
            '#suffix' => '<!-- Unnamed (Rectangle) -->
            <div>
              <span class="copy_button">Copy link</span>
            </div>
          </div>',
          ],
        ]
      ]
    ];
  }

  private function get_citations_section($url) {
    $links = array();
    $links[] = [
        '#markup' => 'Citing this image',
      ];
    $links[] = [
        '#markup' => 'Responsibilities of use',
      ];
    $links[] = [
        '#markup' => 'Licensing and Permissions',
      ];
    $links[] = [
        '#markup' => 'Linking and Embedding',
      ];
    $links[] = [
        '#markup' => 'Copies and Reproductions',
      ];
    $render_this = [
      '#theme' => 'item_list',
      '#items' => $links,
    ];
    $rendered_list = render($render_this);
    return [
      'citations-container' => [
        '#type' => 'item',
        '#title' => 'Citations, Rights and Reuse',
        '#attributes' => [
          'class' => array('float_l_49'),
        ],
        'container' => [
          '#type' => 'container',
          'the-items' => [
            '#type' => 'item',
            '#markup' => $rendered_list,
          ]]]];
  }

  private function get_print_and_share_section($url) {
    return [
      'print-container' => [
        '#type' => 'item',
        '#title' => 'Print and share',
        '#attributes' => [
          'class' => array('float_l_49'),
        ],
        'container' => [
          '#type' => 'container',
          'sharelinks' => [
            '#type' => 'item',
            '#markup' => asu_search_get_print_and_share_block($url),
            '#suffix' => '<br class="clearfloat" />',
          ]]]];
  }
}
