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
    $url = Url::fromRoute('<current>', array(), array('fragment' => 'permalink'));
    $link = Link::fromTextAndUrl(t('Permalink'), $url);
    $link = $link->toRenderable();
    $output_links[] = render($link);
    $iiif_html = $this->getIIIFHTML($node_url);
    return [
      '#markup' => 
        ((count($output_links) > 0) ? 
        "<ul class=''><li>" . implode("</li><li>", $output_links) . "</li></ul>" :
        "") . "\n" . $iiif_html,
    ];
  }

  private function getIIIFHTML($url) {
    return '      <div>
        <h4><span>International Image Interoperability Framework</h4>
      </div>

      <!-- Unnamed (Image) -->
      <div style="float:left; width:18%;padding-right:24px">
      <a href="https://iiif.io/technical-details/" target="_blank">
        <div id="u550" class="ax_default image">
        <img id="u550_img" class="img " src="' . \Drupal::request()->getSchemeAndHttpHost()
        . '/' . drupal_get_path('module', 'asu_search') . '/images/iiif_logo.png">
      </a></div>
      
      <!-- Unnamed (Rectangle) -->
      <div style="float:left; width:80%">
        <p><span>We support the </span><a href="https://iiif.io/technical-details/" target="_blank">IIIF</a><span> Presentation API</span></p>

        <!-- Unnamed (Rectangle) -->
        <div>
          <input type="text" readonly value="' . \Drupal::request()->getSchemeAndHttpHost() . $url->toString() . '/manifest" />
          <span class="copy_button">Copy link</span>
        </div>
    </div>' . "\n";
    
  }
}
